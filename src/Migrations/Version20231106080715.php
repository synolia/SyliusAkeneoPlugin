<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231106080715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added useAkeneoPositions column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_api_configuration_categories ADD useAkeneoPositions TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_api_configuration_categories DROP useAkeneoPositions');
    }
}
