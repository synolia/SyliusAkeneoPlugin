<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Form\Type\CategoriesConfigurationType;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\CategoryConfigurationRepository;

final class CategoriesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryConfigurationRepository $categoriesConfigurationRepository,
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
        $categoriesConfigurations = $this->categoriesConfigurationRepository->getCategoriesConfiguration();
        if (!$categoriesConfigurations instanceof \Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration) {
            $categoriesConfigurations = new CategoryConfiguration();
        }

        $form = $this->createForm(CategoriesConfigurationType::class, $categoriesConfigurations);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();

            $request->getSession()->getFlashBag()->add('success', $this->translator->trans('akeneo.ui.admin.changes_successfully_saved'));
        }

        return $this->render(
            '@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/categories.html.twig',
            [
                'form' => $form,
            ],
        );
    }
}
