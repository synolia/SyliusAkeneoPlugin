<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221215073239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactored Akeneo ProductGroup table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_52E487761146F2B9 ON akeneo_product_group');
        $this->addSql('ALTER TABLE
          akeneo_product_group
        ADD
          parent_id INT DEFAULT NULL,
        ADD
          familyVariant VARCHAR(255) NOT NULL,
        CHANGE
          productparent model VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE
          akeneo_product_group
        ADD
          CONSTRAINT FK_52E48776727ACA70 FOREIGN KEY (parent_id) REFERENCES akeneo_product_group (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52E48776D79572D9 ON akeneo_product_group (model)');
        $this->addSql('CREATE INDEX IDX_52E48776727ACA70 ON akeneo_product_group (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE akeneo_product_group DROP FOREIGN KEY FK_52E48776727ACA70');
        $this->addSql('DROP INDEX UNIQ_52E48776D79572D9 ON akeneo_product_group');
        $this->addSql('DROP INDEX IDX_52E48776727ACA70 ON akeneo_product_group');
        $this->addSql('ALTER TABLE
          akeneo_product_group
        ADD
          productParent VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`,
        DROP
          parent_id,
        DROP
          model,
        DROP
          familyVariant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52E487761146F2B9 ON akeneo_product_group (productParent)');
    }
}
