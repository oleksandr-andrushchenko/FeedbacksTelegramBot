<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Object\Feedback\SearchTermTransfer;
use Twig\Environment;

class SearchTermTelegramViewProvider
{
    public function __construct(
        private readonly Environment $twig,
    )
    {
    }

    public function getSearchTermTelegramView(SearchTermTransfer $searchTermTransfer): string
    {
        return $this->twig->render('feedbacks.tg.search_term.html.twig', [
            'search_term' => $searchTermTransfer,
        ]);
    }
}