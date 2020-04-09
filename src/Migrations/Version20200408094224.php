<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200408094224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change families and locales product filter rules configuration attributes to array';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE akeneo_api_product_filters_rules
            CHANGE completenessType completenessType VARCHAR(255) NOT NULL, 
            CHANGE locales locales LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
            CHANGE completenessValue completenessValue VARCHAR(255) NOT NULL, 
            CHANGE updatedBefore updatedBefore DATETIME NOT NULL, 
            CHANGE updatedAfter updatedAfter DATETIME NOT NULL, 
            CHANGE families families LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE akeneo_api_product_filters_rules 
            CHANGE completenessType completenessType VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, 
            CHANGE locales locales LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', 
            CHANGE completenessValue completenessValue VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, 
            CHANGE updatedBefore updatedBefore DATETIME DEFAULT NULL,
            CHANGE updatedAfter updatedAfter DATETIME DEFAULT NULL, 
            CHANGE families families LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\'
        ');
    }
}
