<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor;

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
use Synolia\SyliusAkeneoPlugin\Checker\AttributeOwnerChecker;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Throwable;

abstract class AbstractImageProcessor
{
    private ImageUploaderInterface $imageUploader;

    private EntityManagerInterface $entityManager;

    private FactoryInterface $productImageFactory;

    protected LoggerInterface $akeneoLogger;

    protected ProductConfiguration $productConfiguration;

    private ClientFactory $clientFactory;

    private RepositoryInterface $productConfigurationRepository;
    private AttributeOwnerChecker $attributeOwnerChecker;

    public function __construct(
        ImageUploaderInterface $imageUploader,
        RepositoryInterface $productConfigurationRepository,
        EntityManagerInterface $entityManager,
        FactoryInterface $productImageFactory,
        LoggerInterface $akeneoLogger,
        ClientFactory $clientFactory,
        AttributeOwnerChecker $attributeOwnerChecker
    ) {
        $this->imageUploader = $imageUploader;
        $this->entityManager = $entityManager;
        $this->productImageFactory = $productImageFactory;
        $this->akeneoLogger = $akeneoLogger;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->clientFactory = $clientFactory;
        $this->attributeOwnerChecker = $attributeOwnerChecker;
    }

    protected function getProductConfiguration(): ProductConfiguration
    {
        if (isset($this->productConfiguration)) {
            return $this->productConfiguration;
        }

        $productConfiguration = $this->productConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if ($productConfiguration instanceof ProductConfiguration) {
            $this->productConfiguration = $productConfiguration;

            return $productConfiguration;
        }

        throw new \LogicException('');
    }

    /**
     * @param ProductInterface|ProductVariantInterface|mixed $object
     */
    protected function addImage($object, array $resource, Collection $imageAttributes): void
    {
        if (!$object instanceof ProductInterface && !$object instanceof ProductVariantInterface) {
            return;
        }

        foreach ($resource['values'] as $attributeCode => $images) {
            // Skip attribute if not part of the model
            if (!$this->attributeOwnerChecker->isAttributePartOfModel($resource, $attributeCode)) {
                $this->akeneoLogger->info('Skipped attribute insertion on product', [
                    'product' => $resource['code'] ?? $resource['identifier'],
                    'attribute_code' => $attributeCode,
                ]);
            }

            if (\in_array($attributeCode, array_map(fn ($imageAttribute) => $imageAttribute->getAkeneoAttributes(), $imageAttributes->toArray()), true)) {
                foreach ($images as $image) {
                    dd($image['data']); // 4/4/a/4/44a404c8c183ad722932dfd5032f56f4f6c9ff77_apollon.jpg
                    //TODO: find if already imported
                    $imageType = $this->getFileType((string) $attributeCode);
                    dd($imageType);

                    $syliusImages = $object->getImagesByType($imageType);

                    /** @var ImageInterface $syliusImage */
                    foreach ($syliusImages as $syliusImage) {
                        dd($syliusImage);
                    }

                    try {
                        //TODO: utiliser le chemin "data" comme sur akeneo pour faciliter la détection des images similaires
                        $imageResponse = $this->clientFactory
                            ->createFromApiCredentials()
                            ->getProductMediaFileApi()
                            ->download($image['data']);
                        $imageName = basename($image['data']);
                        $imagePath = sys_get_temp_dir() . '/' . $imageName;
                        file_put_contents($imagePath, $imageResponse->getBody()->getContents());
                        $uploadedImage = new UploadedFile($imagePath, $imageName);

                        /** @var ImageInterface $productImage */
                        $productImage = $this->productImageFactory->createNew();
                        $productImage->setFile($uploadedImage);
                        $productImage->setType($imageType);
                        $this->imageUploader->upload($productImage);

                        $object->addImage($productImage);

                        unlink($imagePath);
                    } catch (Throwable $throwable) {
                        $this->akeneoLogger->warning($throwable->getMessage());
                    }
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
        /** @var ImageInterface $image */
        foreach ($object->getImages() as $image) {
            $this->entityManager->remove($image);
        }
    }

    protected function getFileType(string $attributeCode): ?string
    {
        $repository = $this->entityManager->getRepository(ProductConfigurationImageMapping::class);
        /** @var ProductConfigurationImageMapping|null $mapping */
        $mapping = $repository->findOneBy(['akeneoAttribute' => $attributeCode]);

        return ($mapping instanceof ProductConfigurationImageMapping) ? $mapping->getSyliusAttribute() : null;
    }
}
