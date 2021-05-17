<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use App\Tasks\Spider\Manager\ProxyManager;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Base\Helper\ArrayHelper;

class CustomerProxy extends AbstractSource
{
    protected array $ips = [];

    protected bool $start = false;

    protected int $checktime = 120 * 1000;

    public function __construct(string $ips)
    {
        $this->release = false;
        foreach (explode(',', $ips) as $ip) {
            $this->ips[] = $ip;
        }
    }

    public function loadIP(ProxyManager $manager): void
    {
        if (!$this->start) {
            $this->start = true;
            foreach ($this->ips as $ip) {
                $res = [];
                $parsed_url = parse_url($ip);
                $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
                $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
                $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
                $res['num'] = isset($parsed_url['path']) ? (int)str_replace('/', '', $parsed_url['path']) : 3;
                parse_str($parsed_url['query'] ?? '', $query);
                $res['checktime'] = ArrayHelper::getValue($query, 'checktime', $this->checktime);
                $res['ip2long'] = ip2long($res['ip']);
                $res['source'] = $this->source;
                $res['release'] = $this->release;
                $res['duration'] = 1;
                foreach ($manager->attributes as $key) {
                    $res[$key] ??= null;
                }
                $ip = new IP($res);
                $this->idle[] = $ip;
            }
        }
    }

    public function update(string $domain, IP $ip): void
    {
        throw new NotSupportedException("CustomerProxy no need update ip");
    }

    public function flush(ProxyManager $manager): void
    {
        throw new NotSupportedException("flush no need update ip");
    }
}
