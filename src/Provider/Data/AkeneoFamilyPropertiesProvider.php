<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Data;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias]
final class AkeneoFamilyPropertiesProvider implements AkeneoFamilyPropertiesProviderInterface
{
    private bool $loadsAllFamiliesAtOnce = false;

    private array $families = [];

    public function __construct(private AkeneoPimClientInterface $client)
    {
    }

    public function getProperties(string $familyCode): array
    {
        if (isset($this->families[$familyCode])) {
            return $this->families[$familyCode];
        }

        if ($this->loadsAllFamiliesAtOnce) {
            foreach ($this->client->getFamilyApi()->all() as $familyResource) {
                $this->families[$familyResource['code']] = $familyResource;
            }
        }

        if (!isset($this->families[$familyCode]) && !$this->loadsAllFamiliesAtOnce) {
            $this->families[$familyCode] = $this->client->getFamilyApi()->get($familyCode);
        }

        return $this->families[$familyCode];
    }
}
