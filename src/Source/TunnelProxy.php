<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Base\Helper\ArrayHelper;

class TunnelProxy extends AbstractSource
{
    protected array $ips = [];

    protected bool $start = false;

    public function __construct(string $ips)
    {
        parent::__construct();
        $this->release = false;
        foreach (explode(',', $ips) as $ip) {
            $this->ips[] = $ip;
        }
    }

    public function loadIP(): void
    {
        if (!$this->start) {
            $this->start = true;
            foreach ($this->ips as $i => $ip) {
                $res = [];
                $parsed_url = parse_url($ip);
                $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
                $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
                $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
                parse_str($parsed_url['query'] ?? '', $query);
                $res['num'] = ArrayHelper::getValue($query, 'num', $this->num);
                $res['ip2long'] = $i;
                $res['source'] = $this->source;
                $res['release'] = $this->release;
                $res['timeout'] = $this->timeout;
                $res['duration'] = 1;
                foreach ($this->manager->attributes as $key) {
                    $res[$key] ??= null;
                }
                $key = "{$res['ip']}:{$res['port']}";
                $this->idle[$key] = $tmp = new IP($this, $res);
                $this->run($tmp);
            }
        }
    }

    public function flush(): void
    {
        throw new NotSupportedException("flush no need update ip");
    }
}
