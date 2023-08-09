<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Service\Intl\CountriesProviderInterface;
use App\Service\Intl\CountryTranslationsProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use RuntimeException;

class CountriesUpdateCommand extends Command
{
    public function __construct(
        private readonly CountriesProviderInterface $provider,
        private readonly NormalizerInterface $normalizer,
        private readonly string $targetFile,
        private readonly CountryTranslationsProviderInterface $translationsProvider,
        private readonly string $translationTargetFile,
        private readonly array $supportedLocales,
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
            ->setDescription('Update countries and country translations')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->updateCountries($io);
            $this->updateCountryTranslations($io);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->newLine();
        $io->success('Countries have been updated');

        return Command::SUCCESS;
    }

    private function updateCountries(SymfonyStyle $io): void
    {
        $countries = $this->provider->getCountries();

        if ($countries === null) {
            throw new RuntimeException('Unable to fetch countries');
        }

        $json = json_encode(array_map(fn ($country) => $this->normalizer->normalize($country), $countries));

        $written = file_put_contents($this->targetFile, $json);

        if ($written === false) {
            throw new RuntimeException('Unable to write countries');
        }

        $io->note($json);
    }

    private function updateCountryTranslations(SymfonyStyle $io): void
    {
        $translations = $this->translationsProvider->getCountryTranslations();

        if ($translations === null) {
            throw new RuntimeException('Unable to fetch country translations');
        }

        foreach ($translations as $locale => $data) {
            if (!in_array($locale, $this->supportedLocales, true)) {
                continue;
            }

            $yaml = '';
            foreach ($data as $country => $translation) {
                $yaml .= sprintf("%s: %s\r\n", $country, $translation);
            }

            $written = file_put_contents(str_replace('{locale}', $locale, $this->translationTargetFile), $yaml);

            if ($written === false) {
                throw new RuntimeException(sprintf('Unable to write "%s" country translation', $locale));
            }

            $io->note(json_encode([
                'locale' => $locale,
                'translations' => array_keys($data),
            ]));
        }
    }
}