<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\DetectConfigurableProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\InitStockTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\InsertProductImagesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\RetrieveProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetProductsToWebsitesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetValuesToAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdateColumnNameTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdateColumnValuesForOptionTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdateConfigurablePricesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdateConfigurableProductsRelationTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdateFamilyTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdatePricesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\UpdateRelatedProductsTask;

final class ProductPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->pipe($this->taskProvider->get(RetrieveProductsTask::class))
            ->pipe($this->taskProvider->get(UpdateColumnNameTask::class))
            ->pipe($this->taskProvider->get(DetectConfigurableProductsTask::class))
            ->pipe($this->taskProvider->get(\Synolia\SyliusAkeneoPlugin\Task\Product\MatchPimCodeWithEntityTask::class))
            ->pipe($this->taskProvider->get(UpdateFamilyTask::class))
            ->pipe($this->taskProvider->get(UpdateColumnValuesForOptionTask::class))
            ->pipe($this->taskProvider->get(CreateProductEntitiesTask::class))
            ->pipe($this->taskProvider->get(SetValuesToAttributesTask::class))
            ->pipe($this->taskProvider->get(UpdateConfigurableProductsRelationTask::class))
            ->pipe($this->taskProvider->get(SetProductsToWebsitesTask::class))
            ->pipe($this->taskProvider->get(UpdatePricesTask::class))
            ->pipe($this->taskProvider->get(UpdateConfigurablePricesTask::class))
            ->pipe($this->taskProvider->get(InitStockTask::class))
            ->pipe($this->taskProvider->get(UpdateRelatedProductsTask::class))
            ->pipe($this->taskProvider->get(InsertProductImagesTask::class))
            ;
    }
}
