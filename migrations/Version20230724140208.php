<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230724140208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changed akeneo product group foreign keys';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('TRUNCATE TABLE akeneo_productgroup_product');
        $this->addSql('ALTER TABLE akeneo_productgroup_product DROP FOREIGN KEY FK_15F96A1C4584665A');
        $this->addSql('ALTER TABLE akeneo_productgroup_product DROP FOREIGN KEY FK_15F96A1C5BC5238A');
        $this->addSql('DROP INDEX `primary` ON akeneo_productgroup_product');
        $this->addSql('ALTER TABLE
          akeneo_productgroup_product
        ADD
          CONSTRAINT FK_15F96A1C4584665A FOREIGN KEY (product_id) REFERENCES akeneo_product_group (id)');
        $this->addSql('ALTER TABLE
          akeneo_productgroup_product
        ADD
          CONSTRAINT FK_15F96A1C5BC5238A FOREIGN KEY (productgroup_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE akeneo_productgroup_product ADD PRIMARY KEY (product_id, productgroup_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE akeneo_productgroup_product DROP FOREIGN KEY FK_15F96A1C4584665A');
        $this->addSql('ALTER TABLE akeneo_productgroup_product DROP FOREIGN KEY FK_15F96A1C5BC5238A');
        $this->addSql('DROP INDEX `PRIMARY` ON akeneo_productgroup_product');
        $this->addSql('ALTER TABLE
          akeneo_productgroup_product
        ADD
          CONSTRAINT FK_15F96A1C4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          akeneo_productgroup_product
        ADD
          CONSTRAINT FK_15F96A1C5BC5238A FOREIGN KEY (productgroup_id) REFERENCES akeneo_product_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE akeneo_productgroup_product ADD PRIMARY KEY (productgroup_id, product_id)');
    }
}
