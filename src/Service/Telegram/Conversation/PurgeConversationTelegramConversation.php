<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Service\Telegram\TelegramLocaleSwitcher;
use App\Service\User\UserDataPurger;
use Longman\TelegramBot\Entities\KeyboardButton;

class PurgeConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_QUERIED = 10;
    public const STEP_CONFIRMED = 20;

    public function __construct(
        private readonly UserDataPurger $userDataPurger,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly TelegramLocaleSwitcher $localeSwitcher,
        private readonly CountryProvider $countryProvider,
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

    public function gotConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($this->getConfirmNoButton($tg)->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
        if (!$tg->matchText($this->getConfirmYesButton($tg)->getText())) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryConfirm($tg);
        }

        $this->state->setStep(self::STEP_CONFIRMED);

        $user = $tg->getTelegram()->getMessengerUser()->getUser();

        $this->userDataPurger->purgeUserData($user);

        // todo: implement default setting provider (by telegram object) and implement everywhere and here as well
        $country = $this->countryProvider->getCountry($tg->getTelegram()->getBot()->getCountryCode());
        $this->localeSwitcher->setLocale($user->getLocaleCode() ?? ($country->getLocaleCodes()[0] ?? null));

        $replyText = $tg->okText($tg->trans('reply.ok', domain: 'tg.purge'));

        $tg->stopConversation($entity);

        return $this->chooseActionChatSender->sendActions($tg, $replyText);
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