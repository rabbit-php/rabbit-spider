<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class NiMaDaiLi
 * @package Spider\Domains
 */
class NiMaDaiLi extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@class="mt-0 mb-2 table-responsive"]/table/tbody/tr';
    }

    public function getTypes(): array
    {
        return [1, 2, 3, 4];
    }
    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type): Generator
    {
        $ha = '';
        switch ($type) {
            case 1:
                $ha = 'gaoni';
                break;
            case 2:
                $ha = 'putong';
                break;
            case 3:
                $ha = 'http';
                break;
            default:
                $ha = 'https';
        }
        yield "http://www.nimadaili.com/{$ha}/{$i}";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        [$ip, $port] = explode(':', $node->filterXPath('.//td[1]')->text());
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "普通") !== false ? 1 : 2;
        $protocol = strpos(strtolower($node->filterXPath('.//td[2]')->text()), 'https') !== false ? 'https' : 'http';
        $location = $node->filterXPath('.//td[4]')->text();
        return [ip2long($ip), $ip, (int)$port, $anonymity, $protocol, $location];
    }

    public function getPages(Crawler $crawler): int
    {
        $pages = [];
        $page = $crawler->filterXPath('//*/body/div/div[1]/nav/ul/li');
        $page->each(function (Crawler $node) use (&$pages) {
            $pages[] = (int)$node->filterXPath('.//a')->text('0');
        });
        return max($pages);
    }
}
