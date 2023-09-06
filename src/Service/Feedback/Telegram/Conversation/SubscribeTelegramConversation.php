<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Feedback\Telegram\SubscribeTelegramConversationState;
use App\Entity\Intl\Currency;
use App\Entity\Money;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Exception\Telegram\Api\InvalidCurrencyTelegramException;
use App\Exception\ValidatorException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Repository\Telegram\TelegramPaymentMethodRepository;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\CurrencyProvider;
use App\Service\MoneyFormatter;
use App\Service\Telegram\Payment\TelegramPaymentManager;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversation;
use App\Service\Telegram\TelegramConversationInterface;
use App\Service\Telegram\TelegramUserProvider;
use App\Service\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;
use Psr\Log\LoggerInterface;

/**
 * @property SubscribeTelegramConversationState $state
 */
class SubscribeTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CURRENCY_QUERIED = 10;
    public const STEP_SUBSCRIPTION_PLAN_QUERIED = 20;
    public const STEP_PAYMENT_METHOD_QUERIED = 30;
    public const STEP_PAYMENT_QUERIED = 40;
    public const STEP_CANCEL_PRESSED = 50;

    public function __construct(
        private readonly Validator $validator,
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlansProvider,
        private readonly TelegramPaymentMethodRepository $paymentMethodRepository,
        private readonly TelegramPaymentManager $paymentManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly CurrencyProvider $currencyProvider,
        private readonly MoneyFormatter $moneyFormatter,
        private readonly TelegramBotRepository $botRepository,
        private readonly FeedbackSubscriptionPlanProvider $planProvider,
        private readonly TelegramUserProvider $userProvider,
        private readonly LoggerInterface $logger,
    )
    {
        parent::__construct(new SubscribeTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg, $entity),
            self::STEP_CURRENCY_QUERIED => $this->gotCurrency($tg, $entity),
            self::STEP_SUBSCRIPTION_PLAN_QUERIED => $this->gotSubscriptionPlan($tg, $entity),
            self::STEP_PAYMENT_METHOD_QUERIED => $this->gotPaymentMethod($tg, $entity),
        };
    }

    public function start(TelegramAwareHelper $tg, Entity $entity): ?string
    {
        $paymentMethodCount = count($this->getPaymentMethods($tg));

        if (!$tg->getTelegram()->getBot()->acceptPayments() || $paymentMethodCount === 0) {
            $tg->stopConversation($entity);

            $message = $tg->trans('reply.not_accept_payments');
            $message = $tg->failText($message);

            $keyboard = $this->chooseActionChatSender->getKeyboard($tg);

            return $tg->reply($message, keyboard: $keyboard)->null();
        }

        $this->state->setCurrencyStep($tg->getCurrencyCode() === null);
        $this->state->setPaymentMethodStep($paymentMethodCount > 1);

        if ($this->state->isCurrencyStep()) {
            return $this->queryCurrency($tg);
        }

        return $this->querySubscriptionPlan($tg);
    }

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'subscribe');
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity)->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function getCurrencyQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.currency', domain: 'subscribe');

        if ($help) {
            $query = $tg->view('subscribe_currency_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryCurrency(TelegramAwareHelper $tg, bool $help = false): ?string
    {
        $this->state->setStep(self::STEP_CURRENCY_QUERIED);

        $message = $this->getCurrencyQuery($tg, $help);

        $buttons = $this->getCurrencyButtons($tg);

        if ($this->state->hasNotSkipHelpButton('currency')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCurrency(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('currency');

            return $this->queryCurrency($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($tg->matchText(null)) {
            $currency = null;
        } else {
            $currency = $this->getCurrencyByButton($tg->getText(), $tg);
        }

        if ($currency === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryCurrency($tg);
        }

        $this->state->setCurrency($currency);
        try {
            $this->validator->validate($this->state, groups: 'currency');
            $tg->getTelegram()->getMessengerUser()?->getUser()->setCurrencyCode($currency->getCode());
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryCurrency($tg);
        }

        return $this->querySubscriptionPlan($tg);
    }

    public function getSubscriptionPlanQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $query .= $tg->trans('query.subscription_plan', domain: 'subscribe');

        if ($help) {
            $query = $tg->view('subscribe_subscription_plan_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function querySubscriptionPlan(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SUBSCRIPTION_PLAN_QUERIED);

        $message = $this->getSubscriptionPlanQuery($tg, $help);

        $buttons = $this->getSubscriptionPlanButtons($tg);

        if ($this->state->isCurrencyStep()) {
            $buttons[] = $tg->backButton();
        } else {
            $buttons[] = $this->getChangeCurrencyButton($tg);
        }

        if ($this->state->hasNotSkipHelpButton('subscription_plan')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotSubscriptionPlan(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($this->state->isCurrencyStep() && $tg->matchText($tg->backButton()->getText())) {
            return $this->queryCurrency($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('subscription_plan');

            return $this->querySubscriptionPlan($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($tg->matchText($this->getChangeCurrencyButton($tg)->getText())) {
            $this->state->setCurrencyStep(true);

            return $this->queryCurrency($tg);
        }

        if ($tg->matchText(null)) {
            $subscriptionPlan = null;
        } else {
            $subscriptionPlan = $this->getSubscriptionPlanByButton($tg->getText(), $tg);
        }

        if ($subscriptionPlan === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->querySubscriptionPlan($tg);
        }

        $this->state->setSubscriptionPlan($subscriptionPlan);

        try {
            $this->validator->validate($this->state, groups: 'subscription_plan');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->querySubscriptionPlan($tg);
        }

        if ($this->state->isPaymentMethodStep()) {
            return $this->queryPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($this->getPaymentMethods($tg)[0]);

        return $this->queryPayment($tg, $entity);
    }

    public function getPaymentMethodQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(3);
        $query .= $tg->trans('query.payment_method', domain: 'subscribe');

        if ($help) {
            $query = $tg->view('subscribe_payment_method_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryPaymentMethod(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_PAYMENT_METHOD_QUERIED);

        $message = $this->getPaymentMethodQuery($tg, $help);

        $buttons = $this->getPaymentMethodButtons($tg);
        $buttons[] = $tg->backButton();

        if ($this->state->hasNotSkipHelpButton('payment_method')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotPaymentMethod(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($this->state->isPaymentMethodStep() && $tg->matchText($tg->backButton()->getText())) {
            return $this->queryPaymentMethod($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('payment_method');

            return $this->queryPaymentMethod($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchText(null)) {
            $paymentMethod = null;
        } else {
            $paymentMethod = $this->getPaymentMethodByButton($tg->getText(), $tg);
        }

        if ($paymentMethod === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($paymentMethod);

        try {
            $this->validator->validate($this->state, groups: 'payment_method');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryPaymentMethod($tg);
        }

        return $this->queryPayment($tg, $entity);
    }

    public function getPaymentQuery(TelegramAwareHelper $tg, Money $price): string
    {
        $query = $this->getStep(4);
        $price = $this->moneyFormatter->formatMoneyAsTelegramButton($price);
        $tgLocaleCode = $this->userProvider->getTelegramUserByUpdate($tg->getTelegram()->getUpdate())?->getLanguageCode();
        $parameters = [
            'price' => $price,
        ];
        $payButton = $tg->trans('query.pay_button', $parameters, domain: 'subscribe', locale: $tgLocaleCode);
        $payButton = sprintf('<u>%s</u>', $payButton);
        $parameters = [
            'pay_button' => $payButton,
        ];
        $query .= $tg->trans('query.payment', $parameters, domain: 'subscribe');

        return $query;
    }

    public function getPaymentInvoiceParameters(TelegramAwareHelper $tg): array
    {
        $commandNames = array_map(fn ($command) => $tg->command($command), ['create', 'search', 'lookup']);
        $bots = $this->botRepository->findByGroup($tg->getTelegram()->getBot()->getGroup());
        $botNames = array_map(fn (TelegramBot $bot) => '@' . $bot->getUsername(), $bots);

        return [
            'plan' => $this->planProvider->getSubscriptionPlanName($this->state->getSubscriptionPlan()->getName()),
            'limited_commands' => '"' . join('", "', $commandNames) . '"',
            'bots' => join(', ', $botNames),
        ];
    }

    public function getInvalidCurrencyReply(TelegramAwareHelper $tg, string $currency): string
    {
        $currencyName = sprintf('<u>%s</u>', $currency);
        $parameters = [
            'currency' => $currencyName,
        ];
        $message = $tg->trans('reply.invalid_currency', $parameters, domain: 'subscribe');
        $message = $tg->failText($message);

        return $message;
    }

    public function queryPayment(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_PAYMENT_QUERIED);

        $subscriptionPlan = $this->state->getSubscriptionPlan();
        $price = $this->getPrice($subscriptionPlan, $tg);

        $message = $this->getPaymentQuery($tg, $price);

        $this->chooseActionChatSender->sendActions($tg, text: $message);

        $parameters = $this->getPaymentInvoiceParameters($tg);

        try {
            $this->paymentManager->sendPaymentRequest(
                $tg->getTelegram(),
                $tg->getTelegram()->getMessengerUser(),
                $tg->getChatId(),
                $this->state->getPaymentMethod(),
                $tg->trans('query.payment_invoice_title', $parameters, domain: 'subscribe'),
                $tg->trans('query.payment_invoice_description', $parameters, domain: 'subscribe'),
                $this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText(),
                $subscriptionPlan->getName()->name,
                [],
                $price
            );
        } catch (InvalidCurrencyTelegramException $exception) {
            $this->logger->error($exception);

            $message = $this->getInvalidCurrencyReply($tg, $exception->getCurrency());

            $tg->reply($message);

            return $this->queryCurrency($tg);
        }

        return $tg->stopConversation($entity)->null();
    }

    public function getCurrencyButtons(TelegramAwareHelper $tg): array
    {
        return array_map(
            fn (Currency $currency) => $this->getCurrencyButton($currency, $tg),
            $this->getCurrencies($tg)
        );
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSubscriptionPlanButtons(TelegramAwareHelper $tg): array
    {
        return array_map(
            fn (FeedbackSubscriptionPlan $subscriptionPlan) => $this->getSubscriptionPlanButton($subscriptionPlan, $tg),
            $this->getSubscriptionPlans($tg)
        );
    }

    public function getPrice(FeedbackSubscriptionPlan $subscriptionPlan, TelegramAwareHelper $tg): Money
    {
        $usdPrice = $subscriptionPlan->getPrice($tg->getCountryCode());

        if ($this->state->getCurrency() === null) {
            $currencyCode = $tg->getTelegram()->getMessengerUser()?->getUser()->getCurrencyCode();
            $currency = $this->currencyProvider->getCurrency($currencyCode);
        } else {
            $currency = $this->state->getCurrency();
        }

        return new Money(ceil($usdPrice / $currency->getRate()), $currency->getCode());
    }

    public function getCurrencyButton(Currency $currency, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->currencyProvider->getCurrencyComposeName($currency));
    }

    public function getCurrencyByButton(string $button, TelegramAwareHelper $tg): ?Currency
    {
        foreach ($this->getCurrencies($tg) as $currency) {
            if ($this->getCurrencyButton($currency, $tg)->getText() === $button) {
                return $currency;
            }
        }

        return null;
    }

    public function getSubscriptionPlanButton(FeedbackSubscriptionPlan $subscriptionPlan, TelegramAwareHelper $tg): KeyboardButton
    {
        $price = $this->getPrice($subscriptionPlan, $tg);

        $text = $this->planProvider->getSubscriptionPlanName($subscriptionPlan->getName());
        $text .= ' - ';
        $text .= $this->moneyFormatter->formatMoney($price, native: true);

        return $tg->button($text);
    }

    public function getSubscriptionPlanByButton(string $button, TelegramAwareHelper $tg): ?FeedbackSubscriptionPlan
    {
        foreach ($this->getSubscriptionPlans($tg) as $subscriptionPlan) {
            if ($this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText() === $button) {
                return $subscriptionPlan;
            }
        }

        return null;
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getPaymentMethodButtons(TelegramAwareHelper $tg): array
    {
        return array_map(
            fn (TelegramPaymentMethod $paymentMethod) => $this->getPaymentMethodButton($paymentMethod, $tg),
            $this->getPaymentMethods($tg)
        );
    }

    public function getPaymentMethodButton(TelegramPaymentMethod $paymentMethod, TelegramAwareHelper $tg): KeyboardButton
    {
        $text = $tg->trans(sprintf('payment_method.%s', $paymentMethod->getName()->name), domain: 'tg.payment_method');

        return $tg->button($text);
    }

    public function getPaymentMethodByButton(string $button, TelegramAwareHelper $tg): ?TelegramPaymentMethod
    {
        foreach ($this->getPaymentMethods($tg) as $paymentMethod) {
            if ($this->getPaymentMethodButton($paymentMethod, $tg)->getText() === $button) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function getChangeCurrencyButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('💱 ' . $tg->trans('keyboard.change_currency', domain: 'subscribe'));
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return TelegramPaymentMethod[]
     */
    public function getPaymentMethods(TelegramAwareHelper $tg): array
    {
        return $this->paymentMethodRepository->findActiveByBot($tg->getTelegram()->getBot());
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return Currency[]
     */
    public function getCurrencies(TelegramAwareHelper $tg): array
    {
        $currencyCodes = [];
        foreach ($this->getPaymentMethods($tg) as $paymentMethod) {
            $currencyCodes = array_merge($currencyCodes, $paymentMethod->getCurrencyCodes());
        }

        return $this->currencyProvider->getCurrencies(currencyCodes: $currencyCodes);
    }

    public function getSubscriptionPlans(TelegramAwareHelper $tg): array
    {
        return $this->subscriptionPlansProvider->getSubscriptionPlans(country: $tg->getCountryCode());
    }

    public function getStep(int $num): string
    {
        $total = 4;

        if (!$this->state->isCurrencyStep()) {
            if ($num > 1) {
                $num--;
            }
            $total--;
        }

        if (!$this->state->isPaymentMethodStep()) {
            if ($num > 2) {
                $num--;
            }
            $total--;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }
}