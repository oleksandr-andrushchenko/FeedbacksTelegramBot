<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231006215720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE telegram_channels (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, username VARCHAR(32) NOT NULL, tg_group SMALLINT UNSIGNED NOT NULL, name VARCHAR(1024) NOT NULL, country_code VARCHAR(2) NOT NULL, locale_code VARCHAR(2) NOT NULL, region_1 VARCHAR(255) DEFAULT NULL, region_2 VARCHAR(255) DEFAULT NULL, locality VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_A791F3FEF85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE telegram_bots ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP channel_username, DROP group_username, DROP single_channel, DROP region_1, DROP region_2, DROP locality, DROP timezone, CHANGE bot_group_name tg_group SMALLINT UNSIGNED NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE telegram_channels');
        $this->addSql('ALTER TABLE telegram_bots ADD channel_username VARCHAR(32) DEFAULT NULL, ADD group_username VARCHAR(32) DEFAULT NULL, ADD single_channel TINYINT(1) DEFAULT 1 NOT NULL, ADD region_1 VARCHAR(255) DEFAULT NULL, ADD region_2 VARCHAR(255) DEFAULT NULL, ADD locality VARCHAR(255) DEFAULT NULL, ADD timezone VARCHAR(32) DEFAULT NULL, DROP updated_at, CHANGE tg_group bot_group_name SMALLINT UNSIGNED NOT NULL');
    }
}
