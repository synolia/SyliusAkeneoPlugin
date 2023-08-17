<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Synolia\SyliusAkeneoPlugin\Component\Cache\CacheKey;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class FamilyVariantRetriever
{
    private array $variantsByFamily = [];

    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private LoggerInterface $logger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private CacheInterface $akeneoFamilyVariants,
    ) {
    }

    public function getVariants(string $familyCode): array
    {
        if ($this->variantsByFamily !== []) {
            return $this->variantsByFamily[$familyCode];
        }

        return $this->variantsByFamily[$familyCode] = $this->akeneoFamilyVariants->get(\sprintf(CacheKey::FAMILY_VARIANTS, $familyCode), function () use ($familyCode): array {
            $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

            try {
                $results = $this->akeneoPimClient->getFamilyVariantApi()->all($familyCode, $paginationSize);
                $familyVariants = iterator_to_array($results);
            } catch (\Throwable $exception) {
                $this->logger->warning($exception->getMessage());

                return [];
            }

            return $familyVariants;
        });
    }
}
