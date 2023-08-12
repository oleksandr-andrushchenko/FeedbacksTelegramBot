<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Money;
use App\Entity\Telegram\SubscribeTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Exception\ValidatorException;
use App\Service\Feedback\FeedbackSubscriptionPlanProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\CurrencyProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\SubscribeDescribeTelegramChatSender;
use App\Service\Telegram\Payment\TelegramPaymentManager;
use App\Service\Telegram\Payment\TelegramPaymentMethodProvider;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property SubscribeTelegramConversationState $state
 */
class SubscribeTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_SUBSCRIPTION_PLAN_QUERIED = 10;
    public const STEP_PAYMENT_METHOD_QUERIED = 20;
    public const STEP_PAYMENT_QUERIED = 30;
    public const STEP_CANCEL_PRESSED = 40;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlansProvider,
        private readonly TelegramPaymentMethodProvider $paymentMethodProvider,
        private readonly CountryProvider $countryProvider,
        private readonly CurrencyProvider $currencyProvider,
        private readonly TelegramPaymentManager $paymentManager,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SubscribeDescribeTelegramChatSender $subscribeDescribeChatSender,
    )
    {
        parent::__construct($awareHelper, new SubscribeTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->askSubscriptionPlan($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_PAYMENT_METHOD_QUERIED) {
                return $this->askSubscriptionPlan($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.canceled', domain: 'tg.subscribe'));

            return $this->chooseActionChatSender->sendActions($tg);
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

    public function askSubscriptionPlan(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SUBSCRIPTION_PLAN_QUERIED);
        $this->state->setIsPaymentMethodStep(count($this->getPaymentMethods($tg)) !== 1);

        return $tg->reply(
            $this->getStep(1) . $this->getSubscriptionPlanQuery($tg),
            $tg->keyboard(...[
                ...$this->getSubscriptionPlanButtons($tg),
                $this->getCancelButton($tg),
            ])
        )->null();
    }

    public function gotSubscriptionPlan(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $subscriptionPlan = $this->getSubscriptionPlanByButton($tg->getText(), $tg);

        if ($subscriptionPlan === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->askSubscriptionPlan($tg);
        }

        $this->state->setSubscriptionPlan($subscriptionPlan);
        try {
            $this->validator->validate($this->state, groups: 'subscription_plan');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askSubscriptionPlan($tg);
        }

        if ($this->state->isPaymentMethodStep()) {
            return $this->askPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($this->getPaymentMethods($tg)[0]);

        return $this->askPayment($tg, $conversation);
    }

    public function askPaymentMethod(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_PAYMENT_METHOD_QUERIED);

        return $tg->reply(
            $this->getStep(2) . $this->getPaymentMethodQuery($tg),
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

            return $this->askPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($paymentMethod);
        try {
            $this->validator->validate($this->state, groups: 'payment_method');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askPaymentMethod($tg);
        }

        return $this->askPayment($tg, $conversation);
    }

    public function askPayment(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $this->state->setStep(self::STEP_PAYMENT_QUERIED);

        $subscriptionPlan = $this->state->getSubscriptionPlan();

        $transParameters = [
            'plan' => $this->getSubscriptionPlanText($subscriptionPlan, $tg),
        ];

        $tg->reply($this->getStep(3) . $this->getPaymentQuery($tg));

        $this->paymentManager->sendPaymentRequest(
            $tg->getTelegram(),
            $tg->getTelegram()->getMessengerUser(),
            $tg->getChatId(),
            $this->state->getPaymentMethod()->getName(),
            $tg->trans('query.payment_invoice_title', $transParameters, domain: 'tg.subscribe'),
            $tg->trans('query.payment_invoice_description', $transParameters, domain: 'tg.subscribe'),
            $this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText(),
            $subscriptionPlan->getName()->name,
            [],
            $this->getPrice($subscriptionPlan, $tg)
        );

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
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
        $currencyCode = $this->state->getPaymentMethod()?->getCurrency();
        if ($currencyCode === null) {
            $currencyCode = $this->countryProvider->getCountry($tg->getCountryCode())?->getCurrency() ?? $this->currencyProvider->getDefaultCurrency()->getCode();
        }
        $currency = $this->currencyProvider->getCurrency($currencyCode) ?? $this->currencyProvider->getDefaultCurrency();

        return new Money(ceil($usdPrice / $currency->getRate()), $currency->getCode());
    }

    public function getSubscriptionPlanButton(FeedbackSubscriptionPlan $subscriptionPlan, TelegramAwareHelper $tg): KeyboardButton
    {
        $price = $this->getPrice($subscriptionPlan, $tg);

        return $tg->button(
            sprintf('%s [%s %s]',
                $this->getSubscriptionPlanText($subscriptionPlan, $tg),
                $price->getCurrency(),
                sprintf('%d,00', $price->getAmount()),
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
        $country = $this->countryProvider->getCountry($paymentMethod->getFlag());

        return $tg->button(join('', [
            $this->countryProvider->getCountryIcon($country),
            $paymentMethod->isGlobal() ? '🌎' : '',
            $tg->trans(sprintf('payment_method.%s', $paymentMethod->getName()->name)),
        ]));
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

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }

    public function getPaymentMethods(TelegramAwareHelper $tg): array
    {
        return $this->paymentMethodProvider->getPaymentMethods(country: $tg->getCountryCode());
    }

    public function getSubscriptionPlans(TelegramAwareHelper $tg): array
    {
        return $this->subscriptionPlansProvider->getSubscriptionPlans(country: $tg->getCountryCode());
    }

    private function getStep(int|string $num): string
    {
        if ($this->state->isPaymentMethodStep()) {
            $total = 3;
        } else {
            if ($num > 1) {
                $num--;
            }
            $total = 2;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }
}