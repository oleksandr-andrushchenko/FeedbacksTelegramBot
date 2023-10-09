<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231009015139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_6FCA7516E1D6B8E6 ON addresses');
        $this->addSql('ALTER TABLE addresses ADD administrative_area_level_1 VARCHAR(255) NOT NULL, ADD administrative_area_level_2 VARCHAR(255) DEFAULT NULL, ADD administrative_area_level_3 VARCHAR(255) DEFAULT NULL, DROP region_1, DROP region_2, DROP locality');
        $this->addSql('CREATE INDEX IDX_6FCA75167E7ABCA5 ON addresses (administrative_area_level_1)');
        $this->addSql('ALTER TABLE telegram_channels ADD administrative_area_level_1 VARCHAR(255) DEFAULT NULL, ADD administrative_area_level_2 VARCHAR(255) DEFAULT NULL, ADD administrative_area_level_3 VARCHAR(255) DEFAULT NULL, DROP region_1, DROP region_2, DROP locality');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_6FCA75167E7ABCA5 ON addresses');
        $this->addSql('ALTER TABLE addresses ADD region_2 VARCHAR(255) NOT NULL, ADD locality VARCHAR(255) NOT NULL, DROP administrative_area_level_2, DROP administrative_area_level_3, CHANGE administrative_area_level_1 region_1 VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX IDX_6FCA7516E1D6B8E6 ON addresses (locality)');
        $this->addSql('ALTER TABLE telegram_channels ADD region_1 VARCHAR(255) DEFAULT NULL, ADD region_2 VARCHAR(255) DEFAULT NULL, ADD locality VARCHAR(255) DEFAULT NULL, DROP administrative_area_level_1, DROP administrative_area_level_2, DROP administrative_area_level_3');
    }
}
