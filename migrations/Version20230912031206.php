<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230912031206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches ADD telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_5CD71DC0A0E2F38 ON feedback_search_searches (telegram_bot_id)');
        $this->addSql('ALTER TABLE feedback_searches ADD telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_2F56936BA0E2F38 ON feedback_searches (telegram_bot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0A0E2F38');
        $this->addSql('DROP INDEX IDX_5CD71DC0A0E2F38 ON feedback_search_searches');
        $this->addSql('ALTER TABLE feedback_search_searches DROP telegram_bot_id');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BA0E2F38');
        $this->addSql('DROP INDEX IDX_2F56936BA0E2F38 ON feedback_searches');
        $this->addSql('ALTER TABLE feedback_searches DROP telegram_bot_id');
    }
}
