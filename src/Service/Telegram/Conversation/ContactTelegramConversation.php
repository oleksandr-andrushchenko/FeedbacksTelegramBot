<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\ContactOptions;
use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Enum\Telegram\TelegramGroup;
use App\Enum\Telegram\TelegramView;
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
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->queryMessage($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getBackButton($tg)->getText())) {
            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_MESSAGE_QUERIED) {
            return $this->gotMessage($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view(TelegramView::DESCRIBE_CONTACT), parseMode: 'HTML');
    }

    public function queryMessage(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_MESSAGE_QUERIED);

        $country = $this->countryProvider->getCountry($tg->getCountryCode());
        $localeCode = $this->countryProvider->getCountryDefaultLocale($country);

        $tg->reply(
            $tg->view(
                TelegramView::QUERY_CONTACT,
                [
                    'contacts' => $this->contactOptionsFactory->createContactOptions(TelegramGroup::feedbacks, $localeCode),
                ]
            ),
            parseMode: 'HTML',
            protectContent: true,
            disableWebPagePreview: true
        );

        return $tg->reply($tg->trans('query.message', domain: 'tg.contact'), $tg->keyboard($this->getBackButton($tg)))->null();
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