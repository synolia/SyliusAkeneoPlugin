<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200323105355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Akeneo plugin migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_product_attribute (
          id INT AUTO_INCREMENT NOT NULL, 
          attribute VARCHAR(255) NOT NULL, 
          value VARCHAR(255) NOT NULL, 
          productConfiguration_id INT NOT NULL, 
          INDEX IDX_5D32E2132B9CB4D4 (productConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_product (
          id INT AUTO_INCREMENT NOT NULL, 
          websiteAttribute VARCHAR(255) DEFAULT NULL, 
          attributeMapping LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', 
          importMediaFiles TINYINT(1) DEFAULT NULL, 
          regenerateUrlRewrites TINYINT(1) DEFAULT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_product_default_tax (
          id INT AUTO_INCREMENT NOT NULL, 
          website VARCHAR(255) NOT NULL, 
          taxClass VARCHAR(255) NOT NULL, 
          productConfiguration_id INT NOT NULL, 
          INDEX IDX_48F06B4A2B9CB4D4 (productConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_website_mapping (
          id INT AUTO_INCREMENT NOT NULL, 
          channel_id INT DEFAULT NULL, 
          akeneoChannel VARCHAR(255) NOT NULL, 
          apiConfiguration_id INT DEFAULT NULL, 
          INDEX IDX_70E428A2352A8960 (apiConfiguration_id), 
          INDEX IDX_70E428A272F5A1AA (channel_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_categories (
          id INT AUTO_INCREMENT NOT NULL, 
          activeNewCategories TINYINT(1) NOT NULL, 
          notImportCategories LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
          mainCategory VARCHAR(255) NOT NULL, 
          rootCategory VARCHAR(255) NOT NULL, 
          emptyLocalReplaceBy VARCHAR(255) NOT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_settings (
          id INT AUTO_INCREMENT NOT NULL, 
          name VARCHAR(255) NOT NULL, 
          value VARCHAR(255) DEFAULT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_product_images_mapping (
          id INT AUTO_INCREMENT NOT NULL, 
          syliusAttribute VARCHAR(255) NOT NULL, 
          akeneoAttribute VARCHAR(255) NOT NULL, 
          productConfiguration_id INT NOT NULL, 
          INDEX IDX_A39A907D2B9CB4D4 (productConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_attribute_type_mapping (
          id INT AUTO_INCREMENT NOT NULL, 
          akeneoAttribute VARCHAR(255) NOT NULL, 
          attributeType VARCHAR(255) NOT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration (
          id INT AUTO_INCREMENT NOT NULL, 
          baseUrl VARCHAR(255) NOT NULL, 
          apiClientId VARCHAR(255) NOT NULL, 
          apiClientSecret VARCHAR(255) NOT NULL, 
          token VARCHAR(255) DEFAULT NULL, 
          refreshToken VARCHAR(255) DEFAULT NULL, 
          paginationSize INT NOT NULL, 
          isEnterprise TINYINT(1) NOT NULL, 
          username VARCHAR(255) NOT NULL, 
          password VARCHAR(255) NOT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_product_filters_rules (
          id INT AUTO_INCREMENT NOT NULL, 
          mode VARCHAR(255) NOT NULL, 
          advancedFilter VARCHAR(255) DEFAULT NULL, 
          completenessType VARCHAR(255) DEFAULT NULL, 
          locales VARCHAR(255) NOT NULL, 
          completenessValue VARCHAR(255) DEFAULT NULL, 
          status VARCHAR(255) DEFAULT NULL, 
          updatedMode VARCHAR(255) DEFAULT NULL, 
          updatedBefore DATETIME DEFAULT NULL, 
          updatedAfter DATETIME DEFAULT NULL, 
          updated VARCHAR(255) DEFAULT NULL, 
          families VARCHAR(255) DEFAULT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_product_group (
          id INT AUTO_INCREMENT NOT NULL, 
          productParent VARCHAR(255) NOT NULL, 
          variationAxes LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
          UNIQUE INDEX UNIQ_52E487761146F2B9 (productParent), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_productgroup_product (
          productgroup_id INT NOT NULL, 
          product_id INT NOT NULL, 
          INDEX IDX_15F96A1C5BC5238A (productgroup_id), 
          INDEX IDX_15F96A1C4584665A (product_id), 
          PRIMARY KEY(productgroup_id, product_id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_product_akeneo_image_attribute (
          id INT AUTO_INCREMENT NOT NULL, 
          akeneoAttributes VARCHAR(255) NOT NULL, 
          productConfiguration_id INT NOT NULL, 
          INDEX IDX_739EBA822B9CB4D4 (productConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_attribute 
        ADD 
          CONSTRAINT FK_5D32E2132B9CB4D4 FOREIGN KEY (productConfiguration_id) REFERENCES akeneo_api_configuration_product (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_default_tax 
        ADD 
          CONSTRAINT FK_48F06B4A2B9CB4D4 FOREIGN KEY (productConfiguration_id) REFERENCES akeneo_api_configuration_product (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_website_mapping 
        ADD 
          CONSTRAINT FK_70E428A2352A8960 FOREIGN KEY (apiConfiguration_id) REFERENCES akeneo_api_configuration (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_website_mapping 
        ADD 
          CONSTRAINT FK_70E428A272F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_images_mapping 
        ADD 
          CONSTRAINT FK_A39A907D2B9CB4D4 FOREIGN KEY (productConfiguration_id) REFERENCES akeneo_api_configuration_product (id)');
        $this->addSql('ALTER TABLE 
          akeneo_productgroup_product 
        ADD 
          CONSTRAINT FK_15F96A1C5BC5238A FOREIGN KEY (productgroup_id) REFERENCES akeneo_product_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE 
          akeneo_productgroup_product 
        ADD 
          CONSTRAINT FK_15F96A1C4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_akeneo_image_attribute 
        ADD 
          CONSTRAINT FK_739EBA822B9CB4D4 FOREIGN KEY (productConfiguration_id) REFERENCES akeneo_api_configuration_product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE akeneo_api_configuration_product_attribute DROP FOREIGN KEY FK_5D32E2132B9CB4D4');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_default_tax DROP FOREIGN KEY FK_48F06B4A2B9CB4D4');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_images_mapping 
        DROP 
          FOREIGN KEY FK_A39A907D2B9CB4D4');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_akeneo_image_attribute 
        DROP 
          FOREIGN KEY FK_739EBA822B9CB4D4');
        $this->addSql('ALTER TABLE akeneo_api_configuration_website_mapping DROP FOREIGN KEY FK_70E428A2352A8960');
        $this->addSql('ALTER TABLE akeneo_productgroup_product DROP FOREIGN KEY FK_15F96A1C5BC5238A');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_attribute');
        $this->addSql('DROP TABLE akeneo_api_configuration_product');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_default_tax');
        $this->addSql('DROP TABLE akeneo_api_configuration_website_mapping');
        $this->addSql('DROP TABLE akeneo_api_configuration_categories');
        $this->addSql('DROP TABLE akeneo_settings');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_images_mapping');
        $this->addSql('DROP TABLE akeneo_attribute_type_mapping');
        $this->addSql('DROP TABLE akeneo_api_configuration');
        $this->addSql('DROP TABLE akeneo_api_product_filters_rules');
        $this->addSql('DROP TABLE akeneo_product_group');
        $this->addSql('DROP TABLE akeneo_productgroup_product');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_akeneo_image_attribute');
    }
}
