<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230912083813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_extended_keyboard show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE telegram_conversations CHANGE is_active active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE show_extended_keyboard is_show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE telegram_conversations CHANGE active is_active TINYINT(1) NOT NULL');
    }
}
