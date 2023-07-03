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
        $container->getDefinition('app.repository.telegram_update')->setPublic(true);
        $container->getDefinition('app.repository.telegram_conversation')->setPublic(true);
        $container->getDefinition('app.telegram_update_handler')->setPublic(true);
        $container->getDefinition('app.telegram_registry')->setPublic(true);
        $container->getDefinition('app.telegram_chat_action_sender')->setPublic(true);
        $container->getDefinition('app.telegram_message_sender')->setPublic(true);
        $container->getDefinition('app.telegram_translator')->setPublic(true);
        $container->getDefinition('app.telegram_keyboard_factory')->setPublic(true);
        $container->getDefinition('app.instagram_messenger_user_provider')->setPublic(true);
        $container->getDefinition('app.repository.messenger_user')->setPublic(true);
        $container->getDefinition('app.telegram_conversation_manager')->setPublic(true);
        $container->getDefinition('app.telegram_template_renderer')->setPublic(true);
        $container->getDefinition('app.normalizer.telegram_conversation_state_create_feedback')->setPublic(true);
        $container->getDefinition('app.feedback_search_term_parser')->setPublic(true);
        $container->getDefinition('serializer')->setPublic(true);
        $container->getDefinition('app.telegram_aware_helper')->setPublic(true);
        $container->getDefinition('app.repository.feedback')->setPublic(true);
        $container->getDefinition('app.repository.feedback_search')->setPublic(true);
        $container->getDefinition('app.instagram_messenger_user_provider')->setPublic(true);
        $container->getDefinition('app.telegram_user_provider')->setPublic(true);
        $container->getDefinition('app.telegram_chat_provider')->setPublic(true);
        $container->getDefinition('app.repository.user')->setPublic(true);
    }
}
