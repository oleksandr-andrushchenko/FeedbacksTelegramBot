<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Service\Intl\LanguageTranslationsProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use RuntimeException;

class LanguagesUpdateCommand extends Command
{
    public function __construct(
        private readonly LanguageTranslationsProviderInterface $translationsProvider,
        private readonly string $translationTargetFile,
        private readonly array $supportedLocales,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Update language translations')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $translations = $this->translationsProvider->getLanguageTranslations();

            if ($translations === null) {
                throw new RuntimeException('Unable to fetch language translations');
            }

            foreach ($translations as $locale => $data) {
                if (!in_array($locale, $this->supportedLocales, true)) {
                    continue;
                }

                $yaml = '';
                foreach ($data as $language => $translation) {
                    $yaml .= sprintf("%s: %s\r\n", $language, $translation);
                }

                $written = file_put_contents(str_replace('{locale}', $locale, $this->translationTargetFile), $yaml);

                if ($written === false) {
                    throw new RuntimeException(sprintf('Unable to write "%s" language translation', $locale));
                }

                $io->note(json_encode([
                    'locale' => $locale,
                    'translations' => array_keys($data),
                ]));
            }
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->newLine();
        $io->success('Languages have been updated');

        return Command::SUCCESS;
    }
}