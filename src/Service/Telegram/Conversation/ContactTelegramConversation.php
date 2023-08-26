<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Enum\Telegram\TelegramGroup;
use App\Exception\ValidatorException;
use App\Object\User\UserFeedbackMessageTransfer;
use App\Service\ContactOptionsFactory;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\User\UserFeedbackMessageCreator;
use Longman\TelegramBot\Entities\KeyboardButton;

class ContactTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_LEFT_MESSAGE_CONFIRM_QUERIED = 10;
    public const STEP_MESSAGE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        private readonly UserFeedbackMessageCreator $messageCreator,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly ContactOptionsFactory $contactOptionsFactory,
        private readonly CountryProvider $countryProvider,
    )
    {
        parent::__construct(new CreateFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED => $this->gotLeftMessageConfirm($tg, $entity),
            self::STEP_MESSAGE_QUERIED => $this->gotMessage($tg, $entity),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        $this->describe($tg);

        $country = $this->countryProvider->getCountry($tg->getCountryCode());
        $localeCode = $country->getLocaleCodes()[0] ?? null;

        $tg->reply(
            $tg->view(
                'query_contact',
                [
                    'contacts' => $this->contactOptionsFactory->createContactOptions(TelegramGroup::feedbacks, $localeCode),
                ]
            ),
            protectContent: true
        );

        return $this->queryLeftMessageConfirm($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_contact'));
    }

    public function queryLeftMessageConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED);

        $buttons = [];
        $buttons[] = $this->getLeftMessageConfirmYesButton($tg);
        $buttons[] = $this->getLeftMessageConfirmNoButton($tg);

        return $tg->reply(
            $this->getLeftMessageConfirmQuery($tg),
            $tg->keyboard(...$buttons)
        )->null();
    }

    public function gotLeftMessageConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($this->getLeftMessageConfirmNoButton($tg)->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if (!$tg->matchText($this->getLeftMessageConfirmYesButton($tg)->getText())) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryLeftMessageConfirm($tg);
        }

        return $this->queryMessage($tg);
    }

    public function queryMessage(TelegramAwareHelper $tg): ?string
    {
        $this->state->setStep(self::STEP_MESSAGE_QUERIED);

        return $tg->reply(
            $this->getMessageQuery($tg),
            $tg->keyboard($this->getCancelButton($tg))
        )->null();
    }

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity)->replyUpset($tg->trans('reply.canceled', domain: 'tg.contact'));

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function gotMessage(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryMessage($tg);
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        try {
            $this->messageCreator->createUserFeedbackMessage(
                new UserFeedbackMessageTransfer(
                    $entity->getMessengerUser(),
                    $entity->getMessengerUser()->getUser(),
                    $tg->getText()
                )
            );

            $tg->stopConversation($entity);
            $replyText = $tg->okText($tg->trans('reply.ok', domain: 'tg.contact'));

            return $this->chooseActionChatSender->sendActions($tg, $replyText);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryMessage($tg);
        }
    }

    public static function getLeftMessageConfirmQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.left_message_confirm', domain: 'tg.contact');
    }

    public static function getLeftMessageConfirmYesButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.yes'));
    }

    public static function getLeftMessageConfirmNoButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.no'));
    }

    public static function getMessageQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.message', domain: 'tg.contact');
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}