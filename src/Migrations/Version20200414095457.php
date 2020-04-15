<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200414095457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove website mapping and add channel';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE akeneo_api_configuration_website_mapping');
        $this->addSql('ALTER TABLE akeneo_api_configuration ADD channel VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_api_configuration_website_mapping (
            id INT AUTO_INCREMENT NOT NULL,
            channel_id INT DEFAULT NULL,
            akeneoChannel VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`,
            apiConfiguration_id INT DEFAULT NULL, INDEX IDX_70E428A2352A8960 (apiConfiguration_id),
            INDEX IDX_70E428A272F5A1AA (channel_id),
            PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE akeneo_api_configuration_website_mapping 
            ADD CONSTRAINT FK_70E428A2352A8960
            FOREIGN KEY (apiConfiguration_id)
            REFERENCES akeneo_api_configuration (id)'
        );
        $this->addSql('ALTER TABLE akeneo_api_configuration_website_mapping
            ADD CONSTRAINT FK_70E428A272F5A1AA
            FOREIGN KEY (channel_id)
            REFERENCES sylius_channel (id)'
        );
        $this->addSql('
            ALTER TABLE akeneo_api_configuration 
            DROP channel'
        );
    }
}
