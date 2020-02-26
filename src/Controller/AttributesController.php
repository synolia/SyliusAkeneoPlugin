<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;
use Synolia\SyliusAkeneoPlugin\Form\Type\AttributesTypeMappingType;
use Synolia\SyliusAkeneoPlugin\Manager\SettingsManagerInterface;
use Synolia\SyliusAkeneoPlugin\Model\SettingType;

final class AttributesController extends AbstractController
{
    /** @var \Synolia\SyliusAkeneoPlugin\Manager\SettingsManagerInterface */
    private $settingsManager;

    public function __construct(SettingsManagerInterface $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function __invoke(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var AttributeTypeMapping[] $attributeTypeMappings */
        $attributeTypeMappings = $entityManager->getRepository(AttributeTypeMapping::class)->findAll();

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
                    $entityManager->remove($attributeTypeMapping);
                }
            }

            //Add / edit newly added items
            foreach ($attributes['mappings'] as $attributeTypeMapping) {
                $entityManager->persist($attributeTypeMapping);
            }

            foreach ($attributes['settings'] as $name => $value) {
                $this->settingsManager->set($name, $value);
            }

            $entityManager->flush();

            return $this->redirectToRoute('sylius_akeneo_connector_attributes');
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/attributes_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
