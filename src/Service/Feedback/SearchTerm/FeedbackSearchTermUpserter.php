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
        private readonly FeedbackSearchTermRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessengerUserUpserter $messengerUserUpserter,
    )
    {
    }

    public function upsertFeedbackSearchTerm(SearchTermTransfer $termTransfer): FeedbackSearchTerm
    {
        if ($termTransfer->getMessengerUser() == null) {
            $messengerUser = null;
        } else {
            $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($termTransfer->getMessengerUser());
        }

        $newTerm = new FeedbackSearchTerm(
            $termTransfer->getText(),
            $termTransfer->getNormalizedText() ?? $termTransfer->getText(),
            $termTransfer->getType(),
            messengerUser: $messengerUser,
        );

        $searchTerms = $this->repository->findByNormalizedText($newTerm->getNormalizedText());

        $searchTerms = array_filter($searchTerms, static function (FeedbackSearchTerm $searchTerm) use ($newTerm): bool {
            if ($searchTerm->getText() !== $newTerm->getText()) {
                return false;
            }

            if ($searchTerm->getType() !== $newTerm->getType()) {
                return false;
            }

            return true;
        });

        if (count($searchTerms) === 0) {
            $this->entityManager->persist($newTerm);

            return $newTerm;
        }

        return current($searchTerms);
    }
}
