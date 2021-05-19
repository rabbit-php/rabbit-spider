<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Rabbit\Base\App;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Class IP3366
 * @package Spider\Domains
 */
class IP3366 extends AbstractDomain
{
    public function __construct()
    {
        $this->tableSelector = '//*[@id="list"]/table/tbody/tr';
    }

    public function getTypes(): array
    {
        return [1, 2];
    }
    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type = 1): Generator
    {
        yield "http://www.ip3366.net/free/?stype={$type}&page={$i}";
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "高匿") !== false ? 2 : 1;
        $protocol = strtolower($node->filterXPath('.//td[4]')->text());
        $location = $node->filterXPath('.//td[5]')->text();
        return [ip2long($ip), $ip, (int)$port, $anonymity, $protocol, $location];
    }

    public function getPages(Crawler $crawler): int
    {
        try {
            $page = explode('/', $crawler->filterXPath('//*[@id="listnav"]/ul/strong')->text('1/1'));
            return (int)end($page);
        } catch (Throwable $e) {
            App::error("Get page error!");
            return 1;
        }
    }
}
