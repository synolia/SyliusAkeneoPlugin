<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\Attribute;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Creator\AttributeCreatorInterface;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\AfterProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\BeforeProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeChoiceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeTableProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOption\ProductOptionProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetrieverInterface;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyVariantRetrieverInterface;

class AttributeResourceProcessor implements AkeneoResourceProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductAttributeChoiceProcessorInterface $attributeChoiceProcessor,
        private ProductOptionProcessorInterface $productOptionProcessor,
        private ProductAttributeTableProcessorInterface $productAttributeTableProcessor,
        private EventDispatcherInterface $dispatcher,
        private AttributeCreatorInterface $attributeCreator,
        private FamilyRetrieverInterface $familyRetriever,
        private FamilyVariantRetrieverInterface $familyVariantRetriever,
        private LoggerInterface $akeneoLogger,
        private ManagerRegistry $managerRegistry,
        #[Autowire('%env(int:SYNOLIA_AKENEO_MAX_RETRY_COUNT)%')]
        private int $maxRetryCount,
        #[Autowire('%env(int:SYNOLIA_AKENEO_RETRY_WAIT_TIME)%')]
        private int $retryWaitTime,
        private int $retryCount = 0,
    ) {
    }

    /**
     * @throws MaxResourceProcessorRetryException
     */
    public function process(array $resource): void
    {
        if ($this->retryCount === $this->maxRetryCount) {
            $this->retryCount = 0;

            throw new MaxResourceProcessorRetryException();
        }

        $variationAxes = array_unique($this->getVariationAxes());

        try {
            $this->akeneoLogger->notice('Processing attribute', [
                'code' => $resource['code'] ?? 'unknown',
            ]);

            $this->dispatcher->dispatch(new BeforeProcessingAttributeEvent($resource));

            $attribute = $this->attributeCreator->create($resource);
            $this->entityManager->flush();

            //Handle attribute options
            $this->attributeChoiceProcessor->process($attribute, $resource);

            //Handle attribute table configuration
            $this->productAttributeTableProcessor->process($attribute, $resource);

            //Handler options
            $this->productOptionProcessor->process($attribute, $variationAxes);

            $this->dispatcher->dispatch(new AfterProcessingAttributeEvent($resource, $attribute));

            $this->entityManager->flush();
        } catch (ExcludedAttributeException) {
            // Do nothing
        } catch (ORMInvalidArgumentException $ormInvalidArgumentException) {
            ++$this->retryCount;
            usleep($this->retryWaitTime);

            $this->akeneoLogger->error('Retrying import', [
                'product' => $resource,
                'retry_count' => $this->retryCount,
                'error' => $ormInvalidArgumentException->getMessage(),
            ]);

            $this->entityManager = $this->getNewEntityManager();
            $this->process($resource);
        } catch (\Throwable $throwable) {
            ++$this->retryCount;
            usleep($this->retryWaitTime);

            $this->akeneoLogger->error('Retrying import', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            $this->entityManager = $this->getNewEntityManager();
            $this->process($resource);
        }
    }

    private function getVariationAxes(): array
    {
        $variationAxes = [];
        $families = $this->familyRetriever->getFamilies();

        foreach ($families as $family) {
            $familyVariants = $this->familyVariantRetriever->getVariants($family['code']);

            $variationAxes = array_merge($variationAxes, $this->getVariationAxesForFamilies($familyVariants));
        }

        return $variationAxes;
    }

    private function getVariationAxesForFamilies(array $familyVariants): array
    {
        $variationAxes = [];

        /** @var array{variant_attribute_sets: array} $familyVariant */
        foreach ($familyVariants as $familyVariant) {
            /** @var array{axes: array} $variantAttributeSet */
            foreach ($familyVariant['variant_attribute_sets'] as $variantAttributeSet) {
                foreach ($variantAttributeSet['axes'] as $axe) {
                    $variationAxes[] = $axe;
                }
            }
        }

        return $variationAxes;
    }

    private function getNewEntityManager(): EntityManagerInterface
    {
        $objectManager = $this->managerRegistry->resetManager();

        if (!$objectManager instanceof EntityManagerInterface) {
            throw new \LogicException('Wrong ObjectManager');
        }

        return $objectManager;
    }
}
