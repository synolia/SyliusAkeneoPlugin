<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200214084805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_products_configuration (id INT AUTO_INCREMENT NOT NULL, websiteAttribute VARCHAR(255) DEFAULT NULL, attributeMapping LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', importMediaFiles TINYINT(1) DEFAULT NULL, akeneoImageAttributes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', productImagesMapping LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', importAssetFiles TINYINT(1) DEFAULT NULL, akeneoAssetAttributes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', regenerateUrlRewrites TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_products_configuration_attributes (id INT AUTO_INCREMENT NOT NULL, attribute VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, productsConfiguration_id INT NOT NULL, INDEX IDX_8089029951FB68F2 (productsConfiguration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_products_configuration_default_tax (id INT AUTO_INCREMENT NOT NULL, website VARCHAR(255) NOT NULL, taxClass VARCHAR(255) NOT NULL, productsConfiguration_id INT NOT NULL, INDEX IDX_7981811B51FB68F2 (productsConfiguration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE akeneo_api_configuration_products_configuration_attributes ADD CONSTRAINT FK_8089029951FB68F2 FOREIGN KEY (productsConfiguration_id) REFERENCES akeneo_api_configuration_products_configuration (id)');
        $this->addSql('ALTER TABLE akeneo_api_configuration_products_configuration_default_tax ADD CONSTRAINT FK_7981811B51FB68F2 FOREIGN KEY (productsConfiguration_id) REFERENCES akeneo_api_configuration_products_configuration (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE akeneo_api_configuration_products_configuration_attributes DROP FOREIGN KEY FK_8089029951FB68F2');
        $this->addSql('ALTER TABLE akeneo_api_configuration_products_configuration_default_tax DROP FOREIGN KEY FK_7981811B51FB68F2');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_configuration');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_configuration_attributes');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_configuration_default_tax');
    }
}
