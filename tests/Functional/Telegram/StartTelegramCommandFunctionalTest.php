<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Service\Feedback\Telegram\FeedbackTelegramChannel;
use App\Tests\Traits\Intl\CountryProviderTrait;

class StartTelegramCommandFunctionalTest extends TelegramCommandFunctionalTestCase
{
    use CountryProviderTrait;

    public function testStartSuccess(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

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
        $this->assertEquals($bot->getLocaleCode(), $user->getLocaleCode());
        $this->assertEquals($botCountry->getTimezones()[0] ?? null, $user->getTimezone());

        $this
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(
                'describe.title',
                'describe.agreements',
            )
            ->shouldSeeChooseAction()
        ;
    }
}