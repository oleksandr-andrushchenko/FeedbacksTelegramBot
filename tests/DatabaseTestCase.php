<?php

declare(strict_types=1);

namespace App\Tests;

use App\Tests\Traits\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    use DatabaseTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseUp();
    }

    public function tearDown(): void
    {
        $this->databaseDown();

        parent::tearDown();
    }
}