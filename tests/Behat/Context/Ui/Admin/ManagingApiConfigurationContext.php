<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Tests\Synolia\SyliusAkeneoPlugin\Behat\Page\Admin\ApiConfiguration\UpdatePageInterface;
use Webmozart\Assert\Assert;

final class ManagingApiConfigurationContext implements Context
{
    /** @var UpdatePageInterface */
    private $updatePage;

    public function __construct(UpdatePageInterface $updatePage)
    {
        $this->updatePage = $updatePage;
    }

    /**
     * @Given I want to configure the akeneo api
     */
    public function iWantToConfigureTheAkeneoApi(): void
    {
        $this->updatePage->open();
    }

    /**
     * @When I fill the Akeneo base URL with :url
     */
    public function iFillTheSecretKeyWith(string $url): void
    {
        $this->updatePage->setBaseUrl($url);
    }

    /**
     * @When I save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @Then I should be notified that fields cannot be blank
     */
    public function iShouldBeNotifiedThatCannotBeBlank(): void
    {
        Assert::true($this->updatePage->containsErrorWithMessage('This value should not be blank.'));
    }

    /**
     * @Then I should be notified that :message
     */
    public function iShouldBeNotifiedThat(string $message): void
    {
        Assert::true($this->updatePage->containsErrorWithMessage($message));
    }
}
