<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;

abstract class AbstractOptionValuesProcessor implements OptionValuesProcessorInterface
{
    public function __construct(
        protected RepositoryInterface $productOptionValueRepository,
        protected RepositoryInterface $productOptionValueTranslationRepository,
        protected FactoryInterface $productOptionValueFactory,
        protected FactoryInterface $productOptionValueTranslationFactory,
        protected LoggerInterface $akeneoLogger,
        protected EntityManagerInterface $entityManager,
        protected ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer,
    ) {
    }
}
