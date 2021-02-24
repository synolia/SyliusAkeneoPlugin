<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210224160400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add family column in product group table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_product_group ADD family VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_product_group DROP family');
    }
}
