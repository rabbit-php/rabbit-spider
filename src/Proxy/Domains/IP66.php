<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IP89
 * @package Spider\Domains
 */
class IP66 extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@id="main"]/div[1]/div[2]/div[1]/table/tr';
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
        yield "http://www.66ip.cn/{$i}.html";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        if ($ip === 'ip') {
            return [];
        }
        $port = $node->filterXPath('.//td[2]')->text();
        $location = $node->filterXPath('.//td[3]')->text();
        return [ip2long($ip), $ip, (int)$port, 2, 'http', $location];
    }

    public function getPages(Crawler $crawler): int
    {
        $pages = [];
        $page = $crawler->filterXPath('//*[@id="PageList"]/a');
        $page->each(function (Crawler $node) use (&$pages) {
            $pages[] = (int)$node->filterXPath('.//a')->text('0');
        });
        return max($pages);
    }
}
