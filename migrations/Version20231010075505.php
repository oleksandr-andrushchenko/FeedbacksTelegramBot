<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010075505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `telegram_bots` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_0900_as_cs`');
        $this->addSql('ALTER TABLE `telegram_channels` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_0900_as_cs`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `telegram_bots` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `telegram_channels` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`');
    }
}
