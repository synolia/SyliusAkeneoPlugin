<?php


namespace Synolia\SyliusAkeneoPlugin\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class AkeneoProductFilterRulesController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/api_configuration.html.twig');
    }
}