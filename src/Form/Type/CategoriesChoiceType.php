<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;

final class CategoriesChoiceType extends AbstractType
{
    /** @var CategoryApiInterface */
    private $categoryApi;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var ClientFactory */
    private $clientFactory;

    public function __construct(ClientFactory $clientFactory, ChannelContextInterface $channelContext)
    {
        $this->clientFactory = $clientFactory;
        $this->categoryApi = $this->clientFactory->createFromApiCredentials()->getCategoryApi();
        $this->channelContext = $channelContext;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $categories = [];
        /** @var Channel $channelContext */
        $channelContext = $this->channelContext->getChannel();
        /** @var LocaleInterface $locale */
        $locale = $channelContext->getDefaultLocale();

        $categoryApi = $this->categoryApi->all();
        foreach ($categoryApi as $item) {
            if (isset($item['labels'][$locale->getCode()])) {
                $categories[$item['labels'][$locale->getCode()]] = $item['code'];

                continue;
            }
            $categories[$item['code']] = $item['code'];
        }

        $resolver->setDefaults(['choices' => $categories]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
