<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Enum\Telegram\TelegramView;
use Twig\Environment;

class TelegramTemplateRenderer
{
    public function __construct(
        private readonly Environment $twig,
    )
    {
    }

    public function renderTelegramTemplate(?string $languageCode, string|TelegramView $template, array $context = []): string
    {
        $context['language_code'] = $languageCode;

        return $this->twig->render(sprintf('telegram/%s.html.twig', is_string($template) ? $template : $template->value), $context);
    }
}