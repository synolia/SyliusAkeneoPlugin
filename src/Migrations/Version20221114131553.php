<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221114131553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added associations column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_product_group ADD associations LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_product_group DROP associations');
    }
}
