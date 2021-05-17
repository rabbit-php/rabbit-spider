<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class XiLaIP
 * @package Spider\Domains
 */
class XiLaIP extends AbstractDomain
{
    public static string $tableSelector = '//*[@class="mt-0 mb-2 table-responsive"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "http://www.xiladaili.com/gaoni/",
            "http://www.xiladaili.com/gaoni/2/",
            "http://www.xiladaili.com/gaoni/3/",
            "http://www.xiladaili.com/gaoni/4/",
            "http://www.xiladaili.com/gaoni/5/",
            "http://www.xiladaili.com/gaoni/6/",
            "http://www.xiladaili.com/putong/",
            "http://www.xiladaili.com/putong/2/",
            "http://www.xiladaili.com/putong/3/",
            "http://www.xiladaili.com/putong/4/",
            "http://www.xiladaili.com/putong/5/",
            "http://www.xiladaili.com/putong/6/",
        ];
    }

    /**
     * @param Crawler $node
     * @return array
     */
    public static function buildData(Crawler $node): array
    {
        [$ip, $port] = explode(':', $node->filterXPath('.//td[1]')->text());
        $anonymity = strpos($node->filterXPath('.//td[3]')->text(), "高匿") !== false ? 2 : 1;
        $protocol = strpos(strtolower($node->filterXPath('.//td[2]')->text()), 'https') !== false ? 'https' : 'http';
        $location = $node->filterXPath('.//td[4]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}