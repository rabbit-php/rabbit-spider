<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CheckerProxyIP
 * @package Spider\Proxy\Domains
 */
class CheckerProxyIP extends AbstractDomain
{
    public static string $tableSelector = '';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "https://checkerproxy.net/api/archive/" . date('Y-m-d', strtotime('-1day', time()))
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        $data = (array)json_decode($node->html(), true);
        $ips = array_column($data, 'addr');
        unset($html, $data);
        $rows = [];
        foreach ($ips as $line) {
            list($ip, $port) = explode(":", $line);
            $anonymity = 1;
            $protocol = "http";
            $rows[] = [ip2long($ip), $ip, $port, $anonymity, $protocol, ''];
        }
        return $rows;
    }
}