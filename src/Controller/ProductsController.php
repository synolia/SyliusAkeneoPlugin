<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Synolia\SyliusAkeneoPlugin\Entity\ProductsConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductsConfigurationType;

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
        /** @var ProductsConfiguration $productsConfiguration */
        $productsConfiguration = $this->productsConfigurationRepository->findOneBy([]) ?? new ProductsConfiguration();

        $form = $this->createForm(ProductsConfigurationType::class, $productsConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProductsConfiguration $productsConfigurationData */
            $productsConfigurationData = $form->getData();

            $this->removeElements($productsConfiguration->getDefaultTax(), $productsConfigurationData->getDefaultTax());
            $this->removeElements($productsConfiguration->getConfigurable(), $productsConfigurationData->getConfigurable());
            $this->removeElements($productsConfiguration->getAkeneoImageAttributes(), $productsConfigurationData->getAkeneoImageAttributes());
            $this->removeElements($productsConfiguration->getProductImagesMapping(), $productsConfigurationData->getProductImagesMapping());

            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/products_configuration.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }

    private function removeElements(?Collection $productsConfiguration, ?Collection $productsConfigurationData): void
    {
        if ($productsConfiguration === null || $productsConfigurationData === null) {
            return;
        }

        foreach ($productsConfiguration as $defaultTax) {
            if (!array_search($defaultTax, $productsConfigurationData->toArray())) {
                $this->entityManager->remove($defaultTax);
            }
        }
    }
}
