<?php

declare(strict_types=1);

namespace Rabbit\Spider;

use Rabbit\Base\Helper\ArrayHelper;
use Rabbit\Spider\Manager\LocalCtrl;
use Rabbit\Spider\Manager\ProxyManager;
use Rabbit\Spider\Source\IP;
use Throwable;

/**
 * Class ProxyHelper
 * @package Spider\Proxy
 */
class ProxyHelper
{
    public ProxyManager $manager;

    protected array $local = [];

    public function __construct(ProxyManager $manager, string $ips = null)
    {
        $this->manager = $manager;
        $ips = $ips ?? 'proxy://127.0.0.1';
        foreach (explode(',', $ips) as $ip) {
            $parsed_url = parse_url($ip);
            $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
            $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
            $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
            $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
            $res['num'] = isset($parsed_url['path']) ? (int)str_replace('/', '', $parsed_url['path']) : 3;
            parse_str($parsed_url['query'] ?? '', $query);
            $res['checktime'] = ArrayHelper::getValue($query, 'checktime', 60);
            $res['ip2long'] = ip2long($res['ip']);
            $res['source'] = 0;
            $res['release'] = false;
            $res['timeout'] = 10;
            $res['duration'] = 1;
            $local = new LocalCtrl(new IP($res));
            $local->setManager($manager);
            $this->local[] = $local;
        }
    }

    public function tunnel(string $url, string $tunnel, int $retry = 5): ?SpiderResponse
    {
        $tunnels = $this->manager->getTunnels();
        if (!array_key_exists($tunnel, $tunnels)) {
            return null;
        }
        $ctrl = $tunnels[$tunnel];
        while ($retry--) {
            try {
                $content = $ctrl->request($url);
                if ($content->code === SpiderResponse::CODE_VERCODE || $content->code === SpiderResponse::CODE_EMPTY) {
                    usleep(300 * 1000);
                    continue;
                }
                return $content;
            } catch (Throwable $e) {
                usleep(300 * 1000);
            }
        }
        return null;
    }

    public function getUrlContents(string $url, array $headers = []): ?SpiderResponse
    {
        return $this->local[array_rand($this->local)]->proxy($url, $headers);
    }
}
