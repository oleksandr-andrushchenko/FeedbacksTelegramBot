<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Util\Array\ArrayKeyQuoter;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ArrayKeyQuoter $arrayKeyQuoter,
        private ?string $domain = null,
        private ?string $locale = null,
    )
    {
    }

    public function withDomain(?string $domain): self
    {
        $new = clone $this;
        $new->domain = $domain;

        return $new;
    }

    public function withLocale(?string $locale): self
    {
        $new = clone $this;
        $new->locale = $locale;

        return $new;
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans(
            $id,
            $this->arrayKeyQuoter->quoteKeys($parameters),
            $domain ?? $this->domain,
            $locale ?? $this->locale,
        );
    }
}