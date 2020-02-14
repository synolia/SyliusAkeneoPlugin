<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Synolia\SyliusAkeneoPlugin\Entity\ProductsConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\ProductsConfigurationType;

final class ProductsController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RepositoryInterface */
    private $productsConfigurationRepository;

    public function __construct(EntityManagerInterface $entityManager, RepositoryInterface $productsConfigurationRepository)
    {
        $this->entityManager = $entityManager;
        $this->productsConfigurationRepository = $productsConfigurationRepository;
    }

    public function __invoke(Request $request): Response
    {
        $productsConfiguration = new ProductsConfiguration();

        $result = $this->productsConfigurationRepository->findAll();
        if (!empty($result)) {
            /** @var ProductsConfiguration $productsConfiguration */
            $productsConfiguration = $result[0];
        }

        $form = $this->createForm(ProductsConfigurationType::class, $productsConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProductsConfiguration $productsConfigurationData */
            $productsConfigurationData = $form->getData();
            foreach ($productsConfiguration->getDefaultTax() as $defaultTax) {
                if (!array_search($defaultTax, $productsConfigurationData->getDefaultTax()->toArray())) {
                    $this->entityManager->remove($defaultTax);
                }
            }
            foreach ($productsConfiguration->getConfigurable() as $attribute) {
                if (!array_search($attribute, $productsConfigurationData->getConfigurable()->toArray())) {
                    $this->entityManager->remove($attribute);
                }
            }

            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/products.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }
}
