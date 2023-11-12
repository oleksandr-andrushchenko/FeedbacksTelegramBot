<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Lookup\LookupProcessorName;
use App\Enum\Messenger\Messenger;
use App\Service\Lookup\Processor\LookupProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotLookupCommand extends Command
{
    public function __construct(
        private readonly LookupProcessor $telegramLookupProcessor,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('term', InputArgument::REQUIRED, 'Search term')
            ->addArgument('type', InputArgument::REQUIRED, 'Search term type')
            ->addOption('processor', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Processor (-s)')
            ->setDescription('Lookup across processors for Telegram')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $term = $input->getArgument('term');
        $termType = SearchTermType::fromName($input->getArgument('type'));

        $feedbackSearch = new FeedbackSearch(
            'any-id',
            new User('any-id'),
            new MessengerUser('any-id', Messenger::telegram, 'any-identifier'),
            new FeedbackSearchTerm($term, $term, $termType),
        );
        $render = static fn (string $message) => $io->text($message);
        $context = [];
        $processors = array_map(
            static fn (string $processor): LookupProcessorName => LookupProcessorName::fromName($processor),
            $input->getOption('processor')
        );

        $this->telegramLookupProcessor->processLookup($feedbackSearch, $render, $context, $processors);

        $io->success('Lookup has been completed');

        return Command::SUCCESS;
    }
}