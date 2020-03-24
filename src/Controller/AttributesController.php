<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
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

    /** @var EntityRepository */
    private $attributeTypeMappingRepository;

    /** @var EntityRepository */
    private $apiConfigurationRepository;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        SettingsManagerInterface $settingsManager,
        EntityRepository $attributeTypeMappingRepository,
        EntityRepository $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->settingsManager = $settingsManager;
        $this->attributeTypeMappingRepository = $attributeTypeMappingRepository;
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

        $settings = ['import_referential_attributes' => SettingType::AKENEO_SETTINGS['import_referential_attributes']];
        foreach ($settings as $key => $value) {
            $settings[$key] = $this->settingsManager->get($key);
        }

        $form = $this->createForm(
            AttributesTypeMappingType::class, [
                'mappings' => $attributeTypeMappings,
                'settings' => $settings,
            ],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attributes = $form->getData();

            //Remove removed items from array
            foreach ($attributeTypeMappings as $attributeTypeMapping) {
                if (!\array_search($attributeTypeMapping, $attributes['mappings'], true)) {
                    $this->entityManager->remove($attributeTypeMapping);
                }
            }

            //Add / edit newly added items
            foreach ($attributes['mappings'] as $attributeTypeMapping) {
                $this->entityManager->persist($attributeTypeMapping);
            }

            foreach ($attributes['settings'] as $name => $value) {
                $this->settingsManager->set($name, $value);
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('sylius_akeneo_connector_attributes');
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/attributes_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
