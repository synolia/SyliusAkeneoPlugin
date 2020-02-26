<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200219141138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product filters rules configuration table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_product_filters_rules (
            id INT AUTO_INCREMENT NOT NULL,
            mode VARCHAR(255) NOT NULL,
            advancedFilter VARCHAR(255) DEFAULT NULL,
            completenessType VARCHAR(255) DEFAULT NULL,
            locales VARCHAR(255) NOT NULL,
            completenessValue VARCHAR(255) DEFAULT NULL,
            status VARCHAR(255) DEFAULT NULL,
            updatedMode VARCHAR(255) DEFAULT NULL,
            updatedBefore DATETIME DEFAULT NULL,
            updatedAfter DATETIME DEFAULT NULL,
            updated VARCHAR(255) DEFAULT NULL,
            families VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE akeneo_api_product_filters_rules');
    }
}
