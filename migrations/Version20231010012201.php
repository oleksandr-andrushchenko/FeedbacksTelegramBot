<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010012201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots ADD descriptions_synced TINYINT(1) DEFAULT 0 NOT NULL, ADD webhook_synced TINYINT(1) DEFAULT 0 NOT NULL, ADD commands_synced TINYINT(1) DEFAULT 0 NOT NULL, DROP texts_set, DROP webhook_set, DROP commands_set');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots ADD texts_set TINYINT(1) DEFAULT 0 NOT NULL, ADD webhook_set TINYINT(1) DEFAULT 0 NOT NULL, ADD commands_set TINYINT(1) DEFAULT 0 NOT NULL, DROP descriptions_synced, DROP webhook_synced, DROP commands_synced');
    }
}
