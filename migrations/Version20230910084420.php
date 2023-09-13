<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230910084420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots DROP FOREIGN KEY FK_DACD6ED6A367C37');
        $this->addSql('DROP INDEX IDX_DACD6ED6A367C37 ON telegram_bots');
        $this->addSql('ALTER TABLE telegram_bots ADD locale_code VARCHAR(2) NOT NULL, DROP primary_bot_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots ADD primary_bot_id SMALLINT UNSIGNED DEFAULT NULL, DROP locale_code');
        $this->addSql('ALTER TABLE telegram_bots ADD CONSTRAINT FK_DACD6ED6A367C37 FOREIGN KEY (primary_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_DACD6ED6A367C37 ON telegram_bots (primary_bot_id)');
    }
}
