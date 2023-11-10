<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\Feedback\FeedbackSearch;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Message\Event\ActivityEvent;
use App\Message\Event\Feedback\FeedbackSearchCreatedEvent;
use App\Service\Feedback\Command\FeedbackCommandLimitsChecker;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
use App\Service\Feedback\Statistic\FeedbackUserStatisticProviderInterface;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\IdGenerator;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly FeedbackUserStatisticProviderInterface $statisticProvider,
        private readonly FeedbackCommandLimitsChecker $limitsChecker,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly FeedbackSearchTermUpserter $termUpserter,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackSearchTransfer $transfer
     * @return FeedbackSearch
     * @throws FeedbackCommandLimitExceededException
     * @throws ValidatorException
     */
    public function createFeedbackSearch(FeedbackSearchTransfer $transfer): FeedbackSearch
    {
        $this->validator->validate($transfer);

        $messengerUser = $transfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticProvider);
        }

        $searchTerm = $this->termUpserter->upsertFeedbackSearchTerm($transfer->getSearchTerm());

        $search = new FeedbackSearch(
            $this->idGenerator->generateId(),
            $messengerUser->getUser(),
            $messengerUser,
            $searchTerm,
            hasActiveSubscription: $hasActiveSubscription,
            countryCode: $messengerUser->getUser()->getCountryCode(),
            localeCode: $messengerUser->getUser()->getLocaleCode(),
            telegramBot: $transfer->getTelegramBot()
        );
        $this->entityManager->persist($search);

        $this->eventBus->dispatch(new ActivityEvent(entity: $search));
        $this->eventBus->dispatch(new FeedbackSearchCreatedEvent(search: $search));

        return $search;
    }
}
