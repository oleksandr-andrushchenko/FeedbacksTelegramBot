<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Lookup\LookupProcessorName;
use App\Service\Lookup\Lookuper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotLookupCommand extends Command
{
    public function __construct(
        private readonly Lookuper $lookuper,
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
            ->addOption('country', mode: InputOption::VALUE_REQUIRED, description: 'Context country')
            ->addOption('full', mode: InputOption::VALUE_NONE, description: 'Context country')
            ->setDescription('Lookup across processors for Telegram')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $term = $input->getArgument('term');
        $termType = SearchTermType::fromName($input->getArgument('type'));

        $searchTerm = new FeedbackSearchTerm($term, $term, $termType);
        $render = static fn (string $message) => $io->text($message);
        $context = [
            'countryCode' => $input->getOption('country'),
            'full' => $input->getOption('full'),
        ];
        $processors = array_map(
            static fn (string $processor): LookupProcessorName => LookupProcessorName::from($processor),
            $input->getOption('processor')
        );

        $this->lookuper->lookup($searchTerm, $render, $context, $processors);

        $io->success('Lookup has been completed');

        return Command::SUCCESS;
    }
}