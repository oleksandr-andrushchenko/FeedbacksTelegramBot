<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram\Bot;

use App\Entity\Feedback\Telegram\Bot\SubscribeTelegramBotConversationState;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Service\Feedback\Telegram\Bot\Conversation\SubscribeTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Functional\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Feedback\FeedbackSearchSearchRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSubscriptionPlanProviderTrait;
use App\Tests\Traits\Intl\CurrencyProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotInvoiceSenderProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotPaymentRepositoryProviderTrait;
use Generator;

class SubscribeTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use FeedbackSearchSearchRepositoryProviderTrait;
    use CurrencyProviderTrait;
    use FeedbackSubscriptionPlanProviderTrait;
    use TelegramBotPaymentRepositoryProviderTrait;
    use TelegramBotInvoiceSenderProviderTrait;

    /**
     * @param string $command
     * @param TelegramBotPaymentMethodName[] $paymentMethodNames
     * @param SubscribeTelegramBotConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(
        string $command,
        array $paymentMethodNames,
        SubscribeTelegramBotConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->getBot()->getEntity()->setAcceptPayments(true);
        array_map(fn (TelegramBotPaymentMethodName $name) => $this->createPaymentMethod($name), $paymentMethodNames);

        $this
            ->type($command)
            ->shouldSeeActiveConversation(SubscribeTelegramBotConversation::class, $expectedState)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        $commands = [
            'button & one payment method' => [
                $this->command('subscribe'),
                [TelegramBotPaymentMethodName::portmone],
                false,
            ],
            'button & two payment methods' => [
                $this->command('subscribe'),
                [TelegramBotPaymentMethodName::portmone, TelegramBotPaymentMethodName::liqpay],
                true,
            ],
            'command & one payment method' => [
                FeedbackTelegramBotGroup::SUBSCRIBE,
                [TelegramBotPaymentMethodName::portmone],
                false,
            ],
            'command & two payment methods' => [
                FeedbackTelegramBotGroup::SUBSCRIBE,
                [TelegramBotPaymentMethodName::portmone, TelegramBotPaymentMethodName::liqpay],
                true,
            ],
        ];

        foreach ($commands as $key => [$command, $paymentMethodNames, $expectedPaymentMethodStep]) {
            yield $key => [
                'command' => $command,
                'paymentMethodNames' => $paymentMethodNames,
                'expectedState' => (new SubscribeTelegramBotConversationState())
                    ->setCurrencyStep(false)
                    ->setPaymentMethodStep($expectedPaymentMethodStep)
                    ->setStep(SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
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
     * @param SubscribeTelegramBotConversationState $state
     * @param SubscribeTelegramBotConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider gotCurrencySuccessDataProvider
     */
    public function testGotCurrencySuccess(
        string $command,
        SubscribeTelegramBotConversationState $state,
        SubscribeTelegramBotConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramBotPaymentMethod::class,
        ]);

        $conversation = $this->createConversation(SubscribeTelegramBotConversation::class, $state);

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
                'state' => $state = (new SubscribeTelegramBotConversationState())
                    ->setCurrencyStep($currencyStep)
                    ->setPaymentMethodStep($paymentMethodStep)
                    ->setStep(SubscribeTelegramBotConversation::STEP_CURRENCY_QUERIED),
                'expectedState' => (clone $state)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setStep(SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
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
     * @param SubscribeTelegramBotConversationState $state
     * @param SubscribeTelegramBotConversationState $expectedState
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider gotSubscriptionPlanWithPaymentMethodStepSuccessDataProvider
     */
    public function testGotSubscriptionPlanWithPaymentMethodStepSuccess(
        string $command,
        SubscribeTelegramBotConversationState $state,
        SubscribeTelegramBotConversationState $expectedState,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramBotPaymentMethod::class,
        ]);

        $conversation = $this->createConversation(SubscribeTelegramBotConversation::class, $state);

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
                'state' => $state = (new SubscribeTelegramBotConversationState())
                    ->setCurrencyStep($currencyStep)
                    ->setPaymentMethodStep(true)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setStep(SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED),
                'expectedState' => (clone $state)
                    ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
                    ->setStep(SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED),
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
     * @param SubscribeTelegramBotConversationState $state
     * @param SubscribeTelegramBotConversationState $expectedState
     * @return void
     * @dataProvider gotPaymentMethodSuccessDataProvider
     */
    public function testGotPaymentMethodSuccess(
        string $command,
        SubscribeTelegramBotConversationState $state,
        SubscribeTelegramBotConversationState $expectedState
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramBotPaymentMethod::class,
        ]);

        $conversation = $this->createConversation(SubscribeTelegramBotConversation::class, $state);

        $paymentRepository = $this->getTelegramBotPaymentRepository();
        $previousPaymentCount = $paymentRepository->count([]);

        $this
            ->type($command)
            ->shouldNotSeeActiveConversation($conversation->getClass(), $expectedState)
            ->shouldSeeChooseAction('query.payment')
        ;

        $this->assertEquals($previousPaymentCount + 1, $paymentRepository->count([]));
        $this->assertCount(1, $this->getTelegramBotInvoiceSender()->getCalls());
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
                'state' => $state = (new SubscribeTelegramBotConversationState())
                    ->setCurrencyStep($currencyStep)
                    ->setPaymentMethodStep(true)
                    ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                    ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
                    ->setStep(SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED),
                'expectedState' => (clone $state)
                    ->setStep(SubscribeTelegramBotConversation::STEP_PAYMENT_QUERIED),
            ];
        }
    }
}
