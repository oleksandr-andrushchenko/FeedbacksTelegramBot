<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('monolog.logger')->setPublic(true);
        $container->getDefinition('app.repository.telegram_bot_update')->setPublic(true);
        $container->getDefinition('app.repository.telegram_bot_conversation')->setPublic(true);
        $container->getDefinition('app.telegram_bot_update_handler')->setPublic(true);
        $container->getDefinition('app.telegram_bot_registry')->setPublic(true);
        $container->getDefinition('app.telegram_bot_message_sender')->setPublic(true);
        $container->getDefinition('app.translator')->setPublic(true);
        $container->getDefinition('app.telegram_bot_keyboard_factory')->setPublic(true);
        $container->getDefinition('app.repository.messenger_user')->setPublic(true);
        $container->getDefinition('app.normalizer.telegram_bot_conversation_state_create_feedback')->setPublic(true);
        $container->getDefinition('app.feedback_search_term_parser')->setPublic(true);
        $container->getDefinition('serializer')->setPublic(true);
        $container->getDefinition('app.telegram_bot_aware_helper')->setPublic(true);
        $container->getDefinition('app.repository.feedback')->setPublic(true);
        $container->getDefinition('app.repository.feedback_search')->setPublic(true);
        $container->getDefinition('app.telegram_bot_user_provider')->setPublic(true);
        $container->getDefinition('app.telegram_bot_chat_provider')->setPublic(true);
        $container->getDefinition('app.repository.user')->setPublic(true);
        $container->getDefinition('app.repository.telegram_bot')->setPublic(true);
        $container->getDefinition('app.repository.feedback_search_search')->setPublic(true);
        $container->getDefinition('app.intl_currency_provider')->setPublic(true);
        $container->getDefinition('app.feedback_subscription_plan_provider')->setPublic(true);
        $container->getDefinition('app.repository.telegram_bot_payment')->setPublic(true);
        $container->getDefinition('app.telegram_bot_invoice_sender')->setPublic(true);
        $container->getDefinition('app.intl_country_provider')->setPublic(true);
        $container->getDefinition('app.feedback_rating_provider')->setPublic(true);
    }
}
