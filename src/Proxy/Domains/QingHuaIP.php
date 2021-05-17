<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class QingHuaIP
 * @package Spider\Domains
 */
class QingHuaIP extends AbstractDomain
{
    public static string $tableSelector = '//*[@class="table-responsive-md"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.qinghuadaili.com/free/1/",
            "http://www.qinghuadaili.com/free/2/",
            "http://www.qinghuadaili.com/free/3/",
            "http://www.qinghuadaili.com/free/4/",
            "http://www.qinghuadaili.com/free/5/",
            "http://www.qinghuadaili.com/free/6/",
            "http://www.qinghuadaili.com/free/7/",
            "http://www.qinghuadaili.com/free/8/",
            "http://www.qinghuadaili.com/free/9/",
            "http://www.qinghuadaili.com/free/10/",
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
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "高匿") !== false ? 2 : 1;
        $protocol = strtolower($node->filterXPath('.//td[4]')->text());
        $location = $node->filterXPath('.//td[5]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}