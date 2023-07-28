<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Feedback\FeedbackCreatorOptions;
use App\Entity\Feedback\FeedbackSearchCreatorOptions;
use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Money;
use App\Entity\Telegram\GetFeedbackPremiumTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Enum\Telegram\TelegramView;
use App\Exception\ValidatorException;
use App\Service\Feedback\FeedbackSubscriptionPlanProvider;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\CurrencyProvider;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\FeedbackSubscriptionsTelegramChatSender;
use App\Service\Telegram\Payment\TelegramPaymentManager;
use App\Service\Telegram\Payment\TelegramPaymentMethodProvider;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property GetFeedbackPremiumTelegramConversationState $state
 */
class GetFeedbackPremiumTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_SUBSCRIPTION_PLAN_ASKED = 10;
    public const STEP_PAYMENT_METHOD_ASKED = 20;
    public const STEP_PAYMENT_ASKED = 30;
    public const STEP_CANCEL_PRESSED = 40;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly Validator $validator,
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlansProvider,
        private readonly TelegramPaymentMethodProvider $paymentMethodProvider,
        private readonly CountryProvider $countryProvider,
        private readonly CurrencyProvider $currencyProvider,
        private readonly TelegramPaymentManager $paymentManager,
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly FeedbackSubscriptionsTelegramChatSender $subscriptionsChatSender,
        private readonly FeedbackCreatorOptions $creatorOptions,
        private readonly FeedbackSearchCreatorOptions $searchCreatorOptions,
    )
    {
        parent::__construct($awareHelper, new GetFeedbackPremiumTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            if ($this->userSubscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
                $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

                return $tg->stopConversation($conversation)->startConversation(ChooseFeedbackActionTelegramConversation::class)->null();
            }

            $this->describe($tg);

            return $this->askSubscriptionPlan($tg);
        }

        if ($tg->matchText(null) && $this->state->getStep() !== self::STEP_PAYMENT_ASKED) {
            return $tg->replyWrong()->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            if ($this->state->getStep() === self::STEP_PAYMENT_METHOD_ASKED) {
                return $this->askSubscriptionPlan($tg);
            }
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            return $tg->stopConversation($conversation)
                ->replyUpset('feedbacks.reply.premium.canceled')
                ->startConversation(ChooseFeedbackActionTelegramConversation::class)
                ->null()
            ;
        }

        if ($this->state->getStep() === self::STEP_SUBSCRIPTION_PLAN_ASKED) {
            return $this->onSubscriptionPlanAnswer($tg);
        }

        if ($this->state->getStep() === self::STEP_PAYMENT_METHOD_ASKED) {
            return $this->onPaymentMethodAnswer($tg, $conversation);
        }

        if ($this->state->getStep() === self::STEP_PAYMENT_ASKED) {
            return $this->onPaymentAnswer($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        $tg->replyView(TelegramView::PREMIUM, [
            'commands' => [
                'create' => [
                    'command' => FeedbackTelegramChannel::CREATE_FEEDBACK,
                    'limits' => [
                        'day' => $this->creatorOptions->userPerDayLimit(),
                        'month' => $this->creatorOptions->userPerMonthLimit(),
                        'year' => $this->creatorOptions->userPerYearLimit(),
                    ],
                ],
                'search' => [
                    'command' => FeedbackTelegramChannel::SEARCH_FEEDBACK,
                    'limits' => [
                        'day' => $this->searchCreatorOptions->userPerDayLimit(),
                        'month' => $this->searchCreatorOptions->userPerMonthLimit(),
                        'year' => $this->searchCreatorOptions->userPerYearLimit(),
                    ],
                ],
            ],
        ]);
    }

    public function askSubscriptionPlan(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SUBSCRIPTION_PLAN_ASKED);

        return $tg->reply(
            $this->getStep(1) . $this->getSubscriptionPlanAsk($tg),
            $tg->keyboard(...[
                ...$this->getSubscriptionPlanButtons($tg),
                $this->getCancelButton($tg),
            ])
        )->null();
    }

    public function onSubscriptionPlanAnswer(TelegramAwareHelper $tg): null
    {
        $subscriptionPlan = $this->getSubscriptionPlanByButton($tg->getText(), $tg);

        if ($subscriptionPlan === null) {
            $tg->replyWrong();

            return $this->askSubscriptionPlan($tg);
        }

        $this->state->setSubscriptionPlan($subscriptionPlan);
        try {
            $this->validator->validate($this->state, groups: 'subscription_plan');
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askSubscriptionPlan($tg);
        }

        return $this->askPaymentMethod($tg);
    }

    public function askPaymentMethod(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_PAYMENT_METHOD_ASKED);

        return $tg->reply(
            $this->getStep(2) . $this->getPaymentMethodAsk($tg),
            $tg->keyboard(...[
                ...$this->getPaymentMethodButtons($tg),
                $this->getBackButton($tg),
                $this->getCancelButton($tg),
            ])
        )->null();
    }

    public function onPaymentMethodAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $paymentMethod = $this->getPaymentMethodByButton($tg->getText(), $tg);

        if ($paymentMethod === null) {
            $tg->replyWrong();

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
        $this->state->setStep(self::STEP_PAYMENT_ASKED);

        $subscriptionPlan = $this->state->getSubscriptionPlan();

        $transParameters = [
            'plan' => $this->getSubscriptionPlanText($subscriptionPlan, $tg),
        ];

        $tg->reply($this->getStep(3) . $this->getPaymentAsk($tg));

        $this->paymentManager->sendPaymentRequest(
            $tg->getTelegram(),
            $tg->getTelegram()->getMessengerUser(),
            $tg->getChatId(),
            $this->state->getPaymentMethod()->getName(),
            $tg->trans('feedbacks.ask.premium.payment_invoice_title', $transParameters),
            $tg->trans('feedbacks.ask.premium.payment_invoice_description', $transParameters),
            $this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText(),
            $subscriptionPlan->getName()->name,
            [],
            $this->getPrice($subscriptionPlan, $tg)
        );

        return $tg->stopConversation($conversation)->startConversation(ChooseFeedbackActionTelegramConversation::class)->null();
    }

    public function onPaymentAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $preCheckoutQuery = $tg->getTelegram()->getUpdate()->getPreCheckoutQuery();

        if ($preCheckoutQuery === null) {
            $tg->replyWrong();

            return $this->askPayment($tg, $conversation);
        }

        // todo: compare currency & amount & payload & store order info
        return null;
    }

    public static function getSubscriptionPlanAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('feedbacks.ask.premium.subscription_plan');
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSubscriptionPlanButtons(TelegramAwareHelper $tg): array
    {
        return array_map(
            fn (FeedbackSubscriptionPlan $subscriptionPlan) => $this->getSubscriptionPlanButton($subscriptionPlan, $tg),
            $this->subscriptionPlansProvider->getSubscriptionPlans(countryCode: $tg->getCountryCode())
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

        return $tg->button('feedbacks.keyboard.premium.subscription_plan', [
            'plan' => $this->getSubscriptionPlanText($subscriptionPlan, $tg),
            'price' => sprintf('%d,00', $price->getAmount()),
            'currency' => $price->getCurrency(),
        ]);
    }

    public function getSubscriptionPlanText(FeedbackSubscriptionPlan $subscriptionPlan, TelegramAwareHelper $tg): string
    {
        return $tg->trans(sprintf('feedbacks.subscription_plan.%s', $subscriptionPlan->getName()->name));
    }

    public function getSubscriptionPlanByButton(string $button, TelegramAwareHelper $tg): ?FeedbackSubscriptionPlan
    {
        foreach ($this->subscriptionPlansProvider->getSubscriptionPlans(countryCode: $tg->getCountryCode()) as $subscriptionPlan) {
            if (static::getSubscriptionPlanButton($subscriptionPlan, $tg)->getText() === $button) {
                return $subscriptionPlan;
            }
        }

        return null;
    }

    public static function getPaymentMethodAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('feedbacks.ask.premium.payment_method');
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getPaymentMethodButtons(TelegramAwareHelper $tg): array
    {
        return array_map(
            fn (TelegramPaymentMethod $paymentMethod) => static::getPaymentMethodButton($paymentMethod, $tg),
            $this->paymentMethodProvider->getPaymentMethods(countryCode: $tg->getCountryCode())
        );
    }

    public static function getPaymentMethodButton(TelegramPaymentMethod $paymentMethod, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('payment_method.%s', $paymentMethod->getName()->name));
    }

    public function getPaymentMethodByButton(string $button, TelegramAwareHelper $tg): ?TelegramPaymentMethod
    {
        foreach ($this->paymentMethodProvider->getPaymentMethods(countryCode: $tg->getCountryCode()) as $paymentMethod) {
            if (static::getPaymentMethodButton($paymentMethod, $tg)->getText() === $button) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public static function getPaymentAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('feedbacks.ask.premium.payment');
    }

    public static function getBackButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('keyboard.back');
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('keyboard.cancel');
    }

    private function getStep(int|string $num): string
    {
        return "[{$num}/3] ";
    }
}