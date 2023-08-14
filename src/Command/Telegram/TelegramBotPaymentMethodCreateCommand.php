<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramPaymentMethodName;
use App\Exception\Intl\CurrencyNotFoundException;
use App\Exception\Telegram\Payment\TelegramPaymentMethodNotFoundException;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Object\Telegram\Payment\TelegramPaymentMethodTransfer;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Intl\CurrencyProvider;
use App\Service\Telegram\Payment\TelegramPaymentMethodCreator;
use App\Service\Telegram\Payment\TelegramPaymentMethodInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotPaymentMethodCreateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $botRepository,
        private readonly TelegramPaymentMethodCreator $creator,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramPaymentMethodInfoProvider $infoProvider,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram bot username')
            ->addArgument('name', InputArgument::REQUIRED, 'Payment Method Name')
            ->addArgument('token', InputArgument::REQUIRED, 'Payment method Token')
            ->addArgument('currencies', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Currencies')
            ->setDescription('Create telegram bot payment method')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $username = $input->getArgument('username');
            $bot = $this->botRepository->findOneByUsername($username);
            if ($bot === null) {
                throw new TelegramNotFoundException($username);
            }

            $methodName = $input->getArgument('name');
            $name = TelegramPaymentMethodName::fromName($methodName);
            if ($name === null) {
                throw new TelegramPaymentMethodNotFoundException($methodName);
            }

            $paymentMethodTransfer = new TelegramPaymentMethodTransfer(
                $bot,
                $name,
                $input->getArgument('token'),
                $input->getArgument('currencies')
            );

            $paymentMethod = $this->creator->createTelegramPaymentMethod($paymentMethodTransfer);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $row = $this->infoProvider->getTelegramPaymentInfo($paymentMethod);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Telegram bot payment method has been created');

        return Command::SUCCESS;
    }
}