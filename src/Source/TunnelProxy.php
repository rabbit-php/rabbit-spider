<?php

declare(strict_types=1);

namespace Rabbit\Spider\Source;

use Rabbit\Base\Helper\ArrayHelper;

class TunnelProxy extends AbstractSource
{
    protected bool $start = false;

    public function __construct(protected string $ip)
    {
        $this->source = -2;
    }

    public function loadIP(bool $auto = false): void
    {
        $this->flush();
        $res = [];
        $parsed_url = parse_url($this->ip);
        $res['ip'] = isset($parsed_url['host']) ? $parsed_url['host'] : null;
        $res['port'] = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
        $res['user'] = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $res['pass'] = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
        parse_str($parsed_url['query'] ?? '', $query);
        $res['num'] = (int)ArrayHelper::getValue($query, 'num', $this->num);
        if (false === (bool)ArrayHelper::getValue($query, 'auto', $auto)) {
            return;
        }
        $res['ip2long'] = 0;
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
        $this->run();
    }
}
