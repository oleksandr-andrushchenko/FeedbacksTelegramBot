<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Feedback\Telegram\SubscribeTelegramConversationState;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Enum\Telegram\TelegramPaymentMethodName;
use App\Service\Feedback\Telegram\Conversation\SubscribeTelegramConversation;
use App\Service\Feedback\Telegram\FeedbackTelegramChannel;
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
     * @param TelegramPaymentMethodName[] $paymentMethodNames
     * @param SubscribeTelegramConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(
        string $command,
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
        array_map(fn (TelegramPaymentMethodName $name) => $this->createPaymentMethod($name), $paymentMethodNames);

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
            'button & one payment method' => [
                $this->command('subscribe'),
                [TelegramPaymentMethodName::portmone],
                false,
            ],
            'button & two payment methods' => [
                $this->command('subscribe'),
                [TelegramPaymentMethodName::portmone, TelegramPaymentMethodName::liqpay],
                true,
            ],
            'command & one payment method' => [
                FeedbackTelegramChannel::SUBSCRIBE,
                [TelegramPaymentMethodName::portmone],
                false,
            ],
            'command & two payment methods' => [
                FeedbackTelegramChannel::SUBSCRIBE,
                [TelegramPaymentMethodName::portmone, TelegramPaymentMethodName::liqpay],
                true,
            ],
        ];

        foreach ($commands as $key => [$command, $paymentMethodNames, $expectedPaymentMethodStep]) {
            yield $key => [
                'command' => $command,
                'paymentMethodNames' => $paymentMethodNames,
                'expectedState' => (new SubscribeTelegramConversationState())
                    ->setCurrencyStep(false)
                    ->setPaymentMethodStep($expectedPaymentMethodStep)
                    ->setStep(SubscribeTelegramConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
                'shouldSeeReply' => [
                    'query.subscription_plan',
                ],
                'shouldSeeButtons' => [
                    ...array_map(
                        fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                        FeedbackSubscriptionPlanName::cases()
                    ),
                    'keyboard.change_currency',
                    $this->helpButton(),
                    $this->cancelButton(),
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
                $this->backButton(),
            ],
            'currency step & no payment method step' => [
                true,
                false,
                $this->backButton(),
            ],
        ];

        foreach ($commands as $key => [$currencyStep, $paymentMethodStep, $shouldSeeButton]) {
            yield $key => [
                'command' => '🇺🇸 USD',
                'state' => $state = (new SubscribeTelegramConversationState())
                    ->setCurrencyStep($currencyStep)
                    ->setPaymentMethodStep($paymentMethodStep)
                    ->setStep(SubscribeTelegramConversation::STEP_CURRENCY_QUERIED),
                'expectedState' => (clone $state)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setStep(SubscribeTelegramConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
                'shouldSeeReply' => [
                    'query.subscription_plan',
                ],
                'shouldSeeButtons' => [
                    ...array_map(
                        fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                        FeedbackSubscriptionPlanName::cases()
                    ),
                    $shouldSeeButton,
                    $this->helpButton(),
                    $this->cancelButton(),
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
                'command' => 'one_year - $20,00',
                'state' => $state = (new SubscribeTelegramConversationState())
                    ->setCurrencyStep($currencyStep)
                    ->setPaymentMethodStep(true)
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
                    $this->backButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
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
            ->shouldNotSeeActiveConversation($conversation->getClass(), $expectedState)
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
                    ->setCurrencyStep($currencyStep)
                    ->setPaymentMethodStep(true)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
                    ->setStep(SubscribeTelegramConversation::STEP_PAYMENT_METHOD_QUERIED),
                'expectedState' => (clone $state)
                    ->setStep(SubscribeTelegramConversation::STEP_PAYMENT_QUERIED),
            ];
        }
    }
}
