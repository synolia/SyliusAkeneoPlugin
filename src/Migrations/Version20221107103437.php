<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221107103437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added asset table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE akeneo_assets (
          id INT AUTO_INCREMENT NOT NULL,
          family_code VARCHAR(255) NOT NULL,
          asset_code VARCHAR(255) NOT NULL,
          attribute_code VARCHAR(255) NOT NULL,
          type VARCHAR(255) NOT NULL,
          locale VARCHAR(255) NOT NULL,
          scope VARCHAR(255) NOT NULL,
          content JSON NOT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_assets_products (
          asset_id INT NOT NULL,
          owner_id INT NOT NULL,
          INDEX IDX_397D5EBB5DA1941 (asset_id),
          INDEX IDX_397D5EBB7E3C61F9 (owner_id),
          PRIMARY KEY(asset_id, owner_id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_assets_product_variants (
          asset_id INT NOT NULL,
          variant_id INT NOT NULL,
          INDEX IDX_34A6BEA55DA1941 (asset_id),
          INDEX IDX_34A6BEA53B69A9AF (variant_id),
          PRIMARY KEY(asset_id, variant_id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          akeneo_assets_products
        ADD
          CONSTRAINT FK_397D5EBB5DA1941 FOREIGN KEY (asset_id) REFERENCES akeneo_assets (id)');
        $this->addSql('ALTER TABLE
          akeneo_assets_products
        ADD
          CONSTRAINT FK_397D5EBB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          akeneo_assets_product_variants
        ADD
          CONSTRAINT FK_34A6BEA55DA1941 FOREIGN KEY (asset_id) REFERENCES akeneo_assets (id)');
        $this->addSql('ALTER TABLE
          akeneo_assets_product_variants
        ADD
          CONSTRAINT FK_34A6BEA53B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE akeneo_assets_products DROP FOREIGN KEY FK_397D5EBB5DA1941');
        $this->addSql('ALTER TABLE akeneo_assets_product_variants DROP FOREIGN KEY FK_34A6BEA55DA1941');
        $this->addSql('DROP TABLE akeneo_assets');
        $this->addSql('DROP TABLE akeneo_assets_products');
        $this->addSql('DROP TABLE akeneo_assets_product_variants');
    }
}
