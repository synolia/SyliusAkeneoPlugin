<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        if (!$menu->getChild('akeneo') instanceof ItemInterface) {
            $menu->addChild('akeneo');
        }

        /** @var ItemInterface $subMenu */
        $subMenu = $menu->getChild('akeneo');

        $subMenu->addChild('sylius_admin_akeneo_product_filter_rules', [
            'route' => 'sylius_akeneo_connector_product_filter_rules',
        ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.product_filter_rules.title')
            ->setLabelAttribute('icon', 'sync alternate')
        ;

        $subMenu->addChild('sylius_admin_akeneo_products', [
            'route' => 'sylius_akeneo_connector_products',
        ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.products.title')
            ->setLabelAttribute('icon', 'configure')
        ;

        $subMenu->addChild('sylius_admin_akeneo_attributes', [
            'route' => 'sylius_akeneo_connector_attributes',
        ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.attributes.title')
            ->setLabelAttribute('icon', 'configure')
        ;
    }
}
