<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\SubscribeTelegramConversationState;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Enum\Telegram\TelegramPaymentMethodName;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\SubscribeTelegramConversation;
use App\Tests\Traits\Feedback\FeedbackSearchSearchRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSubscriptionPlanProviderTrait;
use App\Tests\Traits\Intl\CurrencyProviderTrait;
use App\Tests\Traits\Telegram\TelegramInvoiceSenderProviderTrait;
use App\Tests\Traits\Telegram\TelegramPaymentMethodRepositoryProviderTrait;
use App\Tests\Traits\Telegram\TelegramPaymentRepositoryProviderTrait;
use Generator;

class SubscribeTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use FeedbackSearchSearchRepositoryProviderTrait;
    use CurrencyProviderTrait;
    use FeedbackSubscriptionPlanProviderTrait;
    use TelegramPaymentMethodRepositoryProviderTrait;
    use TelegramPaymentRepositoryProviderTrait;
    use TelegramInvoiceSenderProviderTrait;

    /**
     * @param string $command
     * @param bool $showHints
     * @param TelegramPaymentMethodName[] $paymentMethodNames
     * @param SubscribeTelegramConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(
        string $command,
        bool $showHints,
        array $paymentMethodNames,
        SubscribeTelegramConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->getTelegram()->getBot()->setIsAcceptPayments(true);
        $this->getUpdateMessengerUser()->setIsShowHints($showHints);
        array_map(fn (TelegramPaymentMethodName $name) => $this->createPaymentMethod($name), $paymentMethodNames);

        if ($showHints) {
            $shouldSeeReply = array_merge(
                [
                    'describe.title',
                    'command_limits.tries_for',
                    'command_limits.no_free_tries',
                    'describe.summary',
                    'toggle_hints',
                ],
                $shouldSeeReply
            );
        }

        $this
            ->type($command)
            ->shouldSeeActiveConversation(SubscribeTelegramConversation::class, $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        $commands = [
            'button & no hints & one payment method' => [
                'icon.subscribe command.subscribe',
                false,
                [TelegramPaymentMethodName::portmone],
                false,
            ],
            'button & hints & one payment method' => [
                'icon.subscribe command.subscribe',
                true,
                [TelegramPaymentMethodName::portmone],
                false,
            ],
            'button & no hints & two payment methods' => [
                'icon.subscribe command.subscribe',
                false,
                [TelegramPaymentMethodName::portmone, TelegramPaymentMethodName::liqpay],
                true,
            ],
            'button & hints & two payment methods' => [
                'icon.subscribe command.subscribe',
                true,
                [TelegramPaymentMethodName::portmone, TelegramPaymentMethodName::liqpay],
                true,
            ],
            'command & no hints & one payment method' => [
                FeedbackTelegramChannel::SUBSCRIBE,
                false,
                [TelegramPaymentMethodName::portmone],
                false,
            ],
            'command & hints & one payment method' => [
                FeedbackTelegramChannel::SUBSCRIBE,
                true,
                [TelegramPaymentMethodName::portmone],
                false,
            ],
            'command & no hints & two payment methods' => [
                FeedbackTelegramChannel::SUBSCRIBE,
                false,
                [TelegramPaymentMethodName::portmone, TelegramPaymentMethodName::liqpay],
                true,
            ],
            'command & hints & two payment methods' => [
                FeedbackTelegramChannel::SUBSCRIBE,
                true,
                [TelegramPaymentMethodName::portmone, TelegramPaymentMethodName::liqpay],
                true,
            ],
        ];

        foreach ($commands as $key => [$command, $showHints, $paymentMethodNames, $expectedPaymentMethodStep]) {
            yield $key => [
                'command' => $command,
                'showHints' => $showHints,
                'paymentMethodNames' => $paymentMethodNames,
                'expectedState' => (new SubscribeTelegramConversationState())
                    ->setIsCurrencyStep(false)
                    ->setIsPaymentMethodStep($expectedPaymentMethodStep)
                    ->setStep(SubscribeTelegramConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
                'shouldSeeReply' => [
                    'query.subscription_plan',
                ],
                'shouldSeeButtons' => [
                    ...array_map(
                        fn (FeedbackSubscriptionPlanName $plan) => sprintf('subscription_plan.%s', $plan->name),
                        FeedbackSubscriptionPlanName::cases()
                    ),
                    'keyboard.change_currency',
                    'keyboard.cancel',
                ],
            ];
        }
    }

    /**
     * @param string $command
     * @param SubscribeTelegramConversationState $state
     * @param SubscribeTelegramConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider gotCurrencySuccessDataProvider
     */
    public function testGotCurrencySuccess(
        string $command,
        SubscribeTelegramConversationState $state,
        SubscribeTelegramConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramPaymentMethod::class,
        ]);
        $conversation = $this->createConversation(SubscribeTelegramConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function gotCurrencySuccessDataProvider(): Generator
    {
        $commands = [
            'no currency step & no payment method step' => [
                false,
                false,
                'keyboard.change_currency',
            ],
            'no currency step & payment method step' => [
                false,
                true,
                'keyboard.change_currency',
            ],
            'currency step & payment method step' => [
                true,
                true,
                'keyboard.back',
            ],
            'currency step & no payment method step' => [
                true,
                false,
                'keyboard.back',
            ],
        ];

        foreach ($commands as $key => [$currencyStep, $paymentMethodStep, $shouldSeeButton]) {
            yield $key => [
                'command' => '🇺🇸 USD',
                'state' => $state = (new SubscribeTelegramConversationState())
                    ->setIsCurrencyStep($currencyStep)
                    ->setIsPaymentMethodStep($paymentMethodStep)
                    ->setStep(SubscribeTelegramConversation::STEP_CURRENCY_QUERIED),
                'expectedState' => (clone $state)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setStep(SubscribeTelegramConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
                'shouldSeeReply' => [
                    'query.subscription_plan',
                ],
                'shouldSeeButtons' => [
                    ...array_map(
                        fn (FeedbackSubscriptionPlanName $plan) => sprintf('subscription_plan.%s', $plan->name),
                        FeedbackSubscriptionPlanName::cases()
                    ),
                    $shouldSeeButton,
                    'keyboard.cancel',
                ],
            ];
        }
    }

    /**
     * @param string $command
     * @param SubscribeTelegramConversationState $state
     * @param SubscribeTelegramConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider gotSubscriptionPlanWithPaymentMethodStepSuccessDataProvider
     */
    public function testGotSubscriptionPlanWithPaymentMethodStepSuccess(
        string $command,
        SubscribeTelegramConversationState $state,
        SubscribeTelegramConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramPaymentMethod::class,
        ]);
        $conversation = $this->createConversation(SubscribeTelegramConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function gotSubscriptionPlanWithPaymentMethodStepSuccessDataProvider(): Generator
    {
        $commands = [
            'no currency step' => [
                false,
            ],
            'currency step' => [
                true,
            ],
        ];

        foreach ($commands as $key => [$currencyStep]) {
            yield $key => [
                'command' => 'subscription_plan.one_year - $20,00',
                'state' => $state = (new SubscribeTelegramConversationState())
                    ->setIsCurrencyStep($currencyStep)
                    ->setIsPaymentMethodStep(true)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setStep(SubscribeTelegramConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
                'expectedState' => (clone $state)
                    ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
                    ->setStep(SubscribeTelegramConversation::STEP_PAYMENT_METHOD_QUERIED),
                'shouldSeeReply' => [
                    'query.payment_method',
                ],
                'shouldSeeButtons' => [
                    'payment_method.portmone',
                    'keyboard.back',
                    'keyboard.cancel',
                ],
            ];
        }
    }


    /**
     * @param string $command
     * @param SubscribeTelegramConversationState $state
     * @param SubscribeTelegramConversationState $expectedState
     * @return void
     * @dataProvider gotPaymentMethodSuccessDataProvider
     */
    public function testGotPaymentMethodSuccess(
        string $command,
        SubscribeTelegramConversationState $state,
        SubscribeTelegramConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramPaymentMethod::class,
        ]);
        $conversation = $this->createConversation(SubscribeTelegramConversation::class, $state);

        $paymentRepository = $this->getTelegramPaymentRepository();
        $previousPaymentCount = $paymentRepository->count([]);

        $this
            ->type($command)
            ->shouldSeeNotActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeChooseAction('query.payment')
        ;

        $this->assertEquals($previousPaymentCount + 1, $paymentRepository->count([]));
        $this->assertCount(1, $this->getTelegramInvoiceSender()->getCalls());
    }

    public function gotPaymentMethodSuccessDataProvider(): Generator
    {
        $commands = [
            'no currency step' => [
                false,
            ],
            'currency step' => [
                true,
            ],
        ];

        foreach ($commands as $key => [$currencyStep]) {
            yield $key => [
                'command' => 'payment_method.portmone',
                'state' => $state = (new SubscribeTelegramConversationState())
                    ->setIsCurrencyStep($currencyStep)
                    ->setIsPaymentMethodStep(true)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
                    ->setStep(SubscribeTelegramConversation::STEP_PAYMENT_METHOD_QUERIED),
                'expectedState' => (clone $state)
                    ->setStep(SubscribeTelegramConversation::STEP_PAYMENT_QUERIED),
            ];
        }
    }
}
