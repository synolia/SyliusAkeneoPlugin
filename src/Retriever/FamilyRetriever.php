<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class FamilyRetriever implements FamilyRetrieverInterface
{
    /** @var array<string> */
    private array $familiesByVariant = [];

    private array $families = [];

    public function __construct(private AkeneoPimClientInterface $akeneoPimClient, private LoggerInterface $logger, private ApiConnectionProviderInterface $apiConnectionProvider)
    {
    }

    public function getFamily(string $familyCode): array
    {
        if (\array_key_exists($familyCode, $this->families)) {
            return $this->families[$familyCode];
        }

        $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

        $families = $this->akeneoPimClient->getFamilyApi()->all($paginationSize);

        /** @var array{code: string} $family */
        foreach ($families as $family) {
            $this->families[$family['code']] = $family;
        }

        return $this->families[$familyCode];
    }

    public function getFamilyCodeByVariantCode(string $familyVariantCode): string
    {
        if (\array_key_exists($familyVariantCode, $this->familiesByVariant)) {
            return $this->familiesByVariant[$familyVariantCode];
        }

        $paginationSize = $this->apiConnectionProvider->get()->getPaginationSize();

        try {
            $families = $this->akeneoPimClient->getFamilyApi()->all($paginationSize);

            /** @var array{code: string} $family */
            foreach ($families as $family) {
                if (!\array_key_exists($family['code'], $this->families)) {
                    $this->families[$family['code']] = $family;
                }

                $familyVariants = $this->akeneoPimClient->getFamilyVariantApi()->all($family['code'], $paginationSize);
                if (!$familyVariants->valid()) {
                    continue;
                }

                /** @var array{code: string} $familyVariant */
                foreach ($familyVariants as $familyVariant) {
                    $this->familiesByVariant[$familyVariant['code']] = $family['code'];
                }

                if (isset($this->familiesByVariant[$familyVariantCode])) {
                    return $family['code'];
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->warning($exception->getMessage(), [
                'exception' => $exception,
            ]);
        }

        throw new \LogicException(sprintf('Unable to find family for variant "%s"', $familyVariantCode));
    }
}
