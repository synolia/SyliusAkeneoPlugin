<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Synolia\SyliusAkeneoPlugin\Component\Cache\CacheKey;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class FamilyRetriever implements FamilyRetrieverInterface
{
    private array $families = [];

    private array $familiesByCode = [];

    private array $familiesByVariantCode = [];

    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private LoggerInterface $akeneoLogger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private CacheInterface $akeneoFamilies,
        private CacheInterface $akeneoFamily,
        private FamilyVariantRetriever $familyVariantRetriever,
    ) {
    }

    public function getFamilies(): array
    {
        if ($this->families !== []) {
            return $this->families;
        }

        /** @phpstan-ignore-next-line */
        return $this->families = $this->akeneoFamilies->get(CacheKey::FAMILIES, function (): array {
            $families = [];

            $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

            $results = $this->akeneoPimClient->getFamilyApi()->all($paginationSize);

            /** @var array{code: string} $result */
            foreach ($results as $result) {
                $families[$result['code']] = $result;
            }

            return $families;
        });
    }

    public function getFamily(string $familyCode): array
    {
        if (array_key_exists($familyCode, $this->familiesByCode)) {
            return $this->familiesByCode[$familyCode];
        }

        /** @phpstan-ignore-next-line */
        return $this->familiesByCode[$familyCode] = $this->akeneoFamily->get(\sprintf(CacheKey::FAMILY, $familyCode), function () use ($familyCode): array {
            return $this->getFamilies()[$familyCode];
        });
    }

    public function getFamilyCodeByVariantCode(string $familyVariantCode): string
    {
        if (array_key_exists($familyVariantCode, $this->familiesByVariantCode)) {
            return $this->familiesByVariantCode[$familyVariantCode];
        }

        /** @phpstan-ignore-next-line */
        return $this->familiesByVariantCode[$familyVariantCode] = $this->akeneoFamily->get(\sprintf(CacheKey::FAMILY_BY_VARIANT_CODE, $familyVariantCode), function () use ($familyVariantCode): string {
            try {
                /** @var array{code: string} $family */
                foreach ($this->getFamilies() as $family) {
                    /** @var array{code: string} $familyVariant */
                    foreach ($this->familyVariantRetriever->getVariants($family['code']) as $familyVariant) {
                        if ($familyVariant['code'] === $familyVariantCode) {
                            return $family['code'];
                        }
                    }
                }
            } catch (\Throwable $exception) {
                $this->akeneoLogger->warning($exception->getMessage(), [
                    'exception' => $exception,
                ]);
            }

            throw new \LogicException(sprintf('Unable to find family for variant "%s"', $familyVariantCode));
        });
    }
}
