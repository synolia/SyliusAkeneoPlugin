<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200319151625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change entities name';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_producs_akeneo_image_attribute (
            id INT AUTO_INCREMENT NOT NULL,
            akeneoAttributes VARCHAR(255) NOT NULL,
            productConfiguration_id INT NOT NULL,
            INDEX IDX_42868DF52B9CB4D4 (productConfiguration_id),
            PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE akeneo_api_configuration_producs_akeneo_image_attribute
            ADD CONSTRAINT FK_42868DF52B9CB4D4
            FOREIGN KEY (productConfiguration_id)
            REFERENCES akeneo_api_configuration_product (id)'
        );
        $this->addSql('DROP TABLE akeneo_api_configuration_product_akeneo_image_attribute');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_attribute RENAME INDEX idx_a7fd5a6051fb68f2 TO IDX_5D32E2132B9CB4D4');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_default_tax RENAME INDEX idx_bdc7bffb51fb68f2 TO IDX_48F06B4A2B9CB4D4');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_images_mapping RENAME INDEX idx_3aefa1bb51fb68f2 TO IDX_A39A907D2B9CB4D4');
        $this->addSql('ALTER TABLE akeneo_product_group RENAME INDEX uniq_bdb23d441146f2b9 TO UNIQ_52E487761146F2B9');
        $this->addSql('ALTER TABLE productgroup_product RENAME INDEX idx_6836a5a65121c267 TO IDX_82B510635BC5238A');
        $this->addSql('ALTER TABLE productgroup_product RENAME INDEX idx_6836a5a64584665a TO IDX_82B510634584665A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_product_akeneo_image_attribute (
            id INT AUTO_INCREMENT NOT NULL,
            akeneoAttributes VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`,
            productConfiguration_id INT NOT NULL, INDEX IDX_1E48229B51FB68F2 (productConfiguration_id),
            PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_akeneo_image_attribute
            ADD CONSTRAINT FK_1E48229B51FB68F2
            FOREIGN KEY (productConfiguration_id)
            REFERENCES akeneo_api_configuration_product (id)'
        );
        $this->addSql('DROP TABLE akeneo_api_configuration_producs_akeneo_image_attribute');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_attribute RENAME INDEX idx_5d32e2132b9cb4d4 TO IDX_A7FD5A6051FB68F2');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_default_tax RENAME INDEX idx_48f06b4a2b9cb4d4 TO IDX_BDC7BFFB51FB68F2');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product_images_mapping RENAME INDEX idx_a39a907d2b9cb4d4 TO IDX_3AEFA1BB51FB68F2');
        $this->addSql('ALTER TABLE akeneo_product_group RENAME INDEX uniq_52e487761146f2b9 TO UNIQ_BDB23D441146F2B9');
        $this->addSql('ALTER TABLE productgroup_product RENAME INDEX idx_82b510634584665a TO IDX_6836A5A64584665A');
        $this->addSql('ALTER TABLE productgroup_product RENAME INDEX idx_82b510635bc5238a TO IDX_6836A5A65121C267');
    }
}
