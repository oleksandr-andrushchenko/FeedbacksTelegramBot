<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Telegram\TelegramBot;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\Telegram\TelegramUpdateHandlerMockProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateFixtureProviderTrait;
use App\Tests\Traits\WebClientProviderTrait;
use Exception;

class TelegramControllerTest extends DatabaseTestCase
{
    use WebClientProviderTrait;
    use TelegramUpdateHandlerMockProviderTrait;
    use TelegramUpdateFixtureProviderTrait;

    public function testWebhookSuccess(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $client = $this->getWebClient();

        $this->getTelegramUpdateHandlerMock();

        $client->jsonRequest('POST', sprintf('/telegram/webhook/%s', Fixtures::BOT_USERNAME_1), $this->getTelegramUpdateFixture()->jsonSerialize());

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('ok', $response->getContent());
    }

    public function testWebhookFailure(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $client = $this->getWebClient();

        $this->getTelegramUpdateHandlerMock()
            ->method('handleTelegramUpdate')
            ->willThrowException(new Exception())
        ;

        $client->jsonRequest('POST', sprintf('/telegram/webhook/%s', Fixtures::BOT_USERNAME_1), $this->getTelegramUpdateFixture()->jsonSerialize());

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('failed', $response->getContent());
    }
}
