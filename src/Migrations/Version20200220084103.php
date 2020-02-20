<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200220084103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add categories configuration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_categories (
            id INT AUTO_INCREMENT NOT NULL, 
            activeNewCategories TINYINT(1) NOT NULL,
            notImportCategories LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\',
            mainCategory VARCHAR(255) NOT NULL, 
            rootCategory VARCHAR(255) NOT NULL, 
            emptyLocalReplaceBy VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE akeneo_api_configuration_categories');
    }
}
