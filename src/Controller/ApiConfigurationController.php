<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ApiConfigurationType;

final class ApiConfigurationController extends AbstractController
{
    private const PAGING_SIZE = 1;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EntityRepository */
    private $apiConfigurationRepository;

    /** @var TranslatorInterface */
    private $translator;

    /** @var FlashBagInterface */
    private $flashBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $apiConfigurationRepository,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ApiConfiguration $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            $apiConfiguration = new ApiConfiguration();
        }

        $form = $this->createForm(ApiConfigurationType::class, $apiConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\Form\SubmitButton $testCredentialsButton */
            $testCredentialsButton = $form->get('testCredentials');

            try {
                $client = $this->connect($apiConfiguration);
                $client->getCategoryApi()->all(self::PAGING_SIZE);

                $apiConfiguration->setToken($client->getToken() ?? '');
                $apiConfiguration->setRefreshToken($client->getRefreshToken() ?? '');

                $this->entityManager->persist($apiConfiguration);

                if (!$testCredentialsButton->isClicked()) {
                    $this->entityManager->flush();
                }

                $this->flashBag->add('success', $this->translator->trans('akeneo.ui.admin.authentication_successfully_succeeded'));
            } catch (\Throwable $throwable) {
                $this->flashBag->add('error', $throwable->getMessage());
            }
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/api_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function connect(ApiConfiguration $apiConfiguration): AkeneoPimClientInterface
    {
        $clientBuilder = new AkeneoPimClientBuilder($apiConfiguration->getBaseUrl() ?? '');

        return $clientBuilder->buildAuthenticatedByPassword(
            $apiConfiguration->getApiClientId() ?? '',
            $apiConfiguration->getApiClientSecret() ?? '',
            $apiConfiguration->getUsername() ?? '',
            $apiConfiguration->getPassword() ?? '',
        );
    }
}
