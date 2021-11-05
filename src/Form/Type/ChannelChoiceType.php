<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\Api\ChannelApiInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;

final class ChannelChoiceType extends AbstractType
{
    /** @var ChannelApiInterface */
    private $channelApi;

    public function __construct(ClientFactoryInterface $clientFactory)
    {
        $this->channelApi = $clientFactory->createFromApiCredentials()->getChannelApi();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $channelApi = $this->channelApi->all();
        $channel = [];
        foreach ($channelApi as $item) {
            $channel[$item['code']] = $item['code'];
        }

        $resolver->setDefaults(['choices' => $channel]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
