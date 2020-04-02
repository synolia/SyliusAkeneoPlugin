<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;
use Synolia\SyliusAkeneoPlugin\Form\Type\AttributesTypeMappingType;
use Synolia\SyliusAkeneoPlugin\Manager\SettingsManagerInterface;
use Synolia\SyliusAkeneoPlugin\Model\SettingType;

final class AttributesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Manager\SettingsManagerInterface */
    private $settingsManager;

    /** @var RepositoryInterface */
    private $attributeTypeMappingRepository;

    /** @var RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var TranslatorInterface */
    private $translator;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $attributeAkeneoSyliusMappingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SettingsManagerInterface $settingsManager,
        RepositoryInterface $attributeTypeMappingRepository,
        RepositoryInterface $attributeAkeneoSyliusMappingRepository,
        RepositoryInterface $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->settingsManager = $settingsManager;
        $this->attributeTypeMappingRepository = $attributeTypeMappingRepository;
        $this->attributeAkeneoSyliusMappingRepository = $attributeAkeneoSyliusMappingRepository;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    public function __invoke(Request $request): Response
    {
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);
        if (!$apiConfiguration instanceof ApiConfiguration) {
            $this->flashBag->add('error', $this->translator->trans('sylius.ui.admin.akeneo.not_configured_yet'));

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
            AttributesTypeMappingType::class, [
                AttributesTypeMappingType::TYPE_MAPPINGS_CODE => $attributeTypeMappings,
                AttributesTypeMappingType::AKENEO_SYLIUS_MAPPINGS_CODE => $attributeAkeneoSyliusMappings,
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
            $this->flashBag->add('success', $this->translator->trans('akeneo.ui.admin.changes_successfully_saved'));

            return $this->redirectToRoute('sylius_akeneo_connector_attributes');
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/attributes_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function removeRemovedMappedItemsFromFormRequest(
        array $attributes,
        array $attributeTypeMappings,
        array $attributeAkeneoSyliusMappings
    ): void {
        foreach ($attributeTypeMappings as $attributeTypeMapping) {
            if (!\array_search($attributeTypeMapping, $attributes[AttributesTypeMappingType::TYPE_MAPPINGS_CODE], true)) {
                $this->entityManager->remove($attributeTypeMapping);
            }
        }

        foreach ($attributeAkeneoSyliusMappings as $attributeAkeneoSyliusMapping) {
            if (!\array_search($attributeAkeneoSyliusMapping, $attributes[AttributesTypeMappingType::AKENEO_SYLIUS_MAPPINGS_CODE], true)) {
                $this->entityManager->remove($attributeAkeneoSyliusMapping);
            }
        }
    }

    private function addMappedItemsFromFormRequest(array $attributes): void
    {
        foreach ($attributes[AttributesTypeMappingType::TYPE_MAPPINGS_CODE] as $attributeTypeMapping) {
            $this->entityManager->persist($attributeTypeMapping);
        }

        foreach ($attributes[AttributesTypeMappingType::AKENEO_SYLIUS_MAPPINGS_CODE] as $attributeAkeneoSyliusMapping) {
            $this->entityManager->persist($attributeAkeneoSyliusMapping);
        }
    }
}
