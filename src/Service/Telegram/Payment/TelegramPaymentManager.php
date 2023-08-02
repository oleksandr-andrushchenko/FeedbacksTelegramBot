<?php

declare(strict_types=1);

namespace App\Service\Telegram\Payment;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Money;
use App\Entity\Telegram\TelegramPayment;
use App\Entity\Telegram\TelegramPaymentManagerOptions;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramPaymentMethodName;
use App\Enum\Telegram\TelegramPaymentStatus;
use App\Exception\Telegram\PaymentNotFoundException;
use App\Exception\Telegram\UnknownPaymentException;
use App\Repository\Telegram\TelegramPaymentRepository;
use App\Service\Intl\CurrencyProvider;
use App\Service\Logger\ActivityLogger;
use App\Service\Telegram\Api\TelegramInvoiceSender;
use App\Service\Telegram\Telegram;
use App\Service\UuidGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Entities\Payments\OrderInfo;
use Longman\TelegramBot\Entities\Payments\PreCheckoutQuery;
use DateTimeImmutable;
use Longman\TelegramBot\Entities\Payments\SuccessfulPayment;

class TelegramPaymentManager
{
    public function __construct(
        private readonly TelegramPaymentManagerOptions $options,
        private readonly TelegramInvoiceSender $invoiceSender,
        private readonly TelegramPaymentMethodProvider $paymentMethodProvider,
        private readonly CurrencyProvider $currencyProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramPaymentRepository $paymentRepository,
        private readonly UuidGenerator $uuidGenerator,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    public function sendPaymentRequest(
        Telegram $telegram,
        MessengerUser $messengerUser,
        int $chatId,
        TelegramPaymentMethodName $paymentMethodName,
        string $title,
        string $description,
        string $label,
        string $purpose,
        array $payload,
        Money $price
    ): TelegramPayment
    {
        $currency = $this->currencyProvider->getCurrency($price->getCurrency());

        $uuid = $this->uuidGenerator->generateUuid();
        $payload['payment_id'] = $uuid;

        $payment = new TelegramPayment(
            $uuid,
            $messengerUser,
            $chatId,
            $paymentMethodName,
            $purpose,
            $price,
            $payload
        );
        $this->entityManager->persist($payment);

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($payment);
        }

        $this->invoiceSender->sendInvoice(
            $telegram,
            $payment->getChatId(),
            $title,
            $description,
            json_encode($payment->getPayload()),
            $this->paymentMethodProvider->getPaymentMethod($payment->getMethod())->getToken(),
            $currency->getCode(),
            [
                new LabeledPrice([
                    'label' => $label,
                    'amount' => $payment->getPrice()->getAmount() * pow(10, $currency->getExp()),
                ]),
            ]
        );

        return $payment;
    }

    /**
     * @param Telegram $telegram
     * @param PreCheckoutQuery $preCheckoutQuery
     * @return TelegramPayment
     * @throws PaymentNotFoundException
     * @throws UnknownPaymentException
     */
    public function acceptPreCheckoutQuery(Telegram $telegram, PreCheckoutQuery $preCheckoutQuery): TelegramPayment
    {
        $payment = $this->getPaymentByPayload($preCheckoutQuery->getInvoicePayload());
        $payment->setPreCheckoutQuery($preCheckoutQuery->jsonSerialize());

        $this->updateUserByOrderInfo($telegram->getMessengerUser()->getUser(), $preCheckoutQuery->getOrderInfo());

        $telegram->answerPreCheckoutQuery([
            'pre_checkout_query_id' => $preCheckoutQuery->getId(),
            'ok' => $telegram->getOptions()->acceptPayments(),
        ]);

        $payment->setStatus(TelegramPaymentStatus::PRE_CHECKOUT_RECEIVED);
        $payment->setUpdatedAt(new DateTimeImmutable());

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($payment);
        }

        return $payment;
    }

    /**
     * @param Telegram $telegram
     * @param SuccessfulPayment $successfulPayment
     * @return TelegramPayment
     * @throws PaymentNotFoundException
     * @throws UnknownPaymentException
     */
    public function acceptSuccessfulPayment(Telegram $telegram, SuccessfulPayment $successfulPayment): TelegramPayment
    {
        $payment = $this->getPaymentByPayload($successfulPayment->getInvoicePayload());
        $payment->setSuccessfulPayment($successfulPayment->jsonSerialize());

        $this->updateUserByOrderInfo($telegram->getMessengerUser()->getUser(), $successfulPayment->getOrderInfo());

        $payment->setStatus(TelegramPaymentStatus::SUCCESSFUL_PAYMENT_RECEIVED);
        $payment->setUpdatedAt(new DateTimeImmutable());

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($payment);
        }

        return $payment;
    }

    /**
     * @param string $payload
     * @return TelegramPayment
     * @throws PaymentNotFoundException
     * @throws UnknownPaymentException
     */
    public function getPaymentByPayload(string $payload): TelegramPayment
    {
        $data = json_decode($payload, true);
        $uuid = $data['payment_id'] ?? null;

        if ($uuid === null) {
            throw new UnknownPaymentException();
        }

        $payment = $this->paymentRepository->findOneBy(['uuid' => $uuid]);

        if ($payment === null) {
            throw new PaymentNotFoundException();
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
