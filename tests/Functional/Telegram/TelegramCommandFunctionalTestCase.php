<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram;

use App\Tests\DatabaseTestCase;
use App\Tests\Traits\Telegram\TelegramCommandFunctionalTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class TelegramCommandFunctionalTestCase extends DatabaseTestCase
{
    use TelegramCommandFunctionalTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->telegramCommandUp();
    }

    public static function getContainer(): ContainerInterface
    {
        return parent::getContainer();
    }
}