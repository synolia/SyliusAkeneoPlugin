<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class InsertProductImagesTask implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Core\Uploader\ImageUploaderInterface */
    private $imageUploader;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productConfigurationRepository;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productImageFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration */
    private $configuration;

    public function __construct(
        ImageUploaderInterface $imageUploader,
        RepositoryInterface $productConfigurationRepository,
        EntityManagerInterface $entityManager,
        FactoryInterface $productImageFactory
    ) {
        $this->imageUploader = $imageUploader;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->entityManager = $entityManager;
        $this->productImageFactory = $productImageFactory;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        try {
            $this->entityManager->beginTransaction();

            /** @var \Sylius\Component\Core\Model\ProductInterface $product */
            $product = $payload->getProduct();

            if (!$product instanceof ProductInterface) {
                return $payload;
            }

            foreach ($product->getImages() as $image) {
                $this->entityManager->remove($image);
            }

            $configuration = $this->productConfigurationRepository->findOneBy([]);

            if (!$configuration instanceof ProductConfiguration) {
                return $payload;
            }

            $this->configuration = $configuration;

            $imageAttributes = $this->configuration->getAkeneoImageAttributes();

            if ($imageAttributes === null) {
                return $payload;
            }

            foreach ($payload->getAttributes() as $attributeCode => $images) {
                if (\in_array($attributeCode, array_map(function ($imageAttribute) {
                    return $imageAttribute->getAkeneoAttributes();
                }, $imageAttributes->toArray()), true)) {
                    foreach ($images as $image) {
                        try {
                            $imageResponse = $payload->getAkeneoPimClient()->getProductMediaFileApi()->download($image['data']);
                            $imageName = \basename($image['data']);
                            $imagePath = \sys_get_temp_dir() . '/' . $imageName;
                            \file_put_contents($imagePath, $imageResponse->getBody()->getContents());
                            $uploadedImage = new UploadedFile($imagePath, $imageName);

                            /** @var ImageInterface $productImage */
                            $productImage = $this->productImageFactory->createNew();
                            $productImage->setFile($uploadedImage);
                            $productImage->setType($this->getFileType((string) $attributeCode));
                            $this->imageUploader->upload($productImage);

                            $product->addImage($productImage);
                        } catch (\Throwable $throwable) {
                            //TODO: Log error when logger will be implemented.
                        }
                    }
                }
            }
        } catch (\Throwable $throwable) {
        }

        return $payload;
    }

    private function getFileType(string $attributeCode): ?string
    {
        $repository = $this->entityManager->getRepository(ProductConfigurationImageMapping::class);
        /** @var ProductConfigurationImageMapping|null $mapping */
        $mapping = $repository->findOneBy(['akeneoAttribute' => $attributeCode]);

        return ($mapping instanceof ProductConfigurationImageMapping) ? $mapping->getSyliusAttribute() : null;
    }
}
