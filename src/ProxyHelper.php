<?php

declare(strict_types=1);

namespace Rabbit\Spider;

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

    protected LocalCtrl $local;

    public function __construct(ProxyManager $manager, LocalCtrl $local = null)
    {
        $this->manager = $manager;
        $this->local = $local ?? new LocalCtrl(new IP([
            'ip' => '127.0.0.1',
            'timeout' => $manager->timeout,
            'release' => false
        ]));
        $this->local->setManager($manager);
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
        return $this->local->proxy($url, $headers);
    }
}
