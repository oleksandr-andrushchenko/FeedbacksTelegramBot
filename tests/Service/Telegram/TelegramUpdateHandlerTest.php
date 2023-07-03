<?php
declare(strict_types=1);

namespace App\Tests\Service\Telegram;

use App\Entity\Telegram\TelegramUpdate;
use App\Enum\Telegram\TelegramName;
use App\Tests\DatabaseTestCase;
use App\Tests\Traits\AssertLoggedTrait;
use App\Tests\Traits\Telegram\TelegramUpdateHandlerTrait;
use App\Tests\Traits\Telegram\TelegramUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\TelegramUpdateRepositoryProviderTrait;
use Monolog\Level;

class TelegramUpdateHandlerTest extends DatabaseTestCase
{
    use TelegramUpdateFixtureProviderTrait;
    use TelegramUpdateRepositoryProviderTrait;
    use AssertLoggedTrait;
    use TelegramUpdateHandlerTrait;

    public function testHandleTelegramUpdateStoreSuccess(): void
    {
        $updateId = 10;

        $this->handleTelegramUpdate(TelegramName::default, $this->getTelegramMessageUpdateFixture('any', updateId: $updateId));

        $this->assertLogged(Level::Info, 'Telegram update received');

        $updateRepository = $this->getTelegramUpdateRepository();

        $this->assertEquals(1, $updateRepository->count([]));
        $this->assertNotNull($updateRepository->find($updateId));
    }

    public function testHandleTelegramUpdateDuplicateSuccess(): void
    {
        $this->bootFixtures([TelegramUpdate::class]);

        $updateRepository = $this->getTelegramUpdateRepository();
        $previousUpdateCount = $updateRepository->count([]);

        $this->handleTelegramUpdate(TelegramName::default, $this->getTelegramMessageUpdateFixture('any', updateId: 1));

        $this->assertLogged(Level::Info, 'Telegram update received');
        $this->assertLogged(Level::Debug, 'Duplicate telegram update received, processing aborted');

        $this->assertEquals($previousUpdateCount, $updateRepository->count([]));
    }
}
