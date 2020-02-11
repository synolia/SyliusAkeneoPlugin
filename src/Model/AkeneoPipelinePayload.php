<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model;

use Symfony\Component\Console\Output\OutputInterface;

final class AkeneoPipelinePayload
{
    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
