<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210506132536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add settings table to configure the channels to be enabled on products when importing them.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE akeneo_product_configuration_channels (
          product_configuration_id INT NOT NULL,
          channel_id INT NOT NULL,
          INDEX IDX_E6A56A05FD7F4924 (product_configuration_id),
          UNIQUE INDEX UNIQ_E6A56A0572F5A1AA (channel_id),
          PRIMARY KEY(
            product_configuration_id, channel_id
          )
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          akeneo_product_configuration_channels
        ADD
          CONSTRAINT FK_E6A56A05FD7F4924 FOREIGN KEY (product_configuration_id) REFERENCES akeneo_api_configuration_product (id)');
        $this->addSql('ALTER TABLE
          akeneo_product_configuration_channels
        ADD
          CONSTRAINT FK_E6A56A0572F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id)');
        $this->addSql('ALTER TABLE
          akeneo_api_configuration_product
        ADD
          enable_imported_products TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE akeneo_product_configuration_channels');
        $this->addSql('ALTER TABLE akeneo_api_configuration_product DROP enable_imported_products');
    }
}
