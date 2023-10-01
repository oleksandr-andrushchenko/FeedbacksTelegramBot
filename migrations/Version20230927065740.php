<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230927065740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_397A08774EA59BA6 ON address_localities');
        $this->addSql('ALTER TABLE address_localities ADD region_1 VARCHAR(255) NOT NULL, ADD region_2 VARCHAR(255) NOT NULL, ADD locality VARCHAR(255) NOT NULL, DROP region_1_short, DROP region_1_long, DROP region_2_short, DROP region_2_long, DROP locality_short, DROP locality_long');
        $this->addSql('CREATE INDEX IDX_397A0877E1D6B8E6 ON address_localities (locality)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_397A0877E1D6B8E6 ON address_localities');
        $this->addSql('ALTER TABLE address_localities ADD region_1_short VARCHAR(255) NOT NULL, ADD region_1_long VARCHAR(255) NOT NULL, ADD region_2_short VARCHAR(255) NOT NULL, ADD region_2_long VARCHAR(255) NOT NULL, ADD locality_short VARCHAR(255) NOT NULL, ADD locality_long VARCHAR(255) NOT NULL, DROP region_1, DROP region_2, DROP locality');
        $this->addSql('CREATE INDEX IDX_397A08774EA59BA6 ON address_localities (locality_short)');
    }
}
