<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Conversation\TelegramConversation;
use App\Service\Telegram\Conversation\TelegramConversationInterface;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramLocaleSwitcher;
use App\Service\User\UserDataPurger;

class PurgeConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_QUERIED = 10;
    public const STEP_CONFIRMED = 20;

    public function __construct(
        private readonly UserDataPurger $userDataPurger,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly TelegramLocaleSwitcher $localeSwitcher,
    )
    {
        parent::__construct(new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        return $this->queryConfirm($tg);
    }

    public function queryConfirm(TelegramAwareHelper $tg, bool $help = false): null
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

    public function gotConfirm(TelegramAwareHelper $tg, Entity $entity): null
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

        $user = $tg->getTelegram()->getMessengerUser()->getUser();

        $this->userDataPurger->purgeUserData($user);

        // todo: implement default setting provider (by telegram object) and implement everywhere and here as well
        $this->localeSwitcher->setLocale($user->getLocaleCode() ?? $tg->getTelegram()->getBot()->getLocaleCode());

        $message = $tg->trans('reply.ok', domain: 'purge');
        $message = $tg->okText($message);

        $tg->stopConversation($entity);

        return $this->chooseActionChatSender->sendActions($tg, $message);
    }
}