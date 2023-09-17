<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230917045902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_conversations DROP INDEX IDX_7B5D79B8D1B862B8, ADD UNIQUE INDEX UNIQ_7B5D79B8D1B862B8 (hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_conversations DROP INDEX UNIQ_7B5D79B8D1B862B8, ADD INDEX IDX_7B5D79B8D1B862B8 (hash)');
    }
}
