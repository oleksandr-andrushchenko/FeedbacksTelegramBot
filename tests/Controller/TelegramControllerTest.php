<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Site\SitePage;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\Telegram\TelegramBotRepositoryProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateHandlerMockProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateFixtureProviderTrait;
use App\Tests\Traits\WebClientProviderTrait;
use Exception;
use Generator;

class TelegramControllerTest extends DatabaseTestCase
{
    use WebClientProviderTrait;
    use TelegramUpdateHandlerMockProviderTrait;
    use TelegramUpdateFixtureProviderTrait;
    use TelegramBotRepositoryProviderTrait;

    /**
     * @param SitePage $page
     * @return void
     * @dataProvider pageSuccessDataProvider
     */
    public function testPageSuccess(SitePage $page): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);

        $client = $this->getWebClient();

        $client->jsonRequest('GET', sprintf('/telegram/%s/%s', $bot->getUsername(), $page->value));

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertStringContainsString($bot->getUsername(), $response->getContent());
    }

    public function pageSuccessDataProvider(): Generator
    {
        yield 'index' => [
            'page' => SitePage::INDEX,
        ];

        yield 'privacy policy' => [
            'page' => SitePage::PRIVACY_POLICY,
        ];

        yield 'terms of use' => [
            'page' => SitePage::TERMS_OF_USE,
        ];

        yield 'contacts' => [
            'page' => SitePage::CONTACTS,
        ];
    }

    public function testWebhookSuccess(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $client = $this->getWebClient();

        $this->getTelegramUpdateHandlerMock();

        $client->jsonRequest(
            'POST',
            sprintf('/telegram/%s/webhook', Fixtures::BOT_USERNAME_1),
            $this->getTelegramUpdateFixture()->jsonSerialize()
        );

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

        $client->jsonRequest(
            'POST',
            sprintf('/telegram/%s/webhook', Fixtures::BOT_USERNAME_1),
            $this->getTelegramUpdateFixture()->jsonSerialize()
        );

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('failed', $response->getContent());
    }
}
