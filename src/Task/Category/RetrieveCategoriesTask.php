<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Event\FilterEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\CategoryConfigurationRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

/**
 * @internal
 */
final class RetrieveCategoriesTask implements AkeneoTaskInterface
{
    public function __construct(
        private CategoryConfigurationRepository $categoriesConfigurationRepository,
        private LoggerInterface $logger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $queryParameters = [];
        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        try {
            $event = new FilterEvent($payload->getCommandContext());
            $this->eventDispatcher->dispatch($event);

            $queryParameters['search'] = $event->getFilters();
        } catch (CommandContextIsNullException) {
            $queryParameters = [];
        }

        $resources = $payload->getAkeneoPimClient()->getCategoryApi()->all(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            $queryParameters,
        );

        $configuration = $this->categoriesConfigurationRepository->getCategoriesConfiguration();
        if (!$configuration instanceof CategoryConfiguration) {
            $resourcesArray = iterator_to_array($resources);
            $payload->setResources($resourcesArray);
            $this->logger->info(Messages::totalToImport($payload->getType(), \count($resourcesArray)));

            return $payload;
        }

        $categories = iterator_to_array($resources);
        $categoriesTree = $this->buildTree($categories, null);

        $keptCategories = $this->excludeNotInRootCategory($configuration, $categoriesTree);
        $excludedCategories = $this->excludeNotImportedCategories($configuration, $categoriesTree);

        //Only keep category of the root category
        foreach ($categories as $key => $category) {
            if (!\in_array($category['code'], $keptCategories, true)) {
                $this->logger->info(sprintf('%s: %s is not inside selected root categories and will be excluded', $payload->getType(), $category['code']));
                unset($categories[$key]);
            }
        }

        //Remove excluded categories from kept categories
        foreach ($categories as $key => $category) {
            if (\in_array($category['code'], $excludedCategories, true)) {
                $this->logger->info(sprintf('%s: %s is explicitly excluded from configuration', $payload->getType(), $category['code']));
                unset($categories[$key]);
            }
        }

        $this->logger->info(Messages::totalExcludedFromImport($payload->getType(), \count($excludedCategories)));

        $this->logger->info(Messages::totalToImport($payload->getType(), \count($categories)));

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
        if (0 === \count($configuration->getRootCategories())) {
            return $keptCategories;
        }

        foreach ($configuration->getRootCategories() as $rootCategory) {
            $rootNode = $this->findParentNode($rootCategory, $categoriesTree);

            if (!\is_array($rootNode)) {
                return $keptCategories;
            }

            $this->getChildCodesFromParent($rootNode, $keptCategories);
            $keptCategories = array_unique($keptCategories);
        }

        return $keptCategories;
    }

    private function excludeNotImportedCategories(CategoryConfiguration $configuration, array &$categoriesTree): array
    {
        $excludedCategories = [];
        if (0 === \count($configuration->getNotImportCategories())) {
            return $excludedCategories;
        }

        foreach ($configuration->getNotImportCategories() as $notImportCategory) {
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
