<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Traits\Telegram\TelegramUpdateHandlerMockProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateFixtureProviderTrait;
use App\Tests\Traits\WebClientProviderTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Exception;

class TelegramControllerTest extends KernelTestCase
{
    use WebClientProviderTrait;
    use TelegramUpdateHandlerMockProviderTrait;
    use TelegramUpdateFixtureProviderTrait;

    public function testWebhookSuccess(): void
    {
        $client = $this->getWebClient();
        
        $this->getTelegramUpdateHandlerMock();

        $client->jsonRequest('POST', '/telegram/webhook/any_bot', $this->getTelegramUpdateFixture()->jsonSerialize());

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('ok', $response->getContent());
    }

    public function testWebhookFailure(): void
    {
        $client = $this->getWebClient();

        $this->getTelegramUpdateHandlerMock()
            ->method('handleTelegramUpdate')
            ->willThrowException(new Exception())
        ;

        $client->jsonRequest('POST', '/telegram/webhook/any_bot', $this->getTelegramUpdateFixture()->jsonSerialize());

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('failed', $response->getContent());
    }
}
