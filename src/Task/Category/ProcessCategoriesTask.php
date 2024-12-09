<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Filter\SearchFilterProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\TaskHandlerTrait;

/**
 * @internal
 */
final class ProcessCategoriesTask implements AkeneoTaskInterface
{
    use TaskHandlerTrait{
        TaskHandlerTrait::__construct as private __taskHandlerConstruct;
    }

    public function __construct(
        private CategoryConfigurationProviderInterface $categoryConfigurationProvider,
        private LoggerInterface $akeneoLogger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private SearchFilterProviderInterface $searchFilterProvider,
        private TaskHandlerProviderInterface $taskHandlerProvider,
    ) {
        $this->__taskHandlerConstruct($taskHandlerProvider);
    }

    /**
     * @param CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->akeneoLogger->debug(self::class);
        $this->akeneoLogger->debug(Messages::retrieveFromAPI($payload->getType()));

        $resources = $payload->getAkeneoPimClient()->getCategoryApi()->all(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            $this->searchFilterProvider->get($payload),
        );

        $categories = iterator_to_array($resources);
        $categoriesTree = $this->buildTree($categories, null);

        $rootCategoryCodes = $this->categoryConfigurationProvider->get()->getCategoryCodesToImport();
        $excludedCategoryCodes = $this->categoryConfigurationProvider->get()->getCategoryCodesToExclude();

        $keptCategories = $this->excludeNotInRootCategory($rootCategoryCodes, $categoriesTree);
        $excludedCategories = $this->excludeNotImportedCategories($excludedCategoryCodes, $categoriesTree);

        //Only keep category of the root category
        /**
         * @var array{code: string} $category
         */
        foreach ($categories as $key => $category) {
            if (!\in_array($category['code'], $keptCategories, true)) {
                $this->akeneoLogger->info(sprintf('%s: %s is not inside selected root categories and will be excluded', $payload->getType(), $category['code']));
                unset($categories[$key]);
            }
        }

        //Remove excluded categories from kept categories
        /**
         * @var array{code: string} $category
         */
        foreach ($categories as $key => $category) {
            if (\in_array($category['code'], $excludedCategories, true)) {
                $this->akeneoLogger->info(sprintf('%s: %s is explicitly excluded from configuration', $payload->getType(), $category['code']));
                unset($categories[$key]);
            }
        }

        $this->akeneoLogger->info(Messages::totalExcludedFromImport($payload->getType(), \count($excludedCategories)));
        $this->akeneoLogger->info(Messages::totalToImport($payload->getType(), \count($categories)));

        $payload->setResources($categories);

        $this->handle($payload, $categories);

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

    private function excludeNotInRootCategory(array $rootCategoryCodes, array &$categoriesTree): array
    {
        $keptCategories = [];
        if (0 === \count($rootCategoryCodes)) {
            return $keptCategories;
        }

        foreach ($rootCategoryCodes as $rootCategory) {
            $rootNode = $this->findParentNode($rootCategory, $categoriesTree);

            if (!\is_array($rootNode)) {
                return $keptCategories;
            }

            $this->getChildCodesFromParent($rootNode, $keptCategories);
            $keptCategories = array_unique($keptCategories);
        }

        return $keptCategories;
    }

    private function excludeNotImportedCategories(array $excludedCategoryCodes, array &$categoriesTree): array
    {
        $excludedCategories = [];
        if (0 === \count($excludedCategoryCodes)) {
            return $excludedCategories;
        }

        foreach ($excludedCategoryCodes as $notImportCategory) {
            $parentNode = $this->findParentNode($notImportCategory, $categoriesTree);
            if (!\is_array($parentNode)) {
                continue;
            }

            $this->getChildCodesFromParent($parentNode, $excludedCategories);
            $excludedCategories = array_unique($excludedCategories);
        }

        return $excludedCategories;
    }
}
