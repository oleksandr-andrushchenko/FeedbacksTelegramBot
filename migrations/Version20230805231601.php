<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230805231601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches ADD locale_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedbacks ADD locale_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT true NOT NULL, CHANGE is_show_extended_keyboard is_show_extended_keyboard TINYINT(1) DEFAULT false NOT NULL, CHANGE language_code locale_code VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE language_code locale_code VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedbacks DROP locale_code');
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_show_extended_keyboard is_show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL, CHANGE locale_code language_code VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE locale_code language_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches DROP locale_code');
    }
}
