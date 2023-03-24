<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class AdminCategoryMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        if (null === $menu->getChild('akeneo')) {
            $menu->addChild('akeneo');
        }

        /** @var ItemInterface $subMenu */
        $subMenu = $menu->getChild('akeneo');

        $subMenu->addChild('sylius_admin_akeneo_categories', [
            'route' => 'sylius_akeneo_connector_categories',
        ])
        ->setAttribute('type', 'link')
        ->setLabel('sylius.ui.admin.akeneo.categories.title')
        ->setLabelAttribute('icon', 'configure')
        ;
    }
}
