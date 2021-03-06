<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Helper\ArrayHelper;

class CustomerProxy extends AbstractSource
{
    protected array $ips = [];

    protected int $checktime = 120 * 1000;

    public function __construct(string $ips, protected ?IInnerProxy $service = null)
    {
        foreach (explode(',', $ips) as $ip) {
            $this->ips[] = $ip;
        }
    }

    public function loadIP(): void
    {
        $this->flush();
        foreach ($this->ips as $ip) {
            $this->buildProxy($ip);
        }
        if ($this->service !== null) {
            foreach ($this->service->getProxys() as $ip) {
                $this->buildProxy($ip);
            }
        }
        $this->run();
    }

    private function buildProxy(string $ip): void
    {
        $res = [];
        $parsed_url = parse_url($ip);
        $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
        $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
        $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
        $res['num'] = isset($parsed_url['path']) ? (int)str_replace('/', '', $parsed_url['path']) : 3;
        parse_str($parsed_url['query'] ?? '', $query);
        $res['checktime'] = ArrayHelper::getValue($query, 'checktime', $this->checktime);
        $res['ip2long'] = (int)ip2long($res['ip']);
        $res['source'] = $this->source;
        $res['release'] = $this->release;
        $res['timeout'] = $this->timeout;
        $res['duration'] = 1;
        foreach ($this->manager->attributes as $key) {
            $res[$key] ??= null;
        }
        $key = "{$res['ip']}:{$res['port']}";
        if (!($this->idle[$key] ?? false)) {
            $this->idle[$key] = new IP($res, $this);
        }
    }
}
