<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Swlib\SaberGM;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CheckerProxyIP
 * @package Spider\Proxy\Domains
 */
class CheckerProxyIP extends AbstractDomain
{

    private int $day = 0;
    private int $total = 0;

    public function getTypes(): array
    {
        return [1];
    }
    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type): Generator
    {
        $day = $i - 1;
        yield "https://checkerproxy.net/api/archive/" . date('Y-m-d', strtotime("-{$day}day", time()));
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public function buildData(Crawler $node): ?array
    {
        $data = json_decode($node->text(), true);
        $rows = [];
        foreach ($data as $item) {
            list($ip, $port) = explode(":", $item['addr']);
            $rows[] = [ip2long($ip), $ip, $port, $item['kind'], $item['type'] === 1 ? 'http' : 'https', trim("{$item['addr_geo_iso']} {$item['addr_geo_country']} {$item['addr_geo_city']}")];
        }
        return $rows;
    }

    public function getPages(Crawler $crawler): int
    {
        $day = strtotime(date('Y-m-d', time()));
        if ($this->day !== $day) {
            $this->day = $day;
            $data = SaberGM::get("https://checkerproxy.net/api/archive/", ['use_pool' => true])->getParsedJsonArray();
            $this->total = count($data);
        }
        return $this->total;
    }
}
