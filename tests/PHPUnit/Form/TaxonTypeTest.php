<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Form;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonType;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Form\Type\TaxonAttributeValueType;

/**
 * @internal
 *
 * @coversNothing
 */
final class TaxonTypeTest extends KernelTestCase
{
    public function testNativeTaxonFormIsBuiltWithTaxonAttributes(): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get('form.factory');
        /** @var FactoryInterface $taxonFactory */
        $taxonFactory = self::getContainer()->get('sylius.factory.taxon');

        $form = $formFactory->create(TaxonType::class, $taxonFactory->createNew());

        self::assertTrue($form->has('attributes'));
        self::assertInstanceOf(
            TaxonAttributeValueType::class,
            $form->get('attributes')->getConfig()->getAttribute('prototype')->getConfig()->getType()->getInnerType(),
        );
    }
}
