<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Exceptions\Retriever\FamilyMeasureNotFoundException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Retriever\MeasurableNotFoundException;

class FamilyMeasureRetriever
{
    private ?AkeneoPimClientInterface $client = null;

    private array $measuresFamilies = [];

    public function __construct(private ClientFactory $clientFactory)
    {
    }

    /**
     * @throws FamilyMeasureNotFoundException
     */
    public function get(string $measureCode): array
    {
        if (null === $this->client) {
            $this->client = $this->clientFactory->createFromApiCredentials();
        }

        if (array_key_exists($measureCode, $this->measuresFamilies)) {
            return $this->measuresFamilies[$measureCode];
        }

        $measurementFamilies = $this->client->getMeasureFamilyApi()->get($measureCode);

        foreach ($measurementFamilies as $measurementFamily) {
            $this->measuresFamilies[$measureCode] = $measurementFamily;
        }

        if (array_key_exists($measureCode, $this->measuresFamilies)) {
            return $this->measuresFamilies[$measureCode];
        }

        throw new FamilyMeasureNotFoundException(\sprintf(
            'Measure family %s could not be found on Akeneo',
            $measureCode,
        ));
    }

    /**
     * @throws MeasurableNotFoundException|FamilyMeasureNotFoundException
     */
    public function getMeasurable(string $measureCode, string $measurableCode): array
    {
        foreach ($this->get($measureCode) as $measurable) {
            if ($measurable['code'] === $measurableCode) {
                return $measurable;
            }
        }

        throw new MeasurableNotFoundException(\sprintf(
            'Measurable %s could not be found on Akeneo for family %s',
            $measurableCode,
            $measureCode,
        ));
    }
}
