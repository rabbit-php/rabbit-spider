<?php
declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class KuaiDaiLi
 * @package Spider\Domains
 */
class KuaiDaiLi extends AbstractDomain
{
    public static string $tableSelector = '//*[@id="list"]/table/tbody/tr';

    /**
     * @return string[]
     */
    public static function getUrls(): array
    {
        return [
            "https://www.kuaidaili.com/free/inha/",
            "https://www.kuaidaili.com/free/inha/2/",
            "https://www.kuaidaili.com/free/inha/3/",
            "https://www.kuaidaili.com/free/inha/4/",
            "https://www.kuaidaili.com/free/inha/5/",
            "https://www.kuaidaili.com/free/intr/",
            "https://www.kuaidaili.com/free/intr/2/",
            "https://www.kuaidaili.com/free/intr/3/",
            "https://www.kuaidaili.com/free/intr/4/",
            "https://www.kuaidaili.com/free/intr/5/",
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
        $anonymity = $node->filterXPath('.//td[3]')->text() === "高匿名" ? 2 : 1;
        $protocol = strtolower($node->filterXPath('.//td[4]')->text());
        $location = $node->filterXPath('.//td[5]')->text();
        return [ip2long($ip), $ip, $port, $anonymity, $protocol, $location];
    }
}