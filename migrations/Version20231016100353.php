<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016100353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0E2C35FC');
        $this->addSql('DROP INDEX IDX_5CD71DC0E2C35FC ON feedback_search_searches');
        $this->addSql('ALTER TABLE feedback_search_searches CHANGE term_id search_term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC083FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('CREATE INDEX IDX_5CD71DC083FDDA66 ON feedback_search_searches (search_term_id)');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BE2C35FC');
        $this->addSql('DROP INDEX IDX_2F56936BE2C35FC ON feedback_searches');
        $this->addSql('ALTER TABLE feedback_searches CHANGE term_id search_term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B83FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('CREATE INDEX IDX_2F56936B83FDDA66 ON feedback_searches (search_term_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC083FDDA66');
        $this->addSql('DROP INDEX IDX_5CD71DC083FDDA66 ON feedback_search_searches');
        $this->addSql('ALTER TABLE feedback_search_searches CHANGE search_term_id term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0E2C35FC FOREIGN KEY (term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5CD71DC0E2C35FC ON feedback_search_searches (term_id)');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B83FDDA66');
        $this->addSql('DROP INDEX IDX_2F56936B83FDDA66 ON feedback_searches');
        $this->addSql('ALTER TABLE feedback_searches CHANGE search_term_id term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BE2C35FC FOREIGN KEY (term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2F56936BE2C35FC ON feedback_searches (term_id)');
    }
}
