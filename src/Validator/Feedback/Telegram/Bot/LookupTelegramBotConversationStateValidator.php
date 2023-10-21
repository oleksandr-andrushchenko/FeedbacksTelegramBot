<?php

declare(strict_types=1);

namespace App\Validator\Feedback\Telegram\Bot;

use App\Entity\Feedback\Telegram\Bot\LookupTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\LookupTelegramBotConversation;
use App\Service\Validator\ValidatorHelper;
use App\Validator\Feedback\SearchTermTransferConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class LookupTelegramBotConversationStateValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorHelper $helper,
    )
    {
    }

    /**
     * @param LookupTelegramBotConversationState $value
     * @param LookupTelegramBotConversationStateConstraint $constraint
     * @return null
     */
    public function validate(mixed $value, Constraint $constraint): null
    {
        if (!$value instanceof LookupTelegramBotConversationState) {
            throw new UnexpectedValueException($value, LookupTelegramBotConversationState::class);
        }

        if (!$constraint instanceof LookupTelegramBotConversationStateConstraint) {
            throw new UnexpectedValueException($value, LookupTelegramBotConversationStateConstraint::class);
        }

        $helper = $this->helper->withContext($this->context)->withTranslationDomain('feedbacks.tg.validator.lookup');

        if ($value->getStep() > LookupTelegramBotConversation::STEP_SEARCH_TERM_QUERIED) {
            if ($value->getSearchTerm() === null) {
                return $helper->addMessage($constraint->searchTermNotBlankMessage);
            }

            $this->context->getValidator()->validate($value->getSearchTerm(), new SearchTermTransferConstraint());
        }

        return null;
    }
}