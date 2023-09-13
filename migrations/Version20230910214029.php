<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230910214029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedbacks ADD telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F89A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_7E6C3F89A0E2F38 ON feedbacks (telegram_bot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F89A0E2F38');
        $this->addSql('DROP INDEX IDX_7E6C3F89A0E2F38 ON feedbacks');
        $this->addSql('ALTER TABLE feedbacks DROP telegram_bot_id');
    }
}
