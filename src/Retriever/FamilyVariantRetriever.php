<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Synolia\SyliusAkeneoPlugin\Component\Cache\CacheKey;
use Synolia\SyliusAkeneoPlugin\Exceptions\Retriever\FamilyVariantNotFountException;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class FamilyVariantRetriever implements FamilyVariantRetrieverInterface
{
    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private LoggerInterface $akeneoLogger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private CacheInterface $akeneoFamilyVariants,
    ) {
    }

    public function getVariants(string $familyCode): array
    {
        /** @phpstan-ignore-next-line */
        return $this->akeneoFamilyVariants->get(\sprintf(CacheKey::FAMILY_VARIANTS, $familyCode), function () use ($familyCode): array {
            $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

            try {
                $results = $this->akeneoPimClient->getFamilyVariantApi()->all($familyCode, $paginationSize);
                $familyVariants = iterator_to_array($results);
            } catch (\Throwable $exception) {
                $this->akeneoLogger->warning($exception->getMessage());

                return [];
            }

            return $familyVariants;
        });
    }

    /**
     * @throws FamilyVariantNotFountException
     */
    public function getVariant(string $familyCode, string $familyVariantCode): array
    {
        $familyVariants = $this->getVariants($familyCode);

        foreach ($familyVariants as $familyVariant) {
            if ($familyVariant['code'] !== $familyVariantCode) {
                continue;
            }

            return $familyVariant;
        }

        throw new FamilyVariantNotFountException('Could not determine variant');
    }
}
