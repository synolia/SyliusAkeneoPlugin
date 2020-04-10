<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductConfigurationType;

final class ProductsController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RepositoryInterface */
    private $productConfigurationRepository;

    /** @var RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productConfigurationRepository,
        RepositoryInterface $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->productConfigurationRepository = $productConfigurationRepository;
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

        /** @var ProductConfiguration $productConfiguration */
        $productConfiguration = $this->productConfigurationRepository->findOneBy([]) ?? new ProductConfiguration();

        $form = $this->createForm(ProductConfigurationType::class, $productConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProductConfiguration $productConfigurationData */
            $productConfigurationData = $form->getData();

            $this->removeElements($productConfiguration->getDefaultTax(), $productConfigurationData->getDefaultTax());
            $this->removeElements($productConfiguration->getConfigurable(), $productConfigurationData->getConfigurable());
            $this->removeElements($productConfiguration->getAkeneoImageAttributes(), $productConfigurationData->getAkeneoImageAttributes());
            $this->removeElements($productConfiguration->getProductImagesMapping(), $productConfigurationData->getProductImagesMapping());

            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/products_configuration.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }

    private function removeElements(?Collection $productConfiguration, ?Collection $productConfigurationData): void
    {
        if ($productConfiguration === null || $productConfigurationData === null) {
            return;
        }

        foreach ($productConfiguration as $defaultTax) {
            if (false === \array_search($defaultTax, $productConfigurationData->toArray(), true)) {
                $this->entityManager->remove($defaultTax);
            }
        }
    }
}
