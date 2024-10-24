<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;

class PositionProcessor implements CategoryProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 800;
    }

    public function __construct(
        private CategoryConfigurationProviderInterface $categoryConfigurationProvider,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public function process(TaxonInterface $taxon, array $resource): void
    {
        $taxon->setPosition($resource['position']);

        $this->akeneoLogger->info('Update Taxon Position', [
            'taxon_code' => $taxon->getCode(),
            'position' => $resource['position'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function support(TaxonInterface $taxon, array $resource): bool
    {
        return true === $this->categoryConfigurationProvider->get()->useAkeneoPositions() && array_key_exists('position', $resource);
    }
}
