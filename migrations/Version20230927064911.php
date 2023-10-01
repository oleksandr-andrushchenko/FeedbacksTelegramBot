<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230927064911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE region_1 region_1 VARCHAR(255) DEFAULT NULL, CHANGE region_2 region_2 VARCHAR(255) DEFAULT NULL, CHANGE locality locality VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE region_1 region_1 VARCHAR(255) NOT NULL, CHANGE region_2 region_2 VARCHAR(255) NOT NULL, CHANGE locality locality VARCHAR(255) NOT NULL');
    }
}
