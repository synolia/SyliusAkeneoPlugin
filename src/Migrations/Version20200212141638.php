<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200212141638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added akeneo configuration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration (
          id INT AUTO_INCREMENT NOT NULL, 
          baseUrl VARCHAR(255) NOT NULL, 
          apiClientId VARCHAR(255) NOT NULL, 
          apiClientSecret VARCHAR(255) NOT NULL, 
          token VARCHAR(255) NOT NULL, 
          refreshToken VARCHAR(255) NOT NULL, 
          paginationSize INT NOT NULL, 
          isEnterprise TINYINT(1) NOT NULL, 
          username VARCHAR(255) NOT NULL, 
          password VARCHAR(255) NOT NULL, 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_api_configuration_website_mapping (
          id INT AUTO_INCREMENT NOT NULL, 
          channel_id INT DEFAULT NULL, 
          akeneoChannel VARCHAR(255) NOT NULL, 
          apiConfiguration_id INT DEFAULT NULL, 
          INDEX IDX_70E428A2352A8960 (apiConfiguration_id), 
          INDEX IDX_70E428A272F5A1AA (channel_id), 
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_website_mapping 
        ADD 
          CONSTRAINT FK_70E428A2352A8960 FOREIGN KEY (apiConfiguration_id) REFERENCES akeneo_api_configuration (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration_website_mapping 
        ADD 
          CONSTRAINT FK_70E428A272F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id)');
        $this->addSql('ALTER TABLE 
          akeneo_api_configuration CHANGE token token VARCHAR(255) DEFAULT NULL, 
          CHANGE refreshToken refreshToken VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE akeneo_api_configuration_website_mapping DROP FOREIGN KEY FK_70E428A2352A8960');
        $this->addSql('DROP TABLE akeneo_api_configuration');
        $this->addSql('DROP TABLE akeneo_api_configuration_website_mapping');
    }
}
