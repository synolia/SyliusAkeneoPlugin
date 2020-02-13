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

        /** @var ItemInterface $newSubmenu */
        $newSubmenu = $menu->addChild('akeneo');

        $newSubmenu->addChild('sylius_admin_akeneo_api_configuration', [
                'route' => 'sylius_akeneo_connector_api_configuration',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.api_configuration.api_configuration')
            ->setLabelAttribute('icon', 'cogs')
        ;

        $newSubmenu->addChild('sylius_admin_akeneo_product_filter_rules', [
                'route' => 'sylius_akeneo_connector_product_filter_rules',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.product_filter_rules.product_filter_rules')
            ->setLabelAttribute('icon', 'sync alternate')
        ;

        $newSubmenu->addChild('sylius_admin_akeneo_categories', [
                'route' => 'sylius_akeneo_connector_categories',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.categories.categories')
            ->setLabelAttribute('icon', 'configure')
        ;

        $newSubmenu->addChild('sylius_admin_akeneo_products', [
                'route' => 'sylius_akeneo_connector_products',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.products.products')
            ->setLabelAttribute('icon', 'configure')
        ;

        $newSubmenu->addChild('sylius_admin_akeneo_attributes', [
                'route' => 'akeneo_admin_attribute_type_mapping_index',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.attributes.attributes')
            ->setLabelAttribute('icon', 'configure')
        ;

        $newSubmenu->addChild('sylius_admin_akeneo_medias', [
                'route' => 'sylius_akeneo_connector_medias',
            ])
            ->setAttribute('type', 'link')
            ->setLabel('sylius.ui.admin.akeneo.medias.medias')
            ->setLabelAttribute('icon', 'image')
        ;
    }
}
