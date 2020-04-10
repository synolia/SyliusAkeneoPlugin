<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveCategoriesTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Repository\CategoryConfigurationRepository */
    private $categoriesConfigurationRepository;

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Repository\CategoryConfigurationRepository $categoriesConfigurationRepository
     */
    public function __construct(RepositoryInterface $categoriesConfigurationRepository)
    {
        $this->categoriesConfigurationRepository = $categoriesConfigurationRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $resources = $payload->getAkeneoPimClient()->getCategoryApi()->all();

        $configuration = $this->categoriesConfigurationRepository->getCategoriesConfiguration();
        if (!$configuration instanceof CategoryConfiguration) {
            $payload->setResources(\iterator_to_array($resources));

            return $payload;
        }

        $categories = \iterator_to_array($resources);
        $categoriesTree = $this->buildTree($categories, null);

        $keptCategories = $this->excludeNotInRootCategory($configuration, $categoriesTree);
        $excludedCategories = $this->excludeNotImportedCategories($configuration, $categoriesTree);

        //Only keep category of the root category
        foreach ($categories as $key => $category) {
            if (!\in_array($category['code'], $keptCategories, true)) {
                unset($categories[$key]);
            }
        }

        //Remove excluded categories from kept categories
        foreach ($categories as $key => $category) {
            if (\in_array($category['code'], $excludedCategories, true)) {
                unset($categories[$key]);
            }
        }

        $payload->setResources($categories);

        return $payload;
    }

    private function findParentNode(string $parent, array $branches): ?array
    {
        foreach ($branches as $branch) {
            if ($parent === $branch['code']) {
                return $branch;
            }

            if (!isset($branch['children'])) {
                continue;
            }

            $foundNode = $this->findParentNode($parent, $branch['children']);

            if (null !== $foundNode) {
                return $foundNode;
            }
        }

        return null;
    }

    private function getChildCodesFromParent(array $parentNode, array &$nodes = []): void
    {
        $nodes[] = $parentNode['code'];

        if (!isset($parentNode['children'])) {
            return;
        }

        foreach ($parentNode['children'] as $child) {
            $nodes[] = $child['code'];

            $this->getChildCodesFromParent($child ?? [], $nodes);
        }
    }

    private function buildTree(array $elements, ?string $parentCode = null): array
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent'] !== $parentCode) {
                continue;
            }

            $children = $this->buildTree($elements, $element['code']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[$element['code']] = $element;
        }

        return $branch;
    }

    private function excludeNotInRootCategory(CategoryConfiguration $configuration, array &$categoriesTree): array
    {
        $keptCategories = [];
        if (null === $configuration->getRootCategory()) {
            return $keptCategories;
        }

        $rootCategory = $configuration->getRootCategory();
        $rootNode = $this->findParentNode($rootCategory, $categoriesTree);

        if (!is_array($rootNode)) {
            return $keptCategories;
        }

        $this->getChildCodesFromParent($rootNode, $keptCategories);
        $keptCategories = array_unique($keptCategories);

        return $keptCategories;
    }

    private function excludeNotImportedCategories(CategoryConfiguration $configuration, array &$categoriesTree): array
    {
        $excludedCategories = [];
        if (null === $configuration->getNotImportCategories()) {
            return $excludedCategories;
        }

        foreach ($configuration->getNotImportCategories() as $notImportCategory) {
            $parentNode = $this->findParentNode($notImportCategory, $categoriesTree);
            if (!is_array($parentNode)) {
                continue;
            }

            $this->getChildCodesFromParent($parentNode, $excludedCategories);
            $excludedCategories = array_unique($excludedCategories);
        }

        return $excludedCategories;
    }
}
