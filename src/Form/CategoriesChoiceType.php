<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Mock\AkeneoMock;

final class CategoriesChoiceType extends AbstractType
{
    private const CATEGORY_PER_PAGE = 1000;

    /** @var AkeneoMock */
    private $akeneoMock;

    /** @var CategoryApiInterface */
    private $categoryApi;

    /** @var ChannelContextInterface */
    private $channelContext;

    public function __construct(AkeneoMock $akeneoMock, ChannelContextInterface $channelContext)
    {
        $this->akeneoMock = $akeneoMock;

        $this->akeneoMock->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK)
            )
        );

        $this->categoryApi = $this->akeneoMock->createClient()->getCategoryApi();
        $this->channelContext = $channelContext;
    }

    private function getCategories(): string
    {
        return $this->akeneoMock->getFileContent('categories.json');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $categories = [];
        /** @var Channel $channelContext */
        $channelContext = $this->channelContext->getChannel();
        /** @var LocaleInterface $locale */
        $locale = $channelContext->getLocales()[0];

        foreach ($this->categoryApi->listPerPage(self::CATEGORY_PER_PAGE)->getItems() as $item) {
            $categories[$item['labels'][$locale->getCode()]] = $item['code'];
        }

        $resolver->setDefaults(['choices' => $categories]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
