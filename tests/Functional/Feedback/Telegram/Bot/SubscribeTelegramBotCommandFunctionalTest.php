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
     * @param array $paymentMethodNames
     * @param string $command
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(
        array $paymentMethodNames,
        string $command,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        array_map(
            fn (TelegramBotPaymentMethodName $name): TelegramBotPaymentMethod => $this->createPaymentMethod($name),
            $paymentMethodNames
        );

        $this
            ->type($command)
            ->shouldSeeStateStep(
                $this->getConversation(),
                SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED
            )
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button & one payment method' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
            ],
            'command' => $this->command('subscribe'),
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

        yield 'button & two payment methods' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
                TelegramBotPaymentMethodName::liqpay,
            ],
            'command' => $this->command('subscribe'),
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

        yield 'command & one payment method' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
            ],
            'command' => FeedbackTelegramBotGroup::SUBSCRIBE,
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

        yield 'command & two payment methods' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
                TelegramBotPaymentMethodName::liqpay,
            ],
            'command' => FeedbackTelegramBotGroup::SUBSCRIBE,
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

    /**
     * @param bool $currencyStep
     * @param bool $paymentMethodStep
     * @param string $command
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider currencyStepSuccessDataProvider
     */
    public function testCurrencyStepSuccess(
        bool $currencyStep,
        bool $paymentMethodStep,
        string $command,
        array $shouldSeeReply,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramBotPaymentMethod::class,
        ]);

        $state = (new SubscribeTelegramBotConversationState())
            ->setCurrencyStep($currencyStep)
            ->setPaymentMethodStep($paymentMethodStep)
            ->setStep(SubscribeTelegramBotConversation::STEP_CURRENCY_QUERIED)
        ;

        $conversation = $this->createConversation(SubscribeTelegramBotConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function currencyStepSuccessDataProvider(): Generator
    {
        yield 'select currency & no currency step & no payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => false,
            'command' => '🇺🇸 USD',
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
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];

        yield 'select currency & no currency step & payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => true,
            'command' => '🇺🇸 USD',
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
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];

        yield 'select currency & currency step & payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => true,
            'command' => '🇺🇸 USD',
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];

        yield 'select currency & currency step & no payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => false,
            'command' => '🇺🇸 USD',
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];
    }

    /**
     * @param bool $currencyStep
     * @param bool $paymentMethodStep
     * @param string $command
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider subscriptionPlanStepSuccessDataProvider
     */
    public function testSubscriptionPlanStepSuccess(
        bool $currencyStep,
        bool $paymentMethodStep,
        string $command,
        array $shouldSeeReply,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramBotPaymentMethod::class,
        ]);

        $state = (new SubscribeTelegramBotConversationState())
            ->setCurrencyStep($currencyStep)
            ->setPaymentMethodStep($paymentMethodStep)
            ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
            ->setStep(SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED)
        ;

        $conversation = $this->createConversation(SubscribeTelegramBotConversation::class, $state);

        $this
            ->type($command)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function subscriptionPlanStepSuccessDataProvider(): Generator
    {
        yield 'select plan & no currency step & payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => true,
            'command' => 'one_year - $20,00',
            'shouldSeeReply' => [
                'query.payment_method',
            ],
            'shouldSeeButtons' => [
                'payment_method.portmone',
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED,
        ];

        yield 'select plan & currency step & payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => true,
            'command' => 'one_year - $20,00',
            'shouldSeeReply' => [
                'query.payment_method',
            ],
            'shouldSeeButtons' => [
                'payment_method.portmone',
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED,
        ];
    }


    /**
     * @param bool $currencyStep
     * @param bool $paymentMethodStep
     * @param string $command
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider paymentMethodStepSuccessDataProvider
     */
    public function testPaymentMethodStepSuccess(
        bool $currencyStep,
        bool $paymentMethodStep,
        string $command,
        array $shouldSeeReply,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
            TelegramBotPaymentMethod::class,
        ]);

        $state = (new SubscribeTelegramBotConversationState())
            ->setCurrencyStep($currencyStep)
            ->setPaymentMethodStep(true)
            ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
            ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
            ->setStep(SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED)
        ;

        $conversation = $this->createConversation(SubscribeTelegramBotConversation::class, $state);

        $paymentRepository = $this->getTelegramBotPaymentRepository();
        $previousPaymentCount = $paymentRepository->count([]);

        $this
            ->type($command)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;

        $this->assertEquals($previousPaymentCount + 1, $paymentRepository->count([]));
        $this->assertCount(1, $this->getTelegramBotInvoiceSender()->getCalls());
    }

    public function paymentMethodStepSuccessDataProvider(): Generator
    {
        yield 'select method & no currency step & payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => true,
            'command' => 'payment_method.portmone',
            'shouldSeeReply' => [
                'query.payment',
            ],
            'shouldSeeButtons' => [
                $this->command('create'),
                $this->command('search'),
                $this->command('lookup'),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_QUERIED,
        ];

        yield 'select method & currency step & payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => true,
            'command' => 'payment_method.portmone',
            'shouldSeeReply' => [
                'query.payment',
            ],
            'shouldSeeButtons' => [
                $this->command('create'),
                $this->command('search'),
                $this->command('lookup'),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_QUERIED,
        ];
    }
}
