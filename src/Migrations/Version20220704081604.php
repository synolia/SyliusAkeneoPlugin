<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220704081604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added edition and removed isEnterprise from akeneo_api_configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(\sprintf(
            'ALTER TABLE akeneo_api_configuration ADD edition VARCHAR(255) DEFAULT \'%s\' NOT NULL',
            $this->getDefaultEdition(),
        ));

        $this->addSql('ALTER TABLE akeneo_api_configuration DROP isEnterprise');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE akeneo_api_configuration ADD isEnterprise TINYINT(1) DEFAULT(0) NOT NULL');
        $this->addSql('ALTER TABLE akeneo_api_configuration DROP edition');
    }

    private function getDefaultEdition(): string
    {
        if (!\class_exists(\Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration::class)) {
            return AkeneoEditionEnum::COMMUNITY;
        }

        $hasIsEnterpriseColumn = $this->connection
            ->executeQuery('SHOW COLUMNS FROM `akeneo_api_configuration` LIKE \'isEnterprise\'')
            ->fetchAssociative()
        ;

        if (!$hasIsEnterpriseColumn) {
            return AkeneoEditionEnum::COMMUNITY;
        }

        /** @var array $isEnterpriseArray */
        $isEnterpriseArray = $this->connection
            ->executeQuery('SELECT isEnterprise FROM akeneo_api_configuration')
            ->fetchAssociative()
        ;

        if (!is_array($isEnterpriseArray)) {
            return AkeneoEditionEnum::COMMUNITY;
        }

        if (!\array_key_exists('isEnterprise', $isEnterpriseArray)) {
            return AkeneoEditionEnum::COMMUNITY;
        }

        return $isEnterpriseArray['isEnterprise'] ? AkeneoEditionEnum::ENTERPRISE : AkeneoEditionEnum::COMMUNITY;
    }
}
