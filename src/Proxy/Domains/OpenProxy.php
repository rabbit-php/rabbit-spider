<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Swlib\SaberGM;
use Symfony\Component\DomCrawler\Crawler;

class OpenProxy extends AbstractDomain
{
    protected ?array $list = null;

    public function getTypes(): array
    {
        return [1, 2, 3];
    }

    /**
     * @return string[]
     */
    public function getUrls(int $i, int $type): Generator
    {
        if ($this->list === null || count($this->list) === $i) {
            $this->list = [];
            for ($s = 0; $s < 10; $s++) {
                $skip = $s * 6;
                $time = (int)(microtime(true) * 1000);
                $items = SaberGM::get("https://api.openproxy.space/list?skip=$skip&ts=$time", ['use_pool' => true])->getParsedJsonArray();
                foreach ($items as $item) {
                    if ($item['protocols'] === [1, 2]) {
                        $this->list[] = $item['code'];
                    }
                }
            }
        }
        yield "https://openproxy.space/list/" . $this->list[$i - 1];
    }

    public function buildData(Crawler $node): ?array
    {
        $html = $node->filterXPath('//*/body/script[1]')->outerHtml();
        preg_match_all('/(?:(?:[0,1]?\d?\d|2[0-4]\d|25[0-5])\.){3}(?:[0,1]?\d?\d|2[0-4]\d|25[0-5]):\d{0,5}/', $html, $lines);
        if ($lines[0] ?? false) {
            $rows = [];
            foreach ($lines[0] as $line) {
                if (strpos($line, ":") === false) {
                    continue;
                }
                list($ip, $port) = explode(":", $line);
                $rows[] = [ip2long($ip), $ip, $port, 1, 'http', ''];
            }
            return $rows;
        }
        return null;
    }

    public function getPages(Crawler $crawler): int
    {
        return count($this->list);
    }
}
