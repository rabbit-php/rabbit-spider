<?php

declare(strict_types=1);

namespace Rabbit\Spider\Proxy\Domains;

use Generator;
use Swlib\SaberGM;
use Symfony\Component\DomCrawler\Crawler;
use v8js;

class OpenProxy extends AbstractDomain
{
    protected ?array $list = null;

    protected v8js $v8js;

    public function __construct()
    {
        $this->v8js = new v8js();
    }

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
        $html = $node->filterXPath('//*/body/script[1]')->html();
        $html = str_replace('window.__NUXT__=', '', $html);
        $data = $this->v8js->executeString($html)->data;
        foreach (current($data)->data as $item) {
            foreach ($item->items as $ip) {
                if (strpos($ip, ":") === false) {
                    continue;
                }
                list($ip, $port) = explode(":", $ip);
                $rows[] = [ip2long($ip), $ip, $port, 1, 'http', $item->code];
            }
        }
        return $rows;
    }

    public function getPages(Crawler $crawler): int
    {
        return count($this->list);
    }
}
