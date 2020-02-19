<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ApiConfigurationType;

final class ApiConfigurationController extends AbstractController
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ): Response {
        /** @var ApiConfiguration $apiConfiguration */
        $apiConfiguration = $entityManager->getRepository(ApiConfiguration::class)->findOneBy([]);

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
                $client->getCategoryApi()->get('master');

                $apiConfiguration->setToken($client->getToken() ?? '');
                $apiConfiguration->setRefreshToken($client->getRefreshToken() ?? '');

                $entityManager->persist($apiConfiguration);

                if (!$testCredentialsButton->isClicked()) {
                    $entityManager->flush();
                }

                $flashBag->add('success', $translator->trans('akeneo.ui.admin.authentication_successfully_succeeded'));
            } catch (\Throwable $throwable) {
                $flashBag->add('error', $throwable->getMessage());
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
