<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprise;
use App\Entity\Search\BusinessGuide\BusinessGuideEnterprises;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\CrawlerProvider;
use DOMNode;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * @see https://business-guide.com.ua/enterprises?q=0636356979&Submit=%CF%EE%F8%F3%EA
 * @see https://8000994519.business-guide.com.ua/
 */
class BusinessGuideSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly CrawlerProvider $crawlerProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::business_guide;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type === SearchTermType::phone_number) {
            if (!str_starts_with($term, '380')) {
                return false;
            }

            return true;
        }

        if ($type === SearchTermType::person_name) {
            if (count(explode(' ', $term)) === 1) {
                return false;
            }

            return true;
        }

        if ($type === SearchTermType::organization_name) {
            return true;
        }

        if ($type === SearchTermType::place_name) {
            return true;
        }

        return false;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type === SearchTermType::phone_number) {
            if (str_starts_with($term, '380')) {
                $term = substr($term, 2);
            }
        }

        $enterprises = $this->tryCatch(fn () => $this->searchEnterprises($term), null);

        if ($enterprises === null) {
            return [];
        }

        if (count($enterprises->getItems()) === 1) {
            sleep(2);
            $url = $enterprises->getItems()[0]->getHref();

            $enterprise = $this->tryCatch(fn () => $this->searchEnterprise($url), []);

            return [
                $enterprise,
            ];
        }

        return [
            $enterprises,
        ];
    }

    private function tryCatch(callable $job, mixed $failed): mixed
    {
        try {
            return $job();
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return $failed;
        }
    }

    private function searchEnterprises(string $term): ?BusinessGuideEnterprises
    {
        $parameters = ['q' => $term, 'Submit' => 'Пошук'];
        $encoding = 'Windows-1251';
        $parameters = array_map(static fn (string $parameter): string => mb_convert_encoding($parameter, $encoding), $parameters);
        $url = 'https://business-guide.com.ua/enterprises?' . http_build_query($parameters);
        $contentModifier = static fn (string $content): string => '<div><div ' . mb_convert_encoding(explode('<div class="rightSideBar"', explode('<div id="popup_bug"', $content)[1])[0], 'UTF-8', $encoding);

        $crawler = $this->crawlerProvider->getCrawler('GET', $url, contentModifier: $contentModifier, user: true);

        $items = $crawler->filter('table.firmTable')->each(static function (Crawler $item): ?BusinessGuideEnterprise {
            $nameEl = $item->filter('.firmZag a');

            if ($nameEl->count() === 0) {
                return null;
            }

            $name = $nameEl->text();
            $href = $nameEl->attr('href');
            $href = str_starts_with($href, 'http') ? $href : ('https:' . $href);

            if (empty($name)) {
                return null;
            }

            $descEl = $item->filter('.firmOpis');

            if ($descEl->count() !== 0) {
                $desc = $descEl->text();
            }

            $addressEl = $item->filter('.firmCity');

            if ($addressEl->count() !== 0) {
                $address = $addressEl->text();
            }

            return new BusinessGuideEnterprise(
                $name,
                $href,
                desc: empty($desc) ? null : $desc,
                address: empty($address) ? null : $address
            );
        });

        $items = array_filter($items);

        return count($items) === 0 ? null : new BusinessGuideEnterprises($items);
    }

    private function getEnterpriseCrawler(string $url): Crawler
    {
        return $this->crawlerProvider->getCrawler('GET', $url);
    }

    private function searchEnterprise(string $url): ?BusinessGuideEnterprise
    {
        $crawler = $this->getEnterpriseCrawler($url);

        $nameEl = $crawler->filter('.kartkaNazva h1');

        if ($nameEl->count() === 0) {
            return null;
        }

        $name = $nameEl->text();

        if (empty($name)) {
            return null;
        }

        $blocksEl = $crawler->filter('.kartkaTd');

        $leftBlockEls = $blocksEl->eq(0)->children();

        $countryEl = $leftBlockEls->eq(0);

        if ($countryEl->count() !== 0) {
            $country = $countryEl->text();
        }

        $addressLabelEl = $leftBlockEls->eq(1);
        $addressEl = $leftBlockEls->eq(2);

        if ($addressLabelEl->count() !== 0 && str_contains($addressLabelEl->text(), 'Адреса') && $addressEl->count() !== 0) {
            $address = $addressEl->text();
        }

        $phoneLabelEl = $leftBlockEls->eq(3);
        $phoneEl = $leftBlockEls->eq(4);

        if ($phoneLabelEl->count() !== 0 && str_contains($phoneLabelEl->text(), 'Телефон') && $phoneEl->count() !== 0) {
            $phone = $phoneEl->text();
        }

        $ceoLabelEl = $leftBlockEls->eq(5);
        $ceoEl = $leftBlockEls->eq(6);

        if ($ceoLabelEl->count() !== 0 && str_contains($ceoLabelEl->text(), 'Керівник') && $ceoEl->count() !== 0) {
            $ceo = $ceoEl->text();
        }

        $rightBlockEls = $blocksEl->eq(1)->children();

        $numberEl = $rightBlockEls->eq(2);

        if ($numberEl->count() !== 0 && str_contains($numberEl->text(), 'Реєстраційний номер підприємства')) {
            preg_match('/[0-9]{8,}/', $numberEl->text(), $m);
            $number = isset($m, $m[0]) ? $m[0] : null;
        }

        $sectorsEl = $crawler->filter('#block4 a');

        if ($crawler->filter('#block4 a')->count() !== 0) {
            $sectors = array_map(static fn (DOMNode $node): string => $node->textContent, $sectorsEl->getIterator()->getArrayCopy());
        }

        return new BusinessGuideEnterprise(
            $name,
            $url,
            country: empty($country) ? null : $country,
            phone: empty($phone) ? null : $phone,
            ceo: empty($ceo) ? null : $ceo,
            sectors: empty($sectors) ? null : $sectors,
            desc: empty($desc) ? null : $desc,
            address: empty($address) ? null : $address,
            number: empty($number) ? null : $number,
        );
    }
}
