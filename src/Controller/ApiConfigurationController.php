<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ApiConfigurationType;
use Throwable;

final class ApiConfigurationController extends AbstractController
{
    private const PAGING_SIZE = 1;

    private EntityManagerInterface $entityManager;

    private EntityRepository $apiConfigurationRepository;

    private TranslatorInterface $translator;

    private FlashBagInterface $flashBag;

    private ClientFactory $clientFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        ClientFactory $clientFactory,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->clientFactory = $clientFactory;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            $apiConfiguration = new ApiConfiguration();
        }

        $form = $this->createForm(ApiConfigurationType::class, $apiConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmitButton $testCredentialsButton */
            $testCredentialsButton = $form->get('testCredentials');

            try {
                $client = $this->clientFactory->authenticateByPassword($apiConfiguration);
                $client->getCategoryApi()->all(self::PAGING_SIZE);

                $this->entityManager->persist($apiConfiguration);

                if (!$testCredentialsButton->isClicked()) {
                    $this->entityManager->flush();
                }

                $this->flashBag->add('success', $this->translator->trans('akeneo.ui.admin.authentication_successfully_succeeded'));
            } catch (Throwable $throwable) {
                $this->flashBag->add('error', $throwable->getMessage());
            }
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/api_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
