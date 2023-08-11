<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230811015410 extends AbstractMigration
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_show_extended_keyboard is_show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE telegram_bots CHANGE is_check_updates is_check_updates TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_check_requests is_check_requests TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_accept_payments is_accept_payments TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_admin_only is_admin_only TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
