<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200605081308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Akeneo plugin tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_product (
          id INT AUTO_INCREMENT NOT NULL, 
          akeneoPriceAttribute VARCHAR(255) DEFAULT NULL, 
          akeneoEnabledChannelsAttribute VARCHAR(255) DEFAULT NULL, 
          attributeMapping LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', 
          importMediaFiles TINYINT(1) DEFAULT NULL, 
          regenerateUrlRewrites TINYINT(1) DEFAULT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_attribute_akeneo_sylius_mapping (
          id INT AUTO_INCREMENT NOT NULL, 
          akeneoAttribute VARCHAR(255) NOT NULL, 
          syliusAttribute VARCHAR(255) NOT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_settings (
          id INT AUTO_INCREMENT NOT NULL, 
          name VARCHAR(255) NOT NULL, 
          value VARCHAR(255) DEFAULT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_categories (
          id INT AUTO_INCREMENT NOT NULL, 
          notImportCategories LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
          rootCategories LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
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
          akeneoAttributeType VARCHAR(255) NOT NULL, 
          attributeType VARCHAR(255) NOT NULL, 
          UNIQUE INDEX UNIQ_FF5E270FA2851109 (akeneoAttributeType), 
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
          locales LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
          completenessValue INT NOT NULL, 
          status VARCHAR(255) DEFAULT NULL, 
          updatedMode VARCHAR(255) DEFAULT NULL, 
          updatedBefore DATETIME NOT NULL, 
          updatedAfter DATETIME NOT NULL, 
          updated INT DEFAULT NULL, 
          excludeFamilies LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', 
          channel VARCHAR(255) NOT NULL, 
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
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_images_mapping 
        DROP 
          FOREIGN KEY FK_A39A907D2B9CB4D4');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_akeneo_image_attribute 
        DROP 
          FOREIGN KEY FK_739EBA822B9CB4D4');
        $this->addSql('ALTER TABLE akeneo_productgroup_product DROP FOREIGN KEY FK_15F96A1C5BC5238A');
        $this->addSql('DROP TABLE akeneo_api_configuration_product');
        $this->addSql('DROP TABLE akeneo_attribute_akeneo_sylius_mapping');
        $this->addSql('DROP TABLE akeneo_settings');
        $this->addSql('DROP TABLE akeneo_api_configuration_categories');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_images_mapping');
        $this->addSql('DROP TABLE akeneo_attribute_type_mapping');
        $this->addSql('DROP TABLE akeneo_api_configuration');
        $this->addSql('DROP TABLE akeneo_api_product_filters_rules');
        $this->addSql('DROP TABLE akeneo_product_group');
        $this->addSql('DROP TABLE akeneo_productgroup_product');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_akeneo_image_attribute');
    }
}
