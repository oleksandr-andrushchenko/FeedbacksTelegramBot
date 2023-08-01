<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230801171138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_feedback_messages (id INT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, user_id INT UNSIGNED DEFAULT NULL, text VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6C530ECF9E4CEF7D (messenger_user_id), INDEX IDX_6C530ECFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_feedback_messages ADD CONSTRAINT FK_6C530ECF9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE user_feedback_messages ADD CONSTRAINT FK_6C530ECFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT true NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_feedback_messages DROP FOREIGN KEY FK_6C530ECF9E4CEF7D');
        $this->addSql('ALTER TABLE user_feedback_messages DROP FOREIGN KEY FK_6C530ECFA76ED395');
        $this->addSql('DROP TABLE user_feedback_messages');
        $this->addSql('ALTER TABLE messenger_users CHANGE is_show_hints is_show_hints TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
