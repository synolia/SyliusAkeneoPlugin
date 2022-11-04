<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductConfigurationType;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

final class ProductsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private RepositoryInterface $productConfigurationRepository;

    private TranslatorInterface $translator;

    private ApiConnectionProviderInterface $apiConnectionProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productConfigurationRepository,
        TranslatorInterface $translator,
        ApiConnectionProviderInterface $apiConnectionProvider
    ) {
        $this->entityManager = $entityManager;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->translator = $translator;
        $this->apiConnectionProvider = $apiConnectionProvider;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->apiConnectionProvider->get();
        } catch (ApiNotConfiguredException $apiNotConfiguredException) {
            $request->getSession()->getFlashBag()->add('error', $this->translator->trans('sylius.ui.admin.akeneo.not_configured_yet'));

            return $this->redirectToRoute('sylius_akeneo_connector_api_configuration');
        }

        /** @var ProductConfiguration $productConfiguration */
        $productConfiguration = $this->productConfigurationRepository->findOneBy([], ['id' => 'DESC']) ?? new ProductConfiguration();

        $form = $this->createForm(ProductConfigurationType::class, $productConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProductConfiguration $productConfigurationData */
            $productConfigurationData = $form->getData();

            $this->removeElements($productConfiguration->getAkeneoImageAttributes(), $productConfigurationData->getAkeneoImageAttributes());
            $this->removeElements($productConfiguration->getProductImagesMapping(), $productConfigurationData->getProductImagesMapping());

            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();

            $request->getSession()->getFlashBag()->add('success', $this->translator->trans('akeneo.ui.admin.changes_successfully_saved'));
        }

        return $this->render(
            '@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/products_configuration.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    private function removeElements(?Collection $productConfiguration, ?Collection $productConfigurationData): void
    {
        if (null === $productConfiguration || null === $productConfigurationData) {
            return;
        }

        foreach ($productConfiguration as $defaultTax) {
            if (false === array_search($defaultTax, $productConfigurationData->toArray(), true)) {
                $this->entityManager->remove($defaultTax);
            }
        }
    }
}
