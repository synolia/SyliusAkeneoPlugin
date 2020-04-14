<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductOption;

use Sylius\Component\Product\Model\ProductOption;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Option\DeleteTask;

final class DeleteTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
    }

    public function testDeleteOptionTask(): void
    {
        $this->createConfiguration();
        $optionsPayload = new OptionsPayload($this->createClient());

        /** @var \Sylius\Component\Resource\Factory\FactoryInterface $optionFactory */
        $optionFactory = self::$container->get('sylius.factory.product_option');
        /** @var ProductOption $option */
        $option = $optionFactory->createNew();
        $option->setCode('fakeCode');
        $option->setName('fakeName');
        $this->manager->persist($option);
        $this->manager->flush();

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Option\DeleteTask $deleteTask */
        $deleteTask = $this->taskProvider->get(DeleteTask::class);
        $deleteTask->__invoke($optionsPayload);

        $productOptionRepository = self::$container->get('sylius.repository.product_option');
        /** @var \Sylius\Component\Product\Model\ProductOptionInterface $productOption */
        $productOption = $productOptionRepository->findOneBy(['code' => 'fakeCode']);
        $this->assertNull($productOption);
    }

    private function createConfiguration(): void
    {
        $apiConfiguration = new ApiConfiguration();
        $apiConfiguration->setBaseUrl('');
        $apiConfiguration->setApiClientId('');
        $apiConfiguration->setApiClientSecret('');
        $apiConfiguration->setPaginationSize(100);
        $apiConfiguration->setIsEnterprise(true);
        $apiConfiguration->setUsername('');
        $apiConfiguration->setPassword('');
        $apiConfiguration->setChannel('');
        $this->manager->persist($apiConfiguration);
        $this->manager->flush();
    }
}
