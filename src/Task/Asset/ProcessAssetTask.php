<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Asset;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractProcessTask;

final class ProcessAssetTask extends AbstractProcessTask
{
    private EditionCheckerInterface $editionChecker;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        ProcessManagerInterface $processManager,
        BatchAssetTask $task,
        EditionCheckerInterface $editionChecker,
        string $projectDir
    ) {
        parent::__construct(
            $entityManager,
            $processManager,
            $task,
            $akeneoLogger,
            $projectDir
        );

        $this->editionChecker = $editionChecker;
    }

    /**
     * @param AssetPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $isEnterprise = $this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition();

        if (!$isEnterprise) {
            $this->logger->warning('Skipped akeneo:import:assets command because the configured Akeneo edition is not compatible.');

            return $payload;
        }

        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));
        $resources = $payload->getAkeneoPimClient()->getAssetFamilyApi()->all();

        $this->handle($payload, $resources);
        $this->processManager->waitForAllProcesses();

        return $payload;
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new AssetPayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
