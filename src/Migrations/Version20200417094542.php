<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200417094542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete useless columns and tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE akeneo_api_configuration_product_attribute');
        $this->addSql('DROP TABLE akeneo_api_configuration_product_default_tax');
        $this->addSql('ALTER TABLE akeneo_api_configuration_categories DROP emptyLocalReplaceBy');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_product_attribute (
          id INT AUTO_INCREMENT NOT NULL, 
          attribute VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, 
          value VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, 
          productConfiguration_id INT NOT NULL, 
          INDEX IDX_5D32E2132B9CB4D4 (productConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\'');
        $this->addSql('CREATE TABLE akeneo_api_configuration_product_default_tax (
          id INT AUTO_INCREMENT NOT NULL, 
          website VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, 
          taxClass VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, 
          productConfiguration_id INT NOT NULL, 
          INDEX IDX_48F06B4A2B9CB4D4 (productConfiguration_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\'');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_attribute 
        ADD 
          CONSTRAINT FK_5D32E2132B9CB4D4 FOREIGN KEY (productConfiguration_id) REFERENCES akeneo_api_configuration_product (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_product_default_tax 
        ADD 
          CONSTRAINT FK_48F06B4A2B9CB4D4 FOREIGN KEY (productConfiguration_id) REFERENCES akeneo_api_configuration_product (id)');

        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_categories 
        ADD 
          emptyLocalReplaceBy VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
