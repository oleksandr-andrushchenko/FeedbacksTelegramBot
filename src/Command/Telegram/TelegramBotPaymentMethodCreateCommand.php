<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramPaymentMethodName;
use App\Exception\Telegram\Payment\TelegramPaymentMethodNotFoundException;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Object\Telegram\Payment\TelegramPaymentMethodTransfer;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Payment\TelegramPaymentMethodCreator;
use App\Service\Telegram\Payment\TelegramPaymentMethodInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('username', mode: InputOption::VALUE_REQUIRED, description: 'Telegram bot username')
            ->addOption('name', mode: InputOption::VALUE_REQUIRED, description: 'Payment Method Name')
            ->addOption('token', mode: InputOption::VALUE_REQUIRED, description: 'Payment method Token')
            ->addOption('currencies', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Currencies')
            ->setDescription('Create telegram bot payment method')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $username = $input->getOption('username');
            $bot = $this->botRepository->findOneByUsername($username);
            if ($bot === null) {
                throw new TelegramNotFoundException($username);
            }

            $methodName = $input->getOption('name');
            $name = TelegramPaymentMethodName::fromName($methodName);
            if ($name === null) {
                throw new TelegramPaymentMethodNotFoundException($methodName);
            }

            $paymentMethodTransfer = new TelegramPaymentMethodTransfer(
                $bot,
                $name,
                $input->getOption('token'),
                $input->getOption('currencies')
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