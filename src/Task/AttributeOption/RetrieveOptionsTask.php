<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AttributeOption;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MultiSelectAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;
use Webmozart\Assert\Assert;

final class RetrieveOptionsTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $type;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    private $client;

    public function __construct(
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ConfigurationProvider $configurationProvider,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        EntityManagerInterface $entityManager
    ) {
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->logger = $akeneoLogger;
        $this->configurationProvider = $configurationProvider;
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->entityManager = $entityManager;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = 'Attribute Option';
        $this->logger->notice(Messages::retrieveFromAPI($this->type));
        Assert::isInstanceOf($payload, AttributePayload::class);
        $this->client = $payload->getAkeneoPimClient();

        $compatibleAttributes = [];

        $processedCount = 0;
        $totalItemsCount = $this->count();

        $query = $this->prepareSelectQuery(AttributePayload::SELECT_PAGINATION_SIZE, 0);
        $query->executeStatement();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                $resource = \json_decode($result['values'], true);

                try {
                    $attributeTypeMatcher = $this->attributeTypeMatcher->match($resource['type']);
                    if (
                        !$attributeTypeMatcher instanceof SelectAttributeTypeMatcher &&
                        !$attributeTypeMatcher instanceof MultiSelectAttributeTypeMatcher &&
                        !$attributeTypeMatcher instanceof ReferenceEntityAttributeTypeMatcher
                    ) {
                        continue;
                    }
                    $compatibleAttributes[$resource['code']] = [
                        'isMultiple' => $attributeTypeMatcher instanceof MultiSelectAttributeTypeMatcher,
                        'typeMatcher' => $attributeTypeMatcher,
                    ];
                } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
                    $this->logger->warning(\sprintf(
                        '%s: %s',
                        $resource['code'],
                        $unsupportedAttributeTypeException->getMessage()
                    ));

                    continue;
                }
            }

            $processedCount += \count($results);
            $this->logger->info(\sprintf('Processed %d attributes out of %d.', $processedCount, $totalItemsCount));
            $query = $this->prepareSelectQuery(AttributePayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->executeStatement();
        }

        $optionsPayload = $this->process($payload, $compatibleAttributes);
        $this->logger->info(Messages::totalToImport($this->type, count($optionsPayload->getSelectOptionsResources())));

        return $optionsPayload;
    }

    private function count(): int
    {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT count(id) FROM `%s`',
            AttributePayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->executeStatement();

        return (int) \current($query->fetch());
    }

    private function prepareSelectQuery(
        int $limit = AttributePayload::SELECT_PAGINATION_SIZE,
        int $offset = 0
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT `values`
             FROM `%s`
             LIMIT :limit
             OFFSET :offset',
            AttributePayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->bindValue('limit', $limit, ParameterType::INTEGER);
        $query->bindValue('offset', $offset, ParameterType::INTEGER);

        return $query;
    }

    private function process(PipelinePayloadInterface $payload, array $attributeCodes): OptionsPayload
    {
        $optionsPayload = new OptionsPayload($payload->getAkeneoPimClient());
        $selectOptionsResources = [];
        $referenceEntityOptionsResources = [];
        foreach ($attributeCodes as $attributeCode => $values) {
            $this->buildArray(
                $selectOptionsResources,
                $referenceEntityOptionsResources,
                $attributeCode,
                $values
            );
        }
        $optionsPayload->setSelectOptionsResources($selectOptionsResources);
        $optionsPayload->setReferenceEntityOptionsResources($referenceEntityOptionsResources);

        return $optionsPayload;
    }

    private function buildArray(
        array &$selectOptionsResources,
        array &$referenceEntityOptionsResources,
        string $attributeCode,
        array $values
    ): void {
        if ($values['typeMatcher'] instanceof ReferenceEntityAttributeTypeMatcher) {
            $referenceEntityAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);
            $records = $this->client->getReferenceEntityRecordApi()->all($referenceEntityAttributeProperties['reference_data_name']);

            $referenceEntityOptionsResources[$attributeCode] = [
                'isMultiple' => false,
                'resources' => $records,
            ];

            return;
        }

        $selectOptionsResources[$attributeCode] = [
            'isMultiple' => $values['isMultiple'],
            'resources' => $this->client->getAttributeOptionApi()->all(
                $attributeCode,
                $this->configurationProvider->getConfiguration()->getPaginationSize()
            ),
        ];
    }
}
