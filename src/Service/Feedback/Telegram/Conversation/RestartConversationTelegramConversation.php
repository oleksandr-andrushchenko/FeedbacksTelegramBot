<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Chat\StartTelegramCommandHandler;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversation;
use App\Service\Telegram\TelegramConversationInterface;

class RestartConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_QUERIED = 10;

    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly StartTelegramCommandHandler $startHandler,
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
        return $this->queryConfirm($tg);
    }

    public function queryConfirm(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $query = $tg->trans('query.confirm', domain: 'restart');

        if ($help) {
            $query = $tg->view('restart_confirm_help', [
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

        $countryCode = $tg->getTelegram()->getBot()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);

        $tg->getTelegram()->getMessengerUser()
            ?->setShowExtendedKeyboard(false)
            ?->getUser()
            ?->setCountryCode($country->getCode())
            ?->setLocaleCode($tg->getTelegram()->getBot()->getLocaleCode())
            ?->setCurrencyCode($country->getCurrencyCode())
            ?->setTimezone($country->getTimezones()[0] ?? null)
        ;

        $tg->stopConversation($entity)->stopConversations();

        $message = $tg->trans('reply.ok', domain: 'restart');
        $message = $tg->okText($message);

        $tg->reply($message);

        return $this->startHandler->handleStart($tg);
    }
}