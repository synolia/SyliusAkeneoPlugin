<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class FamilyRetriever implements FamilyRetrieverInterface
{
    /** @var array<string> */
    private array $familiesByVariant = [];

    private AkeneoPimEnterpriseClientInterface $akeneoPimClient;

    private LoggerInterface $logger;

    private ApiConnectionProviderInterface $apiConnectionProvider;

    public function __construct(
        AkeneoPimEnterpriseClientInterface $akeneoPimClient,
        LoggerInterface $logger,
        ApiConnectionProviderInterface $apiConnectionProvider
    ) {
        $this->akeneoPimClient = $akeneoPimClient;
        $this->logger = $logger;
        $this->apiConnectionProvider = $apiConnectionProvider;
    }

    public function getFamilyCodeByVariantCode(string $familyVariantCode): string
    {
        if (\array_key_exists($familyVariantCode, $this->familiesByVariant)) {
            return $this->familiesByVariant[$familyVariantCode];
        }

        $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

        try {
            $families = $this->akeneoPimClient->getFamilyApi()->all($paginationSize);

            foreach ($families as $family) {
                $familyVariants = $this->akeneoPimClient->getFamilyVariantApi()->all($family['code'], $paginationSize);
                if (!$familyVariants->valid()) {
                    continue;
                }

                foreach ($familyVariants as $familyVariant) {
                    $this->familiesByVariant[$familyVariant['code']] = $family['code'];
                }

                if (isset($this->familiesByVariant[$familyVariantCode])) {
                    return $family['code'];
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->warning($exception->getMessage());
        }

        throw new \LogicException(sprintf('Unable to find family for variant "%s"', $familyVariantCode));
    }
}
