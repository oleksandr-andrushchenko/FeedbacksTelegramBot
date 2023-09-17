<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230913051550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_feedback_messages ADD telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user_feedback_messages ADD CONSTRAINT FK_6C530ECFA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_6C530ECFA0E2F38 ON user_feedback_messages (telegram_bot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_feedback_messages DROP FOREIGN KEY FK_6C530ECFA0E2F38');
        $this->addSql('DROP INDEX IDX_6C530ECFA0E2F38 ON user_feedback_messages');
        $this->addSql('ALTER TABLE user_feedback_messages DROP telegram_bot_id');
    }
}
