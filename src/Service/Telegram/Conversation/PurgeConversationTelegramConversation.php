<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Service\User\UserDataPurger;
use Longman\TelegramBot\Entities\KeyboardButton;

class PurgeConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_QUERIED = 10;
    public const STEP_CONFIRMED = 20;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly UserDataPurger $userDataPurger,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $conversation),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        $this->describe($tg);

        return $this->queryConfirm($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_purge', [
            'items' => [
                'username',
                'name',
                'phone_number',
                'email',
                'country',
                'locale',
                'currency',
                'timezone',
                'settings',
            ],
        ]));
    }

    public function queryConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $buttons = [];

        $buttons[] = $this->getConfirmYesButton($tg);
        $buttons[] = $this->getConfirmNoButton($tg);

        return $tg->reply($this->getConfirmQuery($tg), $tg->keyboard(...$buttons))->null();
    }

    public function gotConfirm(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getConfirmNoButton($tg)->getText())) {
            return $this->chooseActionChatSender->sendActions($tg->stopConversation($conversation));
        }
        if (!$tg->matchText($this->getConfirmYesButton($tg)->getText())) {
            return $this->queryConfirm($tg->replyWrong($tg->trans('reply.wrong')));
        }

        $this->state->setStep(self::STEP_CONFIRMED);

        $this->userDataPurger->purgeUserData($tg->getTelegram()->getMessengerUser()->getUser());

        return $tg
            ->replyOk(
                $tg->trans('reply.ok', domain: 'tg.purge'),
                keyboard: $this->chooseActionChatSender->getKeyboard($tg)
            )
            ->stopConversation($conversation)
            ->null()
        ;
    }

    public static function getConfirmQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.confirm', domain: 'tg.purge');
    }

    public static function getConfirmYesButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.yes'));
    }

    public static function getConfirmNoButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.no'));
    }
}