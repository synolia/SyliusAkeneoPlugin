<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleSimpleType;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

final class ProductFilterRulesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductFiltersRulesRepository $productFiltersRulesRepository,
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

        $productFiltersRules = $this->productFiltersRulesRepository->getProductFiltersRules();
        if (null === $productFiltersRules) {
            $productFiltersRules = new ProductFiltersRules();
        }

        $simpleForm = $this->createForm(ProductFilterRuleSimpleType::class, $productFiltersRules);
        $simpleForm->handleRequest($request);

        $advancedForm = $this->createForm(ProductFilterRuleAdvancedType::class, $productFiltersRules);
        $advancedForm->handleRequest($request);

        if ($simpleForm->isSubmitted() && $simpleForm->isValid()) {
            $this->update($request, $simpleForm);
        }

        if ($advancedForm->isSubmitted() && $advancedForm->isValid()) {
            $this->update($request, $advancedForm);
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/filters_configuration.html.twig', [
            'simple_form' => $simpleForm->createView(),
            'advanced_form' => $advancedForm->createView(),
        ]);
    }

    private function update(Request $request, FormInterface $form): void
    {
        $this->entityManager->persist($form->getData());
        $this->entityManager->flush();

        $request->getSession()->getFlashBag()->add('success', $this->translator->trans('akeneo.ui.admin.changes_successfully_saved'));
    }
}
