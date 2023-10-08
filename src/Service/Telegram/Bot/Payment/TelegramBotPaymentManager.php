<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Payment;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Money;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\Telegram\TelegramBotPaymentManagerOptions;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramBotPaymentStatus;
use App\Exception\Telegram\Bot\Payment\TelegramBotInvalidCurrencyBotException;
use App\Exception\Telegram\Bot\Payment\TelegramBotPaymentNotFoundException;
use App\Exception\Telegram\Bot\Payment\TelegramBotUnknownPaymentException;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;
use App\Service\Intl\CurrencyProvider;
use App\Service\Logger\ActivityLogger;
use App\Service\Telegram\Bot\Api\TelegramBotInvoiceSenderInterface;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\UuidGenerator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Entities\Payments\OrderInfo;
use Longman\TelegramBot\Entities\Payments\PreCheckoutQuery;
use Longman\TelegramBot\Entities\Payments\SuccessfulPayment;

class TelegramBotPaymentManager
{
    public function __construct(
        private readonly TelegramBotPaymentManagerOptions $options,
        private readonly TelegramBotInvoiceSenderInterface $invoiceSender,
        private readonly CurrencyProvider $currencyProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramBotPaymentRepository $paymentRepository,
        private readonly UuidGenerator $uuidGenerator,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @param MessengerUser $messengerUser
     * @param int $chatId
     * @param TelegramBotPaymentMethod $paymentMethod
     * @param string $title
     * @param string $description
     * @param string $label
     * @param string $purpose
     * @param array $payload
     * @param Money $price
     * @return TelegramBotPayment
     * @throws TelegramBotInvalidCurrencyBotException
     */
    public function sendPaymentRequest(
        TelegramBot $bot,
        MessengerUser $messengerUser,
        int $chatId,
        TelegramBotPaymentMethod $paymentMethod,
        string $title,
        string $description,
        string $label,
        string $purpose,
        array $payload,
        Money $price
    ): TelegramBotPayment
    {
        $currency = $this->currencyProvider->getCurrency($price->getCurrency());

        $uuid = $this->uuidGenerator->generateUuid();
        $payload['payment_id'] = $uuid;

        $payment = new TelegramBotPayment(
            $uuid,
            $messengerUser,
            $chatId,
            $paymentMethod,
            $purpose,
            $price,
            $payload
        );
        $this->entityManager->persist($payment);

        $this->invoiceSender->sendInvoice(
            $bot,
            $payment->getChatId(),
            $title,
            $description,
            json_encode($payment->getPayload()),
            $paymentMethod->getToken(),
            $currency->getCode(),
            [
                new LabeledPrice([
                    'label' => $label,
                    'amount' => $payment->getPrice()->getAmount() * pow(10, $currency->getExp()),
                ]),
            ]
        );

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($payment);
        }

        return $payment;
    }

    /**
     * @param TelegramBot $bot
     * @param PreCheckoutQuery $preCheckoutQuery
     * @return TelegramBotPayment
     * @throws TelegramBotPaymentNotFoundException
     * @throws TelegramBotUnknownPaymentException
     */
    public function acceptPreCheckoutQuery(TelegramBot $bot, PreCheckoutQuery $preCheckoutQuery): TelegramBotPayment
    {
        $payment = $this->getPaymentByPayload($preCheckoutQuery->getInvoicePayload());
        $payment->setPreCheckoutQuery($preCheckoutQuery->jsonSerialize());

        $this->updateUserByOrderInfo($bot->getMessengerUser()->getUser(), $preCheckoutQuery->getOrderInfo());

        $bot->answerPreCheckoutQuery([
            'pre_checkout_query_id' => $preCheckoutQuery->getId(),
            'ok' => $bot->getEntity()->acceptPayments(),
        ]);

        $payment->setStatus(TelegramBotPaymentStatus::PRE_CHECKOUT_RECEIVED);
        $payment->setUpdatedAt(new DateTimeImmutable());

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($payment);
        }

        return $payment;
    }

    /**
     * @param TelegramBot $bot
     * @param SuccessfulPayment $successfulPayment
     * @return TelegramBotPayment
     * @throws TelegramBotPaymentNotFoundException
     * @throws TelegramBotUnknownPaymentException
     */
    public function acceptSuccessfulPayment(TelegramBot $bot, SuccessfulPayment $successfulPayment): TelegramBotPayment
    {
        $payment = $this->getPaymentByPayload($successfulPayment->getInvoicePayload());
        $payment->setSuccessfulPayment($successfulPayment->jsonSerialize());

        $this->updateUserByOrderInfo($bot->getMessengerUser()->getUser(), $successfulPayment->getOrderInfo());

        $payment->setStatus(TelegramBotPaymentStatus::SUCCESSFUL_PAYMENT_RECEIVED);
        $payment->setUpdatedAt(new DateTimeImmutable());

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($payment);
        }

        return $payment;
    }

    /**
     * @param string $payload
     * @return TelegramBotPayment
     * @throws TelegramBotPaymentNotFoundException
     * @throws TelegramBotUnknownPaymentException
     */
    public function getPaymentByPayload(string $payload): TelegramBotPayment
    {
        $data = json_decode($payload, true);
        $uuid = $data['payment_id'] ?? null;

        if ($uuid === null) {
            throw new TelegramBotUnknownPaymentException();
        }

        $payment = $this->paymentRepository->findOneBy(['uuid' => $uuid]);

        if ($payment === null) {
            throw new TelegramBotPaymentNotFoundException();
        }

        // todo: compare current user and payments user

        return $payment;
    }

    public function updateUserByOrderInfo(User $user, ?OrderInfo $orderInfo): void
    {
        if ($orderInfo === null) {
            return;
        }

        if ($user->getPhoneNumber() === null && $orderInfo->getPhoneNumber() !== null) {
            $user->setPhoneNumber((int) $orderInfo->getPhoneNumber());
        }

        if ($user->getEmail() === null && $orderInfo->getEmail() !== null) {
            $user->setEmail($orderInfo->getEmail());
        }
    }
}
