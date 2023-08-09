<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230809055737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT true NOT NULL, CHANGE is_show_extended_keyboard is_show_extended_keyboard TINYINT(1) DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE telegram_bots CHANGE is_check_updates is_check_updates TINYINT(1) DEFAULT true NOT NULL, CHANGE is_check_requests is_check_requests TINYINT(1) DEFAULT true NOT NULL, CHANGE is_accept_payments is_accept_payments TINYINT(1) DEFAULT false NOT NULL, CHANGE is_admin_only is_admin_only TINYINT(1) DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE telegram_conversations ADD bot_id SMALLINT UNSIGNED DEFAULT NULL, DROP bot');
        $this->addSql('ALTER TABLE telegram_conversations ADD CONSTRAINT FK_7B5D79B892C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_7B5D79B892C1C487 ON telegram_conversations (bot_id)');
        $this->addSql('ALTER TABLE telegram_payments ADD bot_id SMALLINT UNSIGNED DEFAULT NULL, DROP bot');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A92C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_31578A7A92C1C487 ON telegram_payments (bot_id)');
        $this->addSql('ALTER TABLE telegram_requests ADD bot_id SMALLINT UNSIGNED DEFAULT NULL, DROP bot');
        $this->addSql('ALTER TABLE telegram_requests ADD CONSTRAINT FK_2F00C71992C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_2F00C71992C1C487 ON telegram_requests (bot_id)');
        $this->addSql('ALTER TABLE telegram_updates ADD bot_id SMALLINT UNSIGNED DEFAULT NULL, DROP bot');
        $this->addSql('ALTER TABLE telegram_updates ADD CONSTRAINT FK_1E0E72C692C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_1E0E72C692C1C487 ON telegram_updates (bot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_show_extended_keyboard is_show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE telegram_bots CHANGE is_check_updates is_check_updates TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_check_requests is_check_requests TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_accept_payments is_accept_payments TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_admin_only is_admin_only TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE telegram_conversations DROP FOREIGN KEY FK_7B5D79B892C1C487');
        $this->addSql('DROP INDEX IDX_7B5D79B892C1C487 ON telegram_conversations');
        $this->addSql('ALTER TABLE telegram_conversations ADD bot VARCHAR(32) NOT NULL, DROP bot_id');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A92C1C487');
        $this->addSql('DROP INDEX IDX_31578A7A92C1C487 ON telegram_payments');
        $this->addSql('ALTER TABLE telegram_payments ADD bot VARCHAR(32) NOT NULL, DROP bot_id');
        $this->addSql('ALTER TABLE telegram_requests DROP FOREIGN KEY FK_2F00C71992C1C487');
        $this->addSql('DROP INDEX IDX_2F00C71992C1C487 ON telegram_requests');
        $this->addSql('ALTER TABLE telegram_requests ADD bot VARCHAR(32) NOT NULL, DROP bot_id');
        $this->addSql('ALTER TABLE telegram_updates DROP FOREIGN KEY FK_1E0E72C692C1C487');
        $this->addSql('DROP INDEX IDX_1E0E72C692C1C487 ON telegram_updates');
        $this->addSql('ALTER TABLE telegram_updates ADD bot VARCHAR(32) NOT NULL, DROP bot_id');
    }
}
