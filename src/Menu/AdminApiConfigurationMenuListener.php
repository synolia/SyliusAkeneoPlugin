<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class AdminApiConfigurationMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        if (!$menu->getChild('akeneo') instanceof ItemInterface) {
            $menu->addChild('akeneo');
        }

        /** @var ItemInterface $subMenu */
        $subMenu = $menu->getChild('akeneo');

        $subMenu
            ->addChild('sylius_admin_akeneo_api_configuration', [
                'route' => 'sylius_akeneo_connector_api_configuration',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.api_configuration.title')
            ->setLabelAttribute('icon', 'cogs')
        ;
    }
}
