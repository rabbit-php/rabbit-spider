<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class XiLaIP
 * @package Spider\Domains
 */
class Data5u extends AbstractDomain
{
    public static string $tableSelector = '';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://api.ip.data5u.com/dynamic/get.html?order=6242cc5a85d44b8a6cdf5622a97ea761&json=1&random=0&sep=5",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        $neirong = $node->filterXPath(".//p")->text('');
        $data = (array)json_decode($neirong, true);
        $rows = [];
        if($data['success']) {
            foreach ($data['data'] as $line) {
                $ip = $line['ip'];
                $port = $line['port'];
                $ttl = $line['ttl'];      // 有效期
                $anonymity = 1;
                $protocol = "http";
                $rows[] = [ip2long($ip), $ip, $port, $anonymity, $protocol, ''];
            }
        }
        return $rows;
    }
}