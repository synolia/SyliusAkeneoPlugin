<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200220145237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add products configuration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_products_images_mapping (
          id INT AUTO_INCREMENT NOT NULL, 
          syliusAttribute VARCHAR(255) NOT NULL, 
          akeneoAttribute VARCHAR(255) NOT NULL, 
          productsConfiguration_id INT NOT NULL, 
          INDEX IDX_3AEFA1BB51FB68F2 (productsConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_products_akeneo_image_attributes (
          id INT AUTO_INCREMENT NOT NULL, 
          akeneoAttributes VARCHAR(255) NOT NULL, 
          productsConfiguration_id INT NOT NULL, 
          INDEX IDX_1E48229B51FB68F2 (productsConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_products_default_tax (
          id INT AUTO_INCREMENT NOT NULL, 
          website VARCHAR(255) NOT NULL, 
          taxClass VARCHAR(255) NOT NULL, 
          productsConfiguration_id INT NOT NULL, 
          INDEX IDX_BDC7BFFB51FB68F2 (productsConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_products (
          id INT AUTO_INCREMENT NOT NULL, 
          websiteAttribute VARCHAR(255) DEFAULT NULL, 
          attributeMapping LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', 
          importMediaFiles TINYINT(1) DEFAULT NULL, 
          regenerateUrlRewrites TINYINT(1) DEFAULT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_products_attributes (
          id INT AUTO_INCREMENT NOT NULL, 
          attribute VARCHAR(255) NOT NULL, 
          value VARCHAR(255) NOT NULL, 
          productsConfiguration_id INT NOT NULL, 
          INDEX IDX_A7FD5A6051FB68F2 (productsConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_products_images_mapping 
        ADD 
          CONSTRAINT FK_3AEFA1BB51FB68F2 FOREIGN KEY (productsConfiguration_id) REFERENCES akeneo_api_configuration_products (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_products_akeneo_image_attributes 
        ADD 
          CONSTRAINT FK_1E48229B51FB68F2 FOREIGN KEY (productsConfiguration_id) REFERENCES akeneo_api_configuration_products (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_products_default_tax 
        ADD 
          CONSTRAINT FK_BDC7BFFB51FB68F2 FOREIGN KEY (productsConfiguration_id) REFERENCES akeneo_api_configuration_products (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_products_attributes 
        ADD 
          CONSTRAINT FK_A7FD5A6051FB68F2 FOREIGN KEY (productsConfiguration_id) REFERENCES akeneo_api_configuration_products (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_products_images_mapping 
        DROP 
          FOREIGN KEY FK_3AEFA1BB51FB68F2');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_products_akeneo_image_attributes 
        DROP 
          FOREIGN KEY FK_1E48229B51FB68F2');
        $this->addSql('ALTER TABLE akeneo_api_configuration_products_default_tax DROP FOREIGN KEY FK_BDC7BFFB51FB68F2');
        $this->addSql('ALTER TABLE akeneo_api_configuration_products_attributes DROP FOREIGN KEY FK_A7FD5A6051FB68F2');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_images_mapping');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_akeneo_image_attributes');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_default_tax');
        $this->addSql('DROP TABLE akeneo_api_configuration_products');
        $this->addSql('DROP TABLE akeneo_api_configuration_products_attributes');
    }
}
