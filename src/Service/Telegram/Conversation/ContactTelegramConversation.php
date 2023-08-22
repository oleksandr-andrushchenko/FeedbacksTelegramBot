<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Conversation;
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
    public const STEP_MESSAGE_QUERIED = 10;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly UserFeedbackMessageCreator $messageCreator,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly ContactOptionsFactory $contactOptionsFactory,
        private readonly CountryProvider $countryProvider,
    )
    {
        parent::__construct($awareHelper, new CreateFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        return match (true) {
            $this->state->getStep() === null => $this->start($tg),

            $tg->matchText(null) => $this->wrong($tg),
            $tg->matchText($this->getBackButton($tg)->getText()) => $this->gotBack($tg, $conversation),

            $this->state->getStep() === self::STEP_MESSAGE_QUERIED => $this->gotMessage($tg, $conversation),

            default => $this->wrong($tg)
        };
    }

    public function start(TelegramAwareHelper $tg): null
    {
        $this->describe($tg);

        return $this->queryMessage($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_contact'));
    }

    public function queryMessage(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_MESSAGE_QUERIED);

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

        return $tg->reply($tg->trans('query.message', domain: 'tg.contact'), $tg->keyboard($this->getBackButton($tg)))->null();
    }

    public function gotBack(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function wrong(TelegramAwareHelper $tg): ?string
    {
        return $tg->replyWrong($tg->trans('reply.wrong'))->null();
    }

    public function gotMessage(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        try {
            $this->messageCreator->createUserFeedbackMessage(
                new UserFeedbackMessageTransfer(
                    $conversation->getMessengerUser(),
                    $conversation->getMessengerUser()->getUser(),
                    $tg->getText()
                )
            );

            $tg->stopConversation($conversation)->replyOk($tg->trans('reply.ok', domain: 'tg.contact'));

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryMessage($tg);
        }
    }

    public static function getBackButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.back'));
    }
}