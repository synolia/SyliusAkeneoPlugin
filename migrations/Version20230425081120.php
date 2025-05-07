<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230425081120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added akeneo taxon attribute tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE akeneo_taxon_attribute_translations (
          id INT AUTO_INCREMENT NOT NULL,
          translatable_id INT DEFAULT NULL,
          name VARCHAR(255) NOT NULL,
          locale VARCHAR(255) NOT NULL,
          INDEX IDX_EDF43DE42C2AC5D3 (translatable_id),
          UNIQUE INDEX attribute_translation (translatable_id, locale),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_taxon_attribute_values (
          id INT AUTO_INCREMENT NOT NULL,
          attribute_id INT NOT NULL,
          subject_id INT DEFAULT NULL,
          locale_code VARCHAR(255) DEFAULT NULL,
          text_value LONGTEXT DEFAULT NULL,
          boolean_value TINYINT(1) DEFAULT NULL,
          integer_value INT DEFAULT NULL,
          float_value DOUBLE PRECISION DEFAULT NULL,
          datetime_value DATETIME DEFAULT NULL,
          date_value DATE DEFAULT NULL,
          json_value JSON DEFAULT NULL,
          INDEX IDX_7AEE551B6E62EFA (attribute_id),
          INDEX IDX_7AEE55123EDC87 (subject_id),
          UNIQUE INDEX attribute_value (
            subject_id, attribute_id, locale_code
          ),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE akeneo_taxon_attributes (
          id INT AUTO_INCREMENT NOT NULL,
          code VARCHAR(255) NOT NULL,
          type VARCHAR(255) NOT NULL,
          configuration LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\',
          storage_type VARCHAR(255) NOT NULL,
          position INT NOT NULL,
          translatable TINYINT(1) NOT NULL,
          created_at DATETIME DEFAULT NULL,
          updated_at DATETIME DEFAULT NULL,
          UNIQUE INDEX UNIQ_C4D4A29777153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          akeneo_taxon_attribute_translations
        ADD
          CONSTRAINT FK_EDF43DE42C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES akeneo_taxon_attributes (id)');
        $this->addSql('ALTER TABLE
          akeneo_taxon_attribute_values
        ADD
          CONSTRAINT FK_7AEE551B6E62EFA FOREIGN KEY (attribute_id) REFERENCES akeneo_taxon_attributes (id)');
        $this->addSql('ALTER TABLE
          akeneo_taxon_attribute_values
        ADD
          CONSTRAINT FK_7AEE55123EDC87 FOREIGN KEY (subject_id) REFERENCES sylius_taxon (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akeneo_taxon_attribute_translations DROP FOREIGN KEY FK_EDF43DE42C2AC5D3');
        $this->addSql('ALTER TABLE akeneo_taxon_attribute_values DROP FOREIGN KEY FK_7AEE551B6E62EFA');
        $this->addSql('DROP TABLE akeneo_taxon_attribute_translations');
        $this->addSql('DROP TABLE akeneo_taxon_attribute_values');
        $this->addSql('DROP TABLE akeneo_taxon_attributes');
    }
}
