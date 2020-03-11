<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_website_mapping")
 */
final class ApiConfigurationWebsiteMapping implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration|null
     * @ORM\ManyToOne(
     *     targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration",
     *     inversedBy="websiteMappings"
     * )
     */
    private $apiConfiguration;

    /**
     * @var \Sylius\Component\Core\Model\ChannelInterface
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Core\Model\ChannelInterface", inversedBy="websiteMappings")
     */
    private $channel;

    /**
     * @SerializedName("akeneo_channel")
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $akeneoChannel;

    public function getId(): int
    {
        return $this->id;
    }

    public function getApiConfiguration(): ?ApiConfiguration
    {
        return $this->apiConfiguration;
    }

    public function setApiConfiguration(?ApiConfiguration $apiConfiguration): self
    {
        $this->apiConfiguration = $apiConfiguration;

        return $this;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(ChannelInterface $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getAkeneoChannel(): ?string
    {
        return $this->akeneoChannel;
    }

    public function setAkeneoChannel(string $akeneoChannel): self
    {
        $this->akeneoChannel = $akeneoChannel;

        return $this;
    }
}
