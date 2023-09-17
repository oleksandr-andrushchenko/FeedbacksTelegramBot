<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230917030116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE telegram_stopped_conversations (id BIGINT UNSIGNED NOT NULL, messenger_user_id BIGINT NOT NULL, chat_id BIGINT NOT NULL, bot_id SMALLINT NOT NULL, class VARCHAR(255) NOT NULL, state JSON NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE telegram_conversations DROP FOREIGN KEY FK_7B5D79B89E4CEF7D');
        $this->addSql('ALTER TABLE telegram_conversations DROP FOREIGN KEY FK_7B5D79B892C1C487');
        $this->addSql('DROP INDEX IDX_7B5D79B89E4CEF7D ON telegram_conversations');
        $this->addSql('DROP INDEX IDX_7B5D79B892C1C487 ON telegram_conversations');
        $this->addSql('ALTER TABLE telegram_conversations DROP active, CHANGE id id VARCHAR(32) NOT NULL, CHANGE messenger_user_id messenger_user_id BIGINT NOT NULL, CHANGE bot_id bot_id SMALLINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE telegram_stopped_conversations');
        $this->addSql('ALTER TABLE telegram_conversations ADD active TINYINT(1) NOT NULL, CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE messenger_user_id messenger_user_id INT UNSIGNED DEFAULT NULL, CHANGE bot_id bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_conversations ADD CONSTRAINT FK_7B5D79B89E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE telegram_conversations ADD CONSTRAINT FK_7B5D79B892C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7B5D79B89E4CEF7D ON telegram_conversations (messenger_user_id)');
        $this->addSql('CREATE INDEX IDX_7B5D79B892C1C487 ON telegram_conversations (bot_id)');
    }
}
