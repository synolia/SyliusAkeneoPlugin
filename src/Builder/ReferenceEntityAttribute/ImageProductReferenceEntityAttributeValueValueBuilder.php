<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Synolia\SyliusAkeneoPlugin\Model\Image;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ImageAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcher;

final class ImageProductReferenceEntityAttributeValueValueBuilder implements ProductReferenceEntityAttributeValueValueBuilderInterface
{
    /** @var AkeneoReferenceEntityAttributePropertiesProvider */
    private $akeneoReferenceEntityAttributePropertiesProvider;

    /** @var ReferenceEntityAttributeTypeMatcher */
    private $referenceEntityAttributeTypeMatcher;

    /** @var AkeneoPimEnterpriseClientInterface */
    private $akeneoPimEnterpriseClient;

    /** @var ImageUploaderInterface */
    private $imageUploader;

    public function __construct(
        AkeneoReferenceEntityAttributePropertiesProvider $akeneoReferenceEntityAttributePropertiesProvider,
        ReferenceEntityAttributeTypeMatcher $referenceEntityAttributeTypeMatcher,
        AkeneoPimEnterpriseClientInterface $akeneoPimEnterpriseClient,
        ImageUploaderInterface $imageUploader
    ) {
        $this->akeneoReferenceEntityAttributePropertiesProvider = $akeneoReferenceEntityAttributePropertiesProvider;
        $this->referenceEntityAttributeTypeMatcher = $referenceEntityAttributeTypeMatcher;
        $this->akeneoPimEnterpriseClient = $akeneoPimEnterpriseClient;
        $this->imageUploader = $imageUploader;
    }

    public function support(string $referenceEntityCode, string $subAttributeCode): bool
    {
        return $this->referenceEntityAttributeTypeMatcher->match(
            $this->akeneoReferenceEntityAttributePropertiesProvider->getType(
                $referenceEntityCode,
                $subAttributeCode
            )
        ) instanceof ImageAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build($value)
    {
        $imageResponse = $this->akeneoPimEnterpriseClient->getReferenceEntityMediaFileApi()->download((string) $value);
        $imageName = \basename($value);
        $imagePath = \sys_get_temp_dir() . '/' . $imageName;
        \file_put_contents($imagePath, $imageResponse->getBody()->getContents());
        $uploadedImage = new UploadedFile($imagePath, $imageName);
        $image = new Image();
        $image->setFile($uploadedImage);
        $this->imageUploader->upload($image);

        return \trim((string) $image->getPath());
    }
}
