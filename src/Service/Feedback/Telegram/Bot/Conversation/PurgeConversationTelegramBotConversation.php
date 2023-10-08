<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotLocaleSwitcher;
use App\Service\User\UserDataPurger;

class PurgeConversationTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_CONFIRM_QUERIED = 10;
    public const STEP_CONFIRMED = 20;

    public function __construct(
        private readonly UserDataPurger $userDataPurger,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly TelegramBotLocaleSwitcher $localeSwitcher,
    )
    {
        parent::__construct(new TelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->queryConfirm($tg);
    }

    public function queryConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $query = $tg->trans('query.confirm', domain: 'purge');

        if ($help) {
            $query = $tg->view('purge_confirm_help', [
                'query' => $query,
            ]);
        }
        $message = $query;

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
        ];

        if ($this->state->hasNotSkipHelpButton('confirm')) {
            $buttons[] = $tg->helpButton();
        }

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('confirm');

            return $this->queryConfirm($tg, true);
        }
        if (!$tg->matchText($tg->yesButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryConfirm($tg);
        }

        $this->state->setStep(self::STEP_CONFIRMED);

        $user = $tg->getBot()->getMessengerUser()->getUser();

        $this->userDataPurger->purgeUserData($user);

        // todo: implement default setting provider (by telegram object) and implement everywhere and here as well
        $this->localeSwitcher->setLocale($user->getLocaleCode() ?? $tg->getBot()->getEntity()->getLocaleCode());

        $message = $tg->trans('reply.ok', domain: 'purge');
        $message = $tg->okText($message);

        $tg->stopConversation($entity);

        return $this->chooseActionChatSender->sendActions($tg, $message);
    }
}