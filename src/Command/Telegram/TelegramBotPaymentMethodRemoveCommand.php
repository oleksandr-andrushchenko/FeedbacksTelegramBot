<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramPaymentMethodName;
use App\Exception\Telegram\Payment\TelegramPaymentMethodNotFoundException;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Repository\Telegram\TelegramPaymentMethodRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class TelegramBotPaymentMethodRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $botRepository,
        private readonly TelegramPaymentMethodRepository $repository,
        private readonly EntityManagerInterface $entityManager,
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
            ->setDescription('Remove telegram bot payment method')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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

            $paymentMethod = $this->repository->findOneActiveByBotAndName($bot, $name);
            if ($paymentMethod === null) {
                throw new TelegramPaymentMethodNotFoundException($methodName);
            }

            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(
                    sprintf(
                        'Are you sure you want to remove "%s" telegram bot\'s "%s" payment method?',
                        $bot->getUsername(),
                        $paymentMethod->getName()->name
                    ),
                    true
                )
            );

            if (!$confirmed) {
                $io->info(
                    sprintf(
                        '"%s" telegram bot\'s "%s" payment method removing has been cancelled',
                        $bot->getUsername(),
                        $paymentMethod->getName()->name
                    )
                );

                return Command::SUCCESS;
            }

            $paymentMethod->setDeletedAt(new DateTimeImmutable());
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            sprintf(
                '"%s" Telegram bot\'s "%s" payment method has been removed',
                $bot->getUsername(),
                $paymentMethod->getName()->name
            )
        );

        return Command::SUCCESS;
    }
}