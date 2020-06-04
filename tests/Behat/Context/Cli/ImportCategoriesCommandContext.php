<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Sylius\Bundle\CoreBundle\Command\SetupCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class ImportCategoriesCommandContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var Application */
    private $application;

    /** @var CommandTester */
    private $tester;

    /** @var SetupCommand */
    private $command;

    /** @var \Exception|\Throwable */
    private $exception;

    public function __construct(
        KernelInterface $kernel
    ) {
        $this->kernel = $kernel;
    }

    /**
     * @When I run akeneo import categories command
     */
    public function iRunAkeneoImportCategoriesCommand(): void
    {
        $this->application = new Application($this->kernel);
        $this->application->add(new SetupCommand());

        $this->command = $this->application->find('akeneo:import:categories');
        $this->tester = new CommandTester($this->command);

        try {
            $this->tester->execute(['command' => 'akeneo:import:categories']);
        } catch (\Throwable $exception) {
            $this->exception = $exception;
        }
    }

    /**
     * @Then I should see :output in output
     */
    public function iShouldSeeInOutput(string $output): void
    {
        Assert::contains($this->tester->getDisplay(), $output);
    }

    /**
     * @Then I should get an exception :message
     */
    public function iShouldGetAnException(string $message)
    {
        Assert::contains($this->exception, $message);
    }
}
