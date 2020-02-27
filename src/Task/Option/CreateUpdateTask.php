<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateUpdateTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager */
    private $productOptionManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider */
    private $configurationProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeAkeneoRepository,
        ProductOptionManager $productOptionManager,
        ConfigurationProvider $configurationProvider
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeAkeneoRepository;
        $this->productOptionManager = $productOptionManager;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$this->productAttributeRepository instanceof ProductAttributeRepository) {
            throw new \LogicException('Wrong repository instance provided.');
        }

        $this->entityManager->beginTransaction();
        $variationAxes = [];
        $families = $payload->getAkeneoPimClient()->getFamilyApi()->all(
            $this->configurationProvider->getConfiguration()->getPaginationSize() ?? ApiConfiguration::DEFAULT_PAGINATION_SIZE
        );
        foreach ($families as $family) {
            $familyVariants = $payload->getAkeneoPimClient()->getFamilyVariantApi()->all(
                $family['code'],
                $this->configurationProvider->getConfiguration()->getPaginationSize() ?? ApiConfiguration::DEFAULT_PAGINATION_SIZE
            );

            foreach ($familyVariants as $familyVariant) {
                //Sort array of variant attribute sets by level DESC
                usort($familyVariant['variant_attribute_sets'], function ($leftVariantAttributeSets, $rightVariantAttributeSets) {
                    return $leftVariantAttributeSets['level'] < $rightVariantAttributeSets['level'];
                });

                //We only want to get the last variation set
                foreach ($familyVariant['variant_attribute_sets'][0]['axes'] as $axe) {
                    $variationAxes[] = $axe;
                }
            }
        }
        $variationAxes = array_unique($variationAxes);

        /** @var AttributeInterface $attribute */
        foreach ($this->productAttributeRepository->findByCodes($variationAxes) as $attribute) {
            if (\in_array($attribute->getCode(), $variationAxes, true)) {
                $this->productOptionManager->createOrUpdateProductOptionFromAttribute($attribute);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        return $payload;
    }
}
