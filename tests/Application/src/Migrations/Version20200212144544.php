<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200212144544 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_categories (id INT AUTO_INCREMENT NOT NULL, categories_configuration INT DEFAULT NULL, activeNewCategories TINYINT(1) NOT NULL, notImportCategories LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', mainCategory VARCHAR(255) NOT NULL, rootCategory VARCHAR(255) NOT NULL, emptyLocalReplaceBy VARCHAR(255) NOT NULL, INDEX IDX_21B48A99518815EC (categories_configuration), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_attribute_mapping (id INT AUTO_INCREMENT NOT NULL, sylius LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', akeneo LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', translate TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE akeneo_api_configuration_categories ADD CONSTRAINT FK_21B48A99518815EC FOREIGN KEY (categories_configuration) REFERENCES akeneo_api_configuration_attribute_mapping (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE akeneo_api_configuration_categories DROP FOREIGN KEY FK_21B48A99518815EC');
        $this->addSql('DROP TABLE akeneo_api_configuration_categories');
        $this->addSql('DROP TABLE akeneo_api_configuration_attribute_mapping');
    }
}
