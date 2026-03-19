<?php

declare(strict_types = 1);

namespace App\Tests\functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestBase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();

        $this->entityManager->close();

        parent::tearDown();
    }

    protected function generateValidToken(string $email): string
    {
        $apiKey = $_ENV['FE_AUTH_TOKEN'] ?? 'test-api-key';
        $expiration = time() + 86_400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    protected function generateExpiredToken(string $email): string
    {
        $apiKey = $_ENV['FE_AUTH_TOKEN'] ?? 'test-api-key';
        $expiration = time() - 86_400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    private function setUpDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $connection = $this->entityManager->getConnection();

        $schemaTool->dropSchema($metadata);

        $sqls = $schemaTool->getCreateSchemaSql($metadata);

        foreach ($sqls as $sql) {
            $cleanedSql = $this->cleanSqlForSQLite($sql);
            $connection->executeStatement($cleanedSql);
        }
    }

    private function cleanSqlForSQLite(string $sql): string
    {
        $sql = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);

        $sql = preg_replace('/columnDefinition:\s*\'[^\']*ON\s+UPDATE[^\']*\'/i', '', $sql);

        return $sql;
    }

    private function tearDownDatabase(): void
    {
        $connection = $this->entityManager->getConnection();

        $tables = $connection->createSchemaManager()->listTableNames();

        $connection->executeStatement('PRAGMA foreign_keys = OFF');

        foreach ($tables as $table) {
            $connection->executeStatement(sprintf('DELETE FROM %s', $table));
        }

        $connection->executeStatement('PRAGMA foreign_keys = ON');
    }
}
