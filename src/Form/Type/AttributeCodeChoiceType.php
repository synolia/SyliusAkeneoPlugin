<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

final class AttributeCodeChoiceType extends AbstractType
{
    private AkeneoPimEnterpriseClientInterface $akeneoPimClient;

    private AkeneoTaskProvider $akeneoTaskProvider;

    private LocaleContextInterface $localeContext;

    public function __construct(
        ClientFactory $clientFactory,
        AkeneoTaskProvider $akeneoTaskProvider,
        LocaleContextInterface $localeContext
    ) {
        $this->akeneoPimClient = $clientFactory->createFromApiCredentials();
        $this->akeneoTaskProvider = $akeneoTaskProvider;
        $this->localeContext = $localeContext;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $payload = new AttributePayload($this->akeneoPimClient);
        $attributeResult = $this->akeneoTaskProvider->get(RetrieveAttributesTask::class);
        /** @var AttributePayload $attributePayload */
        $attributePayload = $attributeResult->__invoke($payload);

        if (!$attributePayload->getResources() instanceof ResourceCursorInterface) {
            return;
        }

        $attributes = [];
        foreach ($attributePayload->getResources() as $attributeResource) {
            $attributes[($attributeResource['labels'][$this->localeContext->getLocaleCode()]) ?? \current($attributeResource['labels'])] = $attributeResource['code'];
        }

        $resolver->setDefaults([
            'multiple' => false,
            'choices' => $attributes,
            'required' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
