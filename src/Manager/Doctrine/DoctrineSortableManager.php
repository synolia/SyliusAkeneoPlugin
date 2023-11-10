<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\SortableListener;

class DoctrineSortableManager
{
    private array $originalEventListeners = [];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function disableSortableEventListener(): void
    {
        foreach ($this->entityManager->getEventManager()->getListeners() as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof SortableListener) {
                    $this->originalEventListeners[$eventName] = $listener;
                    $this->entityManager->getEventManager()->removeEventListener($eventName, $listener);
                }
            }
        }
    }

    public function enableSortableEventListener(): void
    {
        if ($this->originalEventListeners === []) {
            return;
        }

        foreach ($this->originalEventListeners as $eventName => $listener) {
            $this->entityManager->getEventManager()->addEventListener($eventName, $listener);
        }
    }
}
