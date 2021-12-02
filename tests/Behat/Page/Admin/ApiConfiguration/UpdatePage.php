<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\Behat\Page\Admin\ApiConfiguration;

use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Page\Admin\Crud\UpdatePage as BaseUpdatePage;

final class UpdatePage extends BaseUpdatePage implements UpdatePageInterface
{
    public function setBaseUrl(string $url): void
    {
        $this->getDocument()->fillField('Akeneo base URL', $url);
    }

    public function containsErrorWithMessage(string $message, bool $strict = true): bool
    {
        $validationMessageElements = $this->getDocument()->findAll('css', '.sylius-validation-error');
        $result = false;

        /** @var NodeElement $validationMessageElement */
        foreach ($validationMessageElements as $validationMessageElement) {
            if (true === $strict && $message === $validationMessageElement->getText()) {
                return true;
            }

            if (false === $strict && mb_strstr($validationMessageElement->getText(), $message)) {
                return true;
            }
        }

        return $result;
    }

    public function saveChanges(): void
    {
        $this->getDocument()->pressButton('api_configuration_submit');
    }
}
