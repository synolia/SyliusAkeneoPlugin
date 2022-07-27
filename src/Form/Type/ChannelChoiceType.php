<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;

final class ChannelChoiceType extends AbstractType
{
    private ClientFactoryInterface $clientFactory;

    public function __construct(ClientFactoryInterface $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $channelApi = $this->clientFactory->createFromApiCredentials()->getChannelApi()->all();
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
