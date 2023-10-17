<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231013122153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_search_terms (id INT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, text VARCHAR(255) NOT NULL, normalized_text VARCHAR(255) NOT NULL, type SMALLINT UNSIGNED NOT NULL, messenger SMALLINT UNSIGNED DEFAULT NULL, messenger_username VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_201518849E4CEF7D (messenger_user_id), INDEX IDX_20151884B334D8E9 (normalized_text), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedbacks_search_terms (feedback_id INT UNSIGNED NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, INDEX IDX_15C0A96FD249A887 (feedback_id), INDEX IDX_15C0A96FC2ED3DD8 (feedback_search_term_id), PRIMARY KEY(feedback_id, feedback_search_term_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_search_terms ADD CONSTRAINT FK_201518849E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks_search_terms ADD CONSTRAINT FK_15C0A96FD249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedbacks_search_terms ADD CONSTRAINT FK_15C0A96FC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F892333E4FC');
        $this->addSql('DROP INDEX IDX_7E6C3F892333E4FC ON feedbacks');
        $this->addSql('DROP INDEX IDX_7E6C3F899D568F55 ON feedbacks');
        $this->addSql('ALTER TABLE feedbacks DROP search_term_messenger_user_id, DROP search_term_text, DROP search_term_normalized_text, DROP search_term_type, DROP search_term_messenger, DROP search_term_messenger_username');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_terms DROP FOREIGN KEY FK_201518849E4CEF7D');
        $this->addSql('ALTER TABLE feedbacks_search_terms DROP FOREIGN KEY FK_15C0A96FD249A887');
        $this->addSql('ALTER TABLE feedbacks_search_terms DROP FOREIGN KEY FK_15C0A96FC2ED3DD8');
        $this->addSql('DROP TABLE feedback_search_terms');
        $this->addSql('DROP TABLE feedbacks_search_terms');
        $this->addSql('ALTER TABLE feedbacks ADD search_term_messenger_user_id INT UNSIGNED DEFAULT NULL, ADD search_term_text VARCHAR(255) NOT NULL, ADD search_term_normalized_text VARCHAR(255) NOT NULL, ADD search_term_type SMALLINT UNSIGNED NOT NULL, ADD search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, ADD search_term_messenger_username VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F892333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7E6C3F892333E4FC ON feedbacks (search_term_messenger_user_id)');
        $this->addSql('CREATE INDEX IDX_7E6C3F899D568F55 ON feedbacks (search_term_normalized_text)');
    }
}
