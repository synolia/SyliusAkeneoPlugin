<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * @method LocaleInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocaleInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocaleInterface[]    findAll()
 * @method LocaleInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface LocaleRepositoryInterface
{
    public function getLocaleCodes(): iterable;
}
