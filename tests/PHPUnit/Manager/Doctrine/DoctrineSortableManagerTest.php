<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Manager\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMSetup;
use Gedmo\Sortable\SortableListener;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Synolia\SyliusAkeneoPlugin\Manager\Doctrine\DoctrineSortableManager;

/**
 * @internal
 */
#[CoversNothing]
final class DoctrineSortableManagerTest extends TestCase
{
    private EventManager $eventManager;

    private SortableListener $sortableListener;

    private DoctrineSortableManager $doctrineSortableManager;

    protected function setUp(): void
    {
        $this->eventManager = new EventManager();
        $this->sortableListener = new SortableListener();
        $this->eventManager->addEventSubscriber($this->sortableListener);

        // The manager only depends on the event manager: a real EntityManager on an unused in-memory connection is enough.
        $configuration = ORMSetup::createAttributeMetadataConfiguration([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($configuration, 'enableNativeLazyObjects')) {
            $configuration->enableNativeLazyObjects(true);
        }
        $entityManager = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration),
            $configuration,
            $this->eventManager,
        );

        $this->doctrineSortableManager = new DoctrineSortableManager($entityManager);
    }

    public function testDisableSortableEventListenerRemovesTheSortableListener(): void
    {
        $this->doctrineSortableManager->disableSortableEventListener();

        foreach ($this->eventManager->getAllListeners() as $listeners) {
            self::assertNotContains($this->sortableListener, $listeners);
        }
    }

    public function testEnableSortableEventListenerRestoresTheSortableListener(): void
    {
        $this->doctrineSortableManager->disableSortableEventListener();
        $this->doctrineSortableManager->enableSortableEventListener();

        self::assertContains($this->sortableListener, $this->eventManager->getListeners(Events::prePersist));
        self::assertContains($this->sortableListener, $this->eventManager->getListeners(Events::onFlush));
    }
}
