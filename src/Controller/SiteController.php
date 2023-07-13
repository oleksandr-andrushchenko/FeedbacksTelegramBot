<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class SiteController
{
    public function index(): Response
    {
        return new Response(
            join('', [
                '<!DOCTYPE html>',
                '<html>',
                '<head><title>Contacts</title><meta name="robots" content="noindex"></head>',
                '<body>',
                join('<br/>', [
                    '<h1 style="font-size: medium">If you have questions or any suggestions, please reach me out by:</h1>',
                    'telegram: <a href="https://t.me/wild_snowgirl" target="_blank">wild_snowgirl</a>',
                    'instagram: <a href="https://www.instagram.com/wild.snowgirl/" target="_blank">wild.snowgirl</a>',
                    'github: <a href="https://github.com/oleksandr-andrushchenko" target="_blank">oleksandr-andrushchenko</a>',
                    'linkedin: <a href="https://www.linkedin.com/in/oleksandr-andrushchenko-26ab3078/" target="_blank">oleksandr-andrushchenko-26ab3078</a>',
                    'email: <a href="mailto:oleksandr.andrushchenko1988@gmail.com">oleksandr.andrushchenko1988@gmail.com</a>',
                ]),
                '</body>',
                '</html>',
            ])
        );
    }
}
