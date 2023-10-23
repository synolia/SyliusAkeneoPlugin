<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231019090303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow to delete cascade product group';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE akeneo_product_group DROP FOREIGN KEY FK_52E48776727ACA70');
        $this->addSql('ALTER TABLE akeneo_product_group ADD CONSTRAINT FK_52E48776727ACA70 FOREIGN KEY (parent_id) REFERENCES akeneo_product_group (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE akeneo_product_group DROP FOREIGN KEY FK_52E48776727ACA70');
        $this->addSql('ALTER TABLE akeneo_product_group ADD CONSTRAINT FK_52E48776727ACA70 FOREIGN KEY (parent_id) REFERENCES akeneo_product_group (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
