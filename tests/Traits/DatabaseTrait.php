<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use Fidry\AliceDataFixtures\LoaderInterface;
use Throwable;

trait DatabaseTrait
{
    use EntityManagerProviderTrait;

    protected static bool $databaseBooted = false;

    protected function databaseUp(): void
    {
        $this->bootDatabase()
            ->rollBackIfNeed()
            ->beginTransaction()
        ;
    }

    protected function databaseDown(): void
    {
        $this->rollBackIfNeed();
    }

    protected function beginTransaction(): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $tables = $conn->executeQuery('SHOW TABLES')->fetchFirstColumn();

        foreach ($tables as $table) {
            $conn->executeQuery(sprintf('ALTER TABLE %s AUTO_INCREMENT = 1', $table));
        }

        $conn->beginTransaction();
    }

    protected function rollBackIfNeed(): static
    {
        $conn = $this->getEntityManager()->getConnection();

        try {
            while ($conn->isTransactionActive()) {
                try {
                    $conn->rollBack();
                } catch (ConnectionException) {
                }
            }
        } catch (Throwable $exception) {
            if (!$this->isUnknownDatabaseException($exception)) {
                throw $exception;
            }
        }

        return $this;
    }

    protected function isUnknownDatabaseException(Throwable $exception): bool
    {
        return str_contains($exception->getMessage(), 'Unknown database');
    }

    protected function bootDatabase(): static
    {
        if (static::$databaseBooted) {
            return $this;
        }

        try {
            $this->updateDatabase();
        } catch (Throwable $exception) {
            if (!$this->isUnknownDatabaseException($exception)) {
                throw $exception;
            }

            $this->createDatabase()
                ->updateDatabase()
            ;
        }

        static::$databaseBooted = true;

        return $this;
    }

    /**
     * @return $this
     * @throws \Doctrine\DBAL\Exception
     * @see CreateDatabaseDoctrineCommand
     */
    protected function createDatabase(): static
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $params = $conn->getParams();

        if (isset($params['primary'])) {
            $params = $params['primary'];
        }

        $hasPath = isset($params['path']);
        $name = $hasPath ? $params['path'] : ($params['dbname'] ?? false);
        unset($params['dbname'], $params['path'], $params['url']);
        $tmpConnection = DriverManager::getConnection($params);

        $schemaManager = method_exists($tmpConnection, 'createSchemaManager')
            ? $tmpConnection->createSchemaManager()
            : $tmpConnection->getSchemaManager();
        if (!$hasPath) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        $schemaManager->createDatabase($name);

        return $this;
    }

    protected function updateDatabase(): static
    {
        $em = $this->getEntityManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $sch = new SchemaTool($em);
        $sch->updateSchema($metadata);

        return $this;
    }

    /**
     * @param array $fixtures
     * @return mixed
     * @see https://github.com/theofidry/AliceDataFixtures/blob/master/doc/advanced-usage.md#usage-in-tests
     */
    protected function bootFixtures(array $fixtures): self
    {
        /** @var LoaderInterface $fix */
        $fix = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');

        $fix->load(
            array_map(
                fn ($entityClass) => sprintf(__DIR__ . '/../../fixtures/%s.yaml', str_replace(['App\Entity\\', '\\'], ['', '/'], $entityClass)),
                $fixtures
            )
        );

        return $this;
    }
}