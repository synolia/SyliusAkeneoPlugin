<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ApiConfigurationType;

final class AttributesController extends AbstractController
{
    public function configurationAction(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var ApiConfiguration $apiConfiguration */
        $apiConfiguration = $entityManager->getRepository(ApiConfiguration::class)->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            $apiConfiguration = new ApiConfiguration();
        }

        $form = $this->createForm(ApiConfigurationType::class, $apiConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($apiConfiguration);
            $entityManager->flush();

            return $this->redirectToRoute('sylius_akeneo_connector_api_configuration');
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/api_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
