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
use Synolia\SyliusAkeneoPlugin\Entity\CategorieConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\CategoriesConfigurationType;
use Synolia\SyliusAkeneoPlugin\Repository\CategorieConfigurationRepository;

final class CategoriesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CategorieConfigurationRepository|RepositoryInterface */
    private $categoriesConfigurationRepository;

    /** @var RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $categoriesConfigurationRepository,
        RepositoryInterface $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->categoriesConfigurationRepository = $categoriesConfigurationRepository;
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

        $categoriesConfigurations = null;
        if ($this->categoriesConfigurationRepository instanceof CategorieConfigurationRepository) {
            $categoriesConfigurations = $this->categoriesConfigurationRepository->getCategoriesConfiguration();
        }
        if ($categoriesConfigurations === null) {
            $categoriesConfigurations = new CategorieConfiguration();
        }

        $form = $this->createForm(CategoriesConfigurationType::class, $categoriesConfigurations);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/categories.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }
}
