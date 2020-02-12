<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Synolia\SyliusAkeneoPlugin\Entity\AkeneoCategoriesConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\CategoriesConfigurationType;
use Synolia\SyliusAkeneoPlugin\Repository\AkeneoCategoriesConfigurationRepository;

final class CategoriesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var AkeneoCategoriesConfigurationRepository|RepositoryInterface */
    private $akeneoCategoriesConfigurationRepository;

    public function __construct(EntityManagerInterface $entityManager, RepositoryInterface $akeneoCategoriesConfigurationRepository)
    {
        $this->entityManager = $entityManager;
        $this->akeneoCategoriesConfigurationRepository = $akeneoCategoriesConfigurationRepository;
    }

    public function __invoke(Request $request): Response
    {
        $categoriesConfigurations = null;
        if ($this->akeneoCategoriesConfigurationRepository instanceof AkeneoCategoriesConfigurationRepository) {
            $categoriesConfigurations = $this->akeneoCategoriesConfigurationRepository->getCategoriesConfiguration();
        }
        if ($categoriesConfigurations === null) {
            $categoriesConfigurations = new AkeneoCategoriesConfiguration();
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
