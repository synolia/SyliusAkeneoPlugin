<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200402065752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added Akeneo to Sylius attribute code mapping table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_attribute_akeneo_sylius_mapping (
                       id INT AUTO_INCREMENT NOT NULL, 
                       akeneoAttribute VARCHAR(255) NOT NULL, 
                       syliusAttribute VARCHAR(255) NOT NULL, 
                       partOfModel VARCHAR(255) DEFAULT NULL,
                       PRIMARY KEY(id)) 
                       DEFAULT CHARACTER SET UTF8 
                       COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
                   ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE akeneo_attribute_akeneo_sylius_mapping');
    }
}
