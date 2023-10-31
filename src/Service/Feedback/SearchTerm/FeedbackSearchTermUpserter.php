<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Repository\Feedback\FeedbackSearchTermRepository;
use App\Service\Messenger\MessengerUserUpserter;
use App\Transfer\Feedback\SearchTermTransfer;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchTermUpserter
{
    public function __construct(
        private readonly FeedbackSearchTermRepository $feedbackSearchTermRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessengerUserUpserter $messengerUserUpserter,
        private readonly FeedbackSearchTermTextNormalizer $feedbackSearchTermTextNormalizer
    )
    {
    }

    public function upsertFeedbackSearchTerm(SearchTermTransfer $searchTermTransfer): FeedbackSearchTerm
    {
        if ($searchTermTransfer->getMessengerUser() == null) {
            $messengerUser = null;
        } else {
            $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($searchTermTransfer->getMessengerUser());
        }

        $feedbackSearchTermNew = new FeedbackSearchTerm(
            $searchTermTransfer->getText(),
            $this->feedbackSearchTermTextNormalizer->normalizeFeedbackSearchTermText(
                $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText()
            ),
            $searchTermTransfer->getType(),
            messengerUser: $messengerUser,
        );

        $searchTerms = $this->feedbackSearchTermRepository->findByNormalizedText($feedbackSearchTermNew->getNormalizedText());

        $searchTerms = array_filter($searchTerms, static function (FeedbackSearchTerm $feedbackSearchTerm) use ($feedbackSearchTermNew): bool {
            if ($feedbackSearchTerm->getText() !== $feedbackSearchTermNew->getText()) {
                return false;
            }

            if ($feedbackSearchTerm->getType() !== $feedbackSearchTermNew->getType()) {
                return false;
            }

            return true;
        });

        if (count($searchTerms) === 0) {
            $this->entityManager->persist($feedbackSearchTermNew);

            return $feedbackSearchTermNew;
        }

        return current($searchTerms);
    }
}
