<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Site\SitePage;
use App\Service\Site\SiteViewResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class SiteController
{
    public function __construct(
        private readonly SiteViewResponseFactory $viewResponseFactory,
    )
    {
    }

    public function index(): Response
    {
        return $this->viewResponseFactory->createViewResponse(SitePage::INDEX);
    }

    public function privacyPolicy(): Response
    {
        return $this->viewResponseFactory->createViewResponse(SitePage::PRIVACY_POLICY);
    }

    public function termsOfUse(): Response
    {
        return $this->viewResponseFactory->createViewResponse(SitePage::TERMS_OF_USE);
    }

    public function contacts(): Response
    {
        return $this->viewResponseFactory->createViewResponse(SitePage::CONTACTS);
    }
}
