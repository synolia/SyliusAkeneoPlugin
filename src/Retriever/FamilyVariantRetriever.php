<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class FamilyVariantRetriever
{
    private array $familyVariants = [];

    public function __construct(private AkeneoPimEnterpriseClientInterface $akeneoPimClient, private LoggerInterface $logger, private ApiConnectionProviderInterface $apiConnectionProvider)
    {
    }

    public function getVariants(string $familyCode): array
    {
        if (\array_key_exists($familyCode, $this->familyVariants)) {
            return $this->familyVariants[$familyCode];
        }

        $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

        try {
            $familyVariants = $this->akeneoPimClient->getFamilyVariantApi()->all($familyCode, $paginationSize);

            $this->familyVariants[$familyCode] = iterator_to_array($familyVariants);

            if (isset($this->familyVariants[$familyCode])) {
                return $this->familyVariants[$familyCode];
            }
        } catch (\Throwable $exception) {
            $this->logger->warning($exception->getMessage());
        }

        throw new \LogicException(sprintf('Unable to find variants for family "%s"', $familyCode));
    }
}
