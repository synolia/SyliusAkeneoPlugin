<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;

class ParentProcessor implements CategoryProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 900;
    }

    public function __construct(private TaxonRepository $taxonRepository)
    {
    }

    public function process(TaxonInterface $taxon, array $resource): void
    {
        /** @var TaxonInterface|null $parent */
        $parent = $this->taxonRepository->findOneBy(['code' => $resource['parent']]);

        if (!$parent instanceof TaxonInterface) {
            return;
        }

        $taxon->setParent($parent);
    }

    /**
     * @inheritdoc
     */
    public function support(TaxonInterface $taxon, array $resource): bool
    {
        return null !== $resource['parent'];
    }
}
