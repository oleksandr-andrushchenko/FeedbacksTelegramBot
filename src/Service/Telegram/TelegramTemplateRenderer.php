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

    public function renderTelegramTemplate(TelegramView $template, array $context = [], string $locale = null): string
    {
        // todo: remove locale (locale set up to locale switcher in update handler)
        return $this->twig->render($template->view(), $context);
    }
}