<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231008032647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE token token VARCHAR(50) NOT NULL');
        $this->addSql('RENAME TABLE address_localities TO addresses');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE addresses TO address_localities');
        $this->addSql('ALTER TABLE telegram_bots CHANGE token token VARCHAR(46) NOT NULL');
    }
}
