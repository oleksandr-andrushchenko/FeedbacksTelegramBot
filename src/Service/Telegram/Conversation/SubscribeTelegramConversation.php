<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Intl\Currency;
use App\Entity\Money;
use App\Entity\Telegram\SubscribeTelegramConversationState;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Exception\Telegram\Api\InvalidCurrencyTelegramException;
use App\Exception\ValidatorException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Repository\Telegram\TelegramPaymentMethodRepository;
use App\Service\Feedback\FeedbackSubscriptionPlanProvider;
use App\Service\Intl\CurrencyProvider;
use App\Service\MoneyFormatter;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\SubscribeDescribeTelegramChatSender;
use App\Service\Telegram\Payment\TelegramPaymentManager;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;

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
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlansProvider,
        private readonly TelegramPaymentMethodRepository $paymentMethodRepository,
        private readonly TelegramPaymentManager $paymentManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SubscribeDescribeTelegramChatSender $subscribeDescribeChatSender,
        private readonly CurrencyProvider $currencyProvider,
        private readonly MoneyFormatter $moneyFormatter,
        private readonly TelegramBotRepository $botRepository,
    )
    {
        parent::__construct($awareHelper, new SubscribeTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            $this->state->setIsCurrencyStep($tg->getTelegram()->getMessengerUser()?->getUser()->getCurrencyCode() === null);
            $this->state->setIsPaymentMethodStep(count($this->getPaymentMethods($tg)) > 1);

            if ($this->state->isCurrencyStep()) {
                return $this->queryCurrency($tg);
            }

            return $this->querySubscriptionPlan($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_SUBSCRIPTION_PLAN_QUERIED) {
                return $this->queryCurrency($tg);
            }
            if ($this->state->getStep() === self::STEP_PAYMENT_METHOD_QUERIED) {
                return $this->querySubscriptionPlan($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.canceled', domain: 'tg.subscribe'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_CURRENCY_QUERIED) {
            return $this->gotCurrency($tg);
        }

        if ($this->state->getStep() === self::STEP_SUBSCRIPTION_PLAN_QUERIED) {
            return $this->gotSubscriptionPlan($tg, $conversation);
        }

        if ($this->state->getStep() === self::STEP_PAYMENT_METHOD_QUERIED) {
            return $this->gotPaymentMethod($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $this->subscribeDescribeChatSender->sendSubscribeDescribe($tg);
    }

    public function queryCurrency(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CURRENCY_QUERIED);

        return $tg->reply(
            $this->getStep(1) . $this->getCurrencyQuery($tg),
            $tg->keyboard(...[
                ...$this->getCurrencyButtons($tg),
                $this->getCancelButton($tg),
            ])
        )->null();
    }

    public function gotCurrency(TelegramAwareHelper $tg): null
    {
        $currency = $this->getCurrencyByButton($tg->getText(), $tg);

        if ($currency === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

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

    public function querySubscriptionPlan(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SUBSCRIPTION_PLAN_QUERIED);

        $keyboards = $this->getSubscriptionPlanButtons($tg);

        if ($this->state->isCurrencyStep()) {
            $keyboards[] = $this->getBackButton($tg);
        } else {
            $keyboards[] = $this->getChangeCurrencyButton($tg);
        }

        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getStep(2) . $this->getSubscriptionPlanQuery($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function gotSubscriptionPlan(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getChangeCurrencyButton($tg)->getText())) {
            $this->state->setIsCurrencyStep(true);

            return $this->queryCurrency($tg);
        }

        $subscriptionPlan = $this->getSubscriptionPlanByButton($tg->getText(), $tg);

        if ($subscriptionPlan === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

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

        return $this->queryPayment($tg, $conversation);
    }

    public function queryPaymentMethod(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_PAYMENT_METHOD_QUERIED);

        return $tg->reply(
            $this->getStep(3) . $this->getPaymentMethodQuery($tg),
            $tg->keyboard(...[
                ...$this->getPaymentMethodButtons($tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        )->null();
    }

    public function gotPaymentMethod(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $paymentMethod = $this->getPaymentMethodByButton($tg->getText(), $tg);

        if ($paymentMethod === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($paymentMethod);
        try {
            $this->validator->validate($this->state, groups: 'payment_method');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryPaymentMethod($tg);
        }

        return $this->queryPayment($tg, $conversation);
    }

    public function queryPayment(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $this->state->setStep(self::STEP_PAYMENT_QUERIED);

        $subscriptionPlan = $this->state->getSubscriptionPlan();

        $bots = $this->botRepository->findByGroup($tg->getTelegram()->getBot()->getGroup());
        $transParameters = [
            'plan' => $this->getSubscriptionPlanText($subscriptionPlan, $tg),
            'limited_commands' => '"' . join('", "', array_map(fn ($command) => $tg->command($command), ['create', 'search'])) . '"',
            'hidden_commands' => '"' . join('", "', array_map(fn ($command) => $tg->command($command), ['lookup'])) . '"',
            'bots' => join(', ', array_map(fn (TelegramBot $bot) => '@' . $bot->getUsername(), $bots)),
        ];

        $tg->reply($this->getStep(4) . $this->getPaymentQuery($tg));

        try {
            $this->paymentManager->sendPaymentRequest(
                $tg->getTelegram(),
                $tg->getTelegram()->getMessengerUser(),
                $tg->getChatId(),
                $this->state->getPaymentMethod(),
                $tg->trans('query.payment_invoice_title', $transParameters, domain: 'tg.subscribe'),
                $tg->trans('query.payment_invoice_description', $transParameters, domain: 'tg.subscribe'),
                $this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText(),
                $subscriptionPlan->getName()->name,
                [],
                $this->getPrice($subscriptionPlan, $tg)
            );
        } catch (InvalidCurrencyTelegramException $exception) {
            $tg->replyFail($tg->trans('reply.invalid_currency', ['currency' => $exception->getCurrency()], domain: 'tg.subscribe'));

            return $this->queryCurrency($tg);
        }

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public static function getCurrencyQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.currency', domain: 'tg.subscribe');
    }

    public function getCurrencyButtons(TelegramAwareHelper $tg): array
    {
        return array_map(
            fn (Currency $currency) => $this->getCurrencyButton($currency, $tg),
            $this->getCurrencies($tg)
        );
    }

    public static function getSubscriptionPlanQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.subscription_plan', domain: 'tg.subscribe');
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
        return $tg->button($this->currencyProvider->getComposeCurrencyName($currency));
    }

    public function getCurrencyByButton(string $button, TelegramAwareHelper $tg): ?Currency
    {
        foreach ($this->getCurrencies($tg) as $currency) {
            if (static::getCurrencyButton($currency, $tg)->getText() === $button) {
                return $currency;
            }
        }

        return null;
    }

    public function getSubscriptionPlanButton(FeedbackSubscriptionPlan $subscriptionPlan, TelegramAwareHelper $tg): KeyboardButton
    {
        $price = $this->getPrice($subscriptionPlan, $tg);

        return $tg->button(
            sprintf('%s - %s',
                $this->getSubscriptionPlanText($subscriptionPlan, $tg),
                $this->moneyFormatter->formatMoney($price, true)
            )
        );
    }

    public function getSubscriptionPlanText(FeedbackSubscriptionPlan $subscriptionPlan, TelegramAwareHelper $tg): string
    {
        return $tg->trans(sprintf('subscription_plan.%s', $subscriptionPlan->getName()->name));
    }

    public function getSubscriptionPlanByButton(string $button, TelegramAwareHelper $tg): ?FeedbackSubscriptionPlan
    {
        foreach ($this->getSubscriptionPlans($tg) as $subscriptionPlan) {
            if (static::getSubscriptionPlanButton($subscriptionPlan, $tg)->getText() === $button) {
                return $subscriptionPlan;
            }
        }

        return null;
    }

    public static function getPaymentMethodQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.payment_method', domain: 'tg.subscribe');
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
        return $tg->button($tg->trans(sprintf('payment_method.%s', $paymentMethod->getName()->name)));
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

    public static function getPaymentQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.payment', domain: 'tg.subscribe');
    }

    public static function getBackButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.back'));
    }

    public function getChangeCurrencyButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.change_currency', domain: 'tg.subscribe'));
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return TelegramPaymentMethod[]
     */
    public function getPaymentMethods(TelegramAwareHelper $tg): array
    {
        return $this->paymentMethodRepository->findByBot($tg->getTelegram()->getBot());
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

    private function getStep(int $num): string
    {
        $total = 4;

        if (!$this->state->isCurrencyStep()) {
            if ($num > 1) {
                $num--;
            }
            --$total;
        }

        if (!$this->state->isPaymentMethodStep()) {
            if ($num > 2) {
                $num--;
            }
            --$total;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }
}