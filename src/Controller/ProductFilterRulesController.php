<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleSimpleType;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

final class ProductFilterRulesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var EntityRepository */
    private $apiConfigurationRepository;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        EntityRepository $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
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

        $productFiltersRules = $this->productFiltersRulesRepository->getProductFiltersRules();
        if ($productFiltersRules === null) {
            $productFiltersRules = new ProductFiltersRules();
        }

        $simpleForm = $this->createForm(ProductFilterRuleSimpleType::class, $productFiltersRules);
        $simpleForm->handleRequest($request);

        $advancedForm = $this->createForm(ProductFilterRuleAdvancedType::class, $productFiltersRules);
        $advancedForm->handleRequest($request);

        if ($simpleForm->isSubmitted() && $simpleForm->isValid()) {
            $this->entityManager->persist($simpleForm->getData());
            $this->entityManager->flush();
        }

        if ($advancedForm->isSubmitted() && $advancedForm->isValid()) {
            $this->entityManager->persist($advancedForm->getData());
            $this->entityManager->flush();
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/filters_configuration.html.twig', [
            'simple_form' => $simpleForm->createView(),
            'advanced_form' => $advancedForm->createView(),
        ]);
    }
}
