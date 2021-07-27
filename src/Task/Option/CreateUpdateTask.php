<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Webmozart\Assert\Assert;

final class CreateUpdateTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager */
    private $productOptionManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository */
    private $productAttributeRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider */
    private $configurationProvider;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $type;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductAttributeRepository $productAttributeAkeneoRepository,
        ProductOptionManager $productOptionManager,
        ConfigurationProvider $configurationProvider,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeAkeneoRepository;
        $this->productOptionManager = $productOptionManager;
        $this->configurationProvider = $configurationProvider;
        $this->logger = $akeneoLogger;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (!$this->productAttributeRepository instanceof ProductAttributeRepository) {
            throw new \LogicException('Wrong repository instance provided.');
        }

        Assert::isInstanceOf($payload, OptionsPayload::class);

        try {
            $this->entityManager->beginTransaction();
            $variationAxes = \array_unique($this->getVariationAxes($payload));
            $this->logger->info(Messages::totalToImport($payload->getType(), count($variationAxes)));

            /** @var AttributeInterface $attribute */
            foreach ($this->productAttributeRepository->findByCodes($variationAxes) as $attribute) {
                if (\in_array($attribute->getCode(), $variationAxes, true)) {
                    $this->process($attribute);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function getVariationAxes(PipelinePayloadInterface $payload): array
    {
        Assert::isInstanceOf($payload, AbstractPayload::class);
        $variationAxes = [];
        $client = $payload->getAkeneoPimClient();

        $families = $client->getFamilyApi()->all(
            $this->configurationProvider->getConfiguration()->getPaginationSize()
        );

        foreach ($families as $family) {
            $familyVariants = $client->getFamilyVariantApi()->all(
                $family['code'],
                $this->configurationProvider->getConfiguration()->getPaginationSize()
            );

            $variationAxes = \array_merge($variationAxes, $this->getVariationAxesForFamilies($familyVariants));
        }

        return $variationAxes;
    }

    private function getVariationAxesForFamilies(ResourceCursorInterface $familyVariants): array
    {
        $variationAxes = [];

        foreach ($familyVariants as $familyVariant) {
            //Sort array of variant attribute sets by level DESC
            \usort($familyVariant['variant_attribute_sets'], function ($leftVariantAttributeSets, $rightVariantAttributeSets) {
                return (int) ($leftVariantAttributeSets['level'] < $rightVariantAttributeSets['level']);
            });

            //We only want to get the last variation set
            foreach ($familyVariant['variant_attribute_sets'][0]['axes'] as $axe) {
                $variationAxes[] = $axe;
            }
        }

        return $variationAxes;
    }

    private function process(AttributeInterface $attribute): void
    {
        $productOption = $this->productOptionManager->getProductOptionFromAttribute($attribute);

        if (null === $productOption) {
            $productOption = $this->productOptionManager->createProductOptionFromAttribute($attribute);
            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $productOption->getCode()));
        } else {
            ++$this->updateCount;
            $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $productOption->getCode()));
        }

        $this->productOptionManager->updateData($attribute, $productOption);
    }
}
