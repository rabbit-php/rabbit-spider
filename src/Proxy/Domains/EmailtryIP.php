<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Rabbit\Base\App;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Class EmailtryIP
 * @package Spider\Domains
 */
class EmailtryIP extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@id="proxy-table1"]/tr';
    }

    public function getTypes(): array
    {
        return [1];
    }
    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type): Generator
    {
        yield "http://emailtry.com/index/{$i}";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        $hostArr = explode(':', $node->filterXPath('.//td[1]')->text());
        $ip = array_shift($hostArr);
        $port = array_shift($hostArr) ?? 80;
        $anonymity = strpos($node->filterXPath('.//td[2]')->text(), "High") !== false ? 2 : 1;
        $location = $node->filterXPath('.//td[3]')->text() . ' ' . $node->filterXPath('.//td[4]')->text();
        return [ip2long($ip), $ip, (int)$port, $anonymity, 'http', $location];
    }

    public function getPages(Crawler $crawler): int
    {
        try {
            $arr = explode(' ', $crawler->filterXPath('//*/body/div[2]/div/p[1]/span[1]')->text(' 1 '));
            return (int)$arr[1];
        } catch (Throwable $e) {
            App::error("Get pages error!");
            return 1;
        }
    }
}
