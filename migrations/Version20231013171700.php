<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231013171700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B2333E4FC');
        $this->addSql('DROP INDEX IDX_2F56936B2333E4FC ON feedback_searches');
        $this->addSql('ALTER TABLE feedback_searches DROP search_term_text, DROP search_term_normalized_text, DROP search_term_type, DROP search_term_messenger, DROP search_term_messenger_username, CHANGE search_term_messenger_user_id term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BE2C35FC FOREIGN KEY (term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('CREATE INDEX IDX_2F56936BE2C35FC ON feedback_searches (term_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BE2C35FC');
        $this->addSql('DROP INDEX IDX_2F56936BE2C35FC ON feedback_searches');
        $this->addSql('ALTER TABLE feedback_searches ADD search_term_text VARCHAR(255) NOT NULL, ADD search_term_normalized_text VARCHAR(255) NOT NULL, ADD search_term_type SMALLINT UNSIGNED NOT NULL, ADD search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, ADD search_term_messenger_username VARCHAR(255) DEFAULT NULL, CHANGE term_id search_term_messenger_user_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B2333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2F56936B2333E4FC ON feedback_searches (search_term_messenger_user_id)');
    }
}
