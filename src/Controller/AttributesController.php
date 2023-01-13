<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Form\Type\AttributesTypeMappingType;
use Synolia\SyliusAkeneoPlugin\Manager\SettingsManagerInterface;
use Synolia\SyliusAkeneoPlugin\Model\SettingType;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class AttributesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManagerInterface $settingsManager,
        private RepositoryInterface $attributeTypeMappingRepository,
        private RepositoryInterface $attributeAkeneoSyliusMappingRepository,
        private TranslatorInterface $translator,
        private ApiConnectionProviderInterface $apiConnectionProvider,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->apiConnectionProvider->get();
        } catch (ApiNotConfiguredException) {
            $request->getSession()->getFlashBag()->add('error', $this->translator->trans('sylius.ui.admin.akeneo.not_configured_yet'));

            return $this->redirectToRoute('sylius_akeneo_connector_api_configuration');
        }

        /** @var AttributeTypeMapping[] $attributeTypeMappings */
        $attributeTypeMappings = $this->attributeTypeMappingRepository->findAll();

        /** @var AttributeAkeneoSyliusMapping[] $attributeAkeneoSyliusMappings */
        $attributeAkeneoSyliusMappings = $this->attributeAkeneoSyliusMappingRepository->findAll();

        $settings = ['import_referential_attributes' => SettingType::AKENEO_SETTINGS['import_referential_attributes']];
        foreach ($settings as $key => $value) {
            $settings[$key] = $this->settingsManager->get($key);
        }

        $form = $this->createForm(
            AttributesTypeMappingType::class,
            [
                AttributesTypeMappingType::ATTRIBUTE_TYPE_MAPPINGS_CODE => $attributeTypeMappings,
                AttributesTypeMappingType::ATTRIBUTE_AKENEO_SYLIUS_MAPPINGS_CODE => $attributeAkeneoSyliusMappings,
                'settings' => $settings,
            ],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attributes = $form->getData();

            $this->removeRemovedMappedItemsFromFormRequest($attributes, $attributeTypeMappings, $attributeAkeneoSyliusMappings);
            $this->addMappedItemsFromFormRequest($attributes);

            foreach ($attributes['settings'] as $name => $value) {
                $this->settingsManager->set($name, $value);
            }

            $this->entityManager->flush();
            $request->getSession()->getFlashBag()->add('success', $this->translator->trans('akeneo.ui.admin.changes_successfully_saved'));

            return $this->redirectToRoute('sylius_akeneo_connector_attributes');
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/attributes_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function removeRemovedMappedItemsFromFormRequest(
        array $attributes,
        array $attributeTypeMappings,
        array $attributeAkeneoSyliusMappings,
    ): void {
        foreach ($attributeTypeMappings as $attributeTypeMapping) {
            if (false === array_search($attributeTypeMapping, $attributes[AttributesTypeMappingType::ATTRIBUTE_TYPE_MAPPINGS_CODE], true)) {
                $this->entityManager->remove($attributeTypeMapping);
            }
        }

        foreach ($attributeAkeneoSyliusMappings as $attributeAkeneoSyliusMapping) {
            if (false === array_search($attributeAkeneoSyliusMapping, $attributes[AttributesTypeMappingType::ATTRIBUTE_AKENEO_SYLIUS_MAPPINGS_CODE], true)) {
                $this->entityManager->remove($attributeAkeneoSyliusMapping);
            }
        }
    }

    private function addMappedItemsFromFormRequest(array $attributes): void
    {
        foreach ($attributes[AttributesTypeMappingType::ATTRIBUTE_TYPE_MAPPINGS_CODE] as $attributeTypeMapping) {
            $this->entityManager->persist($attributeTypeMapping);
        }

        foreach ($attributes[AttributesTypeMappingType::ATTRIBUTE_AKENEO_SYLIUS_MAPPINGS_CODE] as $attributeAkeneoSyliusMapping) {
            $this->entityManager->persist($attributeAkeneoSyliusMapping);
        }
    }
}
