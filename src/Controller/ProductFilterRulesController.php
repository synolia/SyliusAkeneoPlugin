<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFiltersRulesType;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

final class ProductFilterRulesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    public function __construct(EntityManagerInterface $entityManager, ProductFiltersRulesRepository $productFiltersRulesRepository)
    {
        $this->entityManager = $entityManager;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
    }

    public function __invoke(Request $request): Response
    {
        $productfiltersRules = $this->productFiltersRulesRepository->getProductFiltersRules();
        if ($productfiltersRules === null) {
            $productfiltersRules = new ProductFiltersRules();
        }

        $form = $this->createForm(ProductFiltersRulesType::class, $productfiltersRules);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/filters_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
