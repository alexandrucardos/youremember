<?php

namespace App\Service\Tools;

use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorMock implements TranslatorInterface
{
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return 'translation_mock';
    }

    public function getLocale(): string
    {
        // TODO: Implement getLocale() method.
    }
}
