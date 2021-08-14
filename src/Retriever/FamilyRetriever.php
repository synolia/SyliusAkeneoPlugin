<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;

final class FamilyRetriever
{
    /** @var array<string> */
    private $familiesByVariant = [];

    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    private $akeneoPimClient;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AkeneoPimEnterpriseClientInterface $akeneoPimClient,
        ConfigurationProvider $configurationProvider,
        LoggerInterface $logger
    ) {
        $this->akeneoPimClient = $akeneoPimClient;
        $this->configurationProvider = $configurationProvider;
        $this->logger = $logger;
    }

    public function getFamilyCodeByVariantCode(string $familyVariantCode): string
    {
        if (array_key_exists($familyVariantCode, $this->familiesByVariant)) {
            return $this->familiesByVariant[$familyVariantCode];
        }

        $paginationSize = $this->configurationProvider->getConfiguration()->getPaginationSize();

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
