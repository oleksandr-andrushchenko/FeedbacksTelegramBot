<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Tests\Traits\Intl\CountryProviderTrait;
use Generator;

class StartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use CountryProviderTrait;

    /**
     * @param bool $showHints
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(bool $showHints): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        if ($showHints) {
            $shouldReply = [
                'describe.title',
                'describe.agreements',
            ];
        } else {
            $shouldReply = [];
        }

        $this
            ->type(FeedbackTelegramChannel::START)
        ;

        $this->assertNotNull($this->getUpdateMessengerUser());
        $user = $this->getUpdateMessengerUser()->getUser();
        $this->assertNotNull($user);

        $bot = $this->getTelegram()->getBot();
        $botCountry = $this->getCountryProvider()->getCountry($bot->getCountryCode());

        $this->assertEquals($botCountry->getCode(), $user->getCountryCode());
        $this->assertEquals($botCountry->getCurrencyCode(), $user->getCurrencyCode());
        $this->assertEquals($botCountry->getLocaleCodes()[0] ?? null, $user->getLocaleCode());
        $this->assertEquals($botCountry->getTimezones()[0] ?? null, $user->getTimezone());

        $this->getUpdateMessengerUser()->setIsShowHints($showHints);
        $this
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(...$shouldReply)
            ->shouldSeeChooseAction()
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'no hints' => [
            'showHints' => false,
        ];

        yield 'hints' => [
            'showHints' => true,
        ];
    }
}