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
    protected RepositoryInterface $productOptionValueRepository;

    protected FactoryInterface $productOptionValueFactory;

    protected RepositoryInterface $productOptionValueTranslationRepository;

    protected FactoryInterface $productOptionValueTranslationFactory;

    protected LoggerInterface $akeneoLogger;

    protected EntityManagerInterface $entityManager;

    protected ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer;

    public function __construct(
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $productOptionValueTranslationRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        LoggerInterface $akeneoLogger,
        EntityManagerInterface $entityManager,
        ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer
    ) {
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->productOptionValueTranslationRepository = $productOptionValueTranslationRepository;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->productOptionValueTranslationFactory = $productOptionValueTranslationFactory;
        $this->akeneoLogger = $akeneoLogger;
        $this->entityManager = $entityManager;
        $this->productOptionValueDataTransformer = $productOptionValueDataTransformer;
    }
}
