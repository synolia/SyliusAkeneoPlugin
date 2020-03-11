<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200311162122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add products group table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE akeneo_products_group (id INT AUTO_INCREMENT NOT NULL, productParent VARCHAR(255) NOT NULL, variationAxes LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_BDB23D441146F2B9 (productParent), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE productsgroup_product (productsgroup_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_6836A5A65121C267 (productsgroup_id), INDEX IDX_6836A5A64584665A (product_id), PRIMARY KEY(productsgroup_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE productsgroup_product ADD CONSTRAINT FK_6836A5A65121C267 FOREIGN KEY (productsgroup_id) REFERENCES akeneo_products_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE productsgroup_product ADD CONSTRAINT FK_6836A5A64584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE productsgroup_product DROP FOREIGN KEY FK_6836A5A65121C267');
        $this->addSql('DROP TABLE akeneo_products_group');
        $this->addSql('DROP TABLE productsgroup_product');
    }
}
