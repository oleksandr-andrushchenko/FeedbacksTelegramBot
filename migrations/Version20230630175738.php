<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230630175738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_searches (id INT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_text VARCHAR(255) NOT NULL, search_term_normalized_text VARCHAR(255) NOT NULL, search_term_type SMALLINT UNSIGNED NOT NULL, search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, search_term_messenger_username VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2F56936B9E4CEF7D (messenger_user_id), INDEX IDX_2F56936B2333E4FC (search_term_messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedbacks (id INT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_text VARCHAR(255) NOT NULL, search_term_normalized_text VARCHAR(255) NOT NULL, search_term_type SMALLINT UNSIGNED NOT NULL, search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, search_term_messenger_username VARCHAR(255) DEFAULT NULL, rating SMALLINT NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7E6C3F899E4CEF7D (messenger_user_id), INDEX IDX_7E6C3F892333E4FC (search_term_messenger_user_id), INDEX IDX_7E6C3F899D568F55 (search_term_normalized_text), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, messenger SMALLINT UNSIGNED NOT NULL, identifier VARCHAR(255) NOT NULL, username VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, language_code VARCHAR(5) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2F5DF3DDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_conversations (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, chat_id BIGINT NOT NULL, class VARCHAR(255) NOT NULL, status VARCHAR(255) DEFAULT \'active\' NOT NULL, state JSON DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7B5D79B89E4CEF7D (messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_updates (id BIGINT UNSIGNED NOT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, language_code VARCHAR(5) DEFAULT NULL, rating SMALLINT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B2333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F899E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F892333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE messenger_users ADD CONSTRAINT FK_2F5DF3DDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE telegram_conversations ADD CONSTRAINT FK_7B5D79B89E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B2333E4FC');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F899E4CEF7D');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F892333E4FC');
        $this->addSql('ALTER TABLE messenger_users DROP FOREIGN KEY FK_2F5DF3DDA76ED395');
        $this->addSql('ALTER TABLE telegram_conversations DROP FOREIGN KEY FK_7B5D79B89E4CEF7D');
        $this->addSql('DROP TABLE feedback_searches');
        $this->addSql('DROP TABLE feedbacks');
        $this->addSql('DROP TABLE messenger_users');
        $this->addSql('DROP TABLE telegram_conversations');
        $this->addSql('DROP TABLE telegram_updates');
        $this->addSql('DROP TABLE users');
    }
}
