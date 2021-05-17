<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IP89
 * @package Spider\Domains
 */
class IP89 extends AbstractDomain
{
    public static string $tableSelector = '//*[@class="layui-form"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.89ip.cn/index_1.html",
            "http://www.89ip.cn/index_2.html",
            "http://www.89ip.cn/index_3.html",
            "http://www.89ip.cn/index_4.html",
            "http://www.89ip.cn/index_5.html",
            "http://www.89ip.cn/index_6.html",
            "http://www.89ip.cn/index_7.html",
            "http://www.89ip.cn/index_8.html",
            "http://www.89ip.cn/index_9.html",
            "http://www.89ip.cn/index_10.html",
            "http://www.89ip.cn/index_11.html",
            "http://www.89ip.cn/index_12.html",
            "http://www.89ip.cn/index_13.html",
            "http://www.89ip.cn/index_14.html",
            "http://www.89ip.cn/index_15.html",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        $ip = $node->filterXPath('.//td[1]')->text();
        $port = $node->filterXPath('.//td[2]')->text();
        $anonymity = 2;
        $protocol = 'http';
        $location = $node->filterXPath('.//td[3]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }

}