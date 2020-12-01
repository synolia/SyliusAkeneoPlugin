<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayloadInterface;

abstract class AbstractInsertProductImageTask
{
    /** @var \Sylius\Component\Core\Uploader\ImageUploaderInterface */
    protected $imageUploader;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    protected $productConfigurationRepository;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    protected $productImageFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration */
    protected $configuration;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        ImageUploaderInterface $imageUploader,
        RepositoryInterface $productConfigurationRepository,
        EntityManagerInterface $entityManager,
        FactoryInterface $productImageFactory,
        LoggerInterface $akeneoLogger
    ) {
        $this->imageUploader = $imageUploader;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->entityManager = $entityManager;
        $this->productImageFactory = $productImageFactory;
        $this->logger = $akeneoLogger;
    }

    /**
     * @param mixed $object
     */
    protected function addImage(ProductMediaPayloadInterface $payload, $object, Collection $imageAttributes): void
    {
        if (!$object instanceof ProductInterface && !$object instanceof ProductVariantInterface) {
            return;
        }

        foreach ($payload->getAttributes() as $attributeCode => $images) {
            if (\in_array($attributeCode, array_map(static function ($imageAttribute) {
                return $imageAttribute->getAkeneoAttributes();
            }, $imageAttributes->toArray()), true)) {
                foreach ($images as $image) {
                    $this->downloadImage($payload, $image, $attributeCode, $object);
                }
            }
        }
    }

    /**
     * @param mixed $object
     */
    protected function cleanImages($object): void
    {
        if (!$object instanceof ProductInterface && !$object instanceof ProductVariantInterface) {
            return;
        }
        foreach ($object->getImages() as $image) {
            $this->entityManager->remove($image);
        }
    }

    protected function getFileType(string $attributeCode): ?string
    {
        $repository = $this->entityManager->getRepository(ProductConfigurationImageMapping::class);
        /** @var ProductConfigurationImageMapping|null $mapping */
        $mapping = $repository->findOneBy(['akeneoAttribute' => $attributeCode]);

        return $mapping instanceof ProductConfigurationImageMapping ? $mapping->getSyliusAttribute() : null;
    }

    /**
     * @param ProductInterface|ProductVariantInterface $product
     */
    private function downloadImage(
        ProductMediaPayloadInterface $payload,
        string $image,
        int $attributeCode,
        $product
    ): void {
        try {
            $imageResponse = $payload->getAkeneoPimClient()->getProductMediaFileApi()->download($image);
            $imageName = \basename($image);
            $imagePath = \sys_get_temp_dir() . '/' . $imageName;
            \file_put_contents($imagePath, $imageResponse->getBody()->getContents());
            $uploadedImage = new UploadedFile($imagePath, $imageName);

            /** @var ImageInterface $productImage */
            $productImage = $this->productImageFactory->createNew();
            $productImage->setFile($uploadedImage);
            $productImage->setType($this->getFileType((string) $attributeCode));
            $this->imageUploader->upload($productImage);

            $product->addImage($productImage);

            \unlink($imagePath);
        } catch (\Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }
    }
}
