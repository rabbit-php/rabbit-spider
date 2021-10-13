<?php

declare(strict_types=1);

namespace Rabbit\Spider\Agents;

class UserAgent
{
    private static array $agentDetails;

    public static function random(array $filterBy = []): string
    {
        $agents = self::loadUserAgents($filterBy);

        if (empty($agents)) {
            throw new \Exception('No user agents matched the filter');
        }

        return $agents[mt_rand(0, count($agents) - 1)];
    }

    public static function getDeviceTypes(): array
    {
        return self::getField('device_type');
    }

    public static function getAgentTypes(): array
    {
        return self::getField('agent_type');
    }

    public static function getAgentNames(): array
    {
        return self::getField('agent_name');
    }

    public static function getOSTypes(): array
    {
        return self::getField('os_type');
    }

    public static function getOSNames(): array
    {
        return self::getField('os_name');
    }

    private static function getField(string $fieldName): array
    {
        $agentDetails = self::getAgentDetails();
        $values       = [];

        foreach ($agentDetails as $agent) {
            if (!isset($agent[$fieldName])) {
                throw new \Exception("Field name \"$fieldName\" not found, can't continue");
            }

            $values[] = $agent[$fieldName];
        }

        return array_values(array_unique($values));
    }

    private static function validateFilter(array $filterBy = []): array
    {
        // Components of $filterBy that will not be ignored
        $filterParams = [
            'agent_name',
            'agent_type',
            'device_type',
            'os_name',
            'os_type',
        ];

        $outputFilter = [];

        foreach ($filterParams as $field) {
            if (!empty($filterBy[$field])) {
                $outputFilter[$field] = $filterBy[$field];
            }
        }

        return $outputFilter;
    }

    private static function loadUserAgents(array $filterBy = []): array
    {
        $filterBy = self::validateFilter($filterBy);

        $agentDetails = self::getAgentDetails();
        $agentStrings = [];

        for ($i = 0; $i < count($agentDetails); $i++) {
            foreach ($filterBy as $key => $value) {
                if (!isset($agentDetails[$i][$key]) || !self::inFilter($agentDetails[$i][$key], (array)$value)) {
                    continue 2;
                }
            }
            $agentStrings[] = $agentDetails[$i]['agent_string'];
        }

        return array_values($agentStrings);
    }

    private static function inFilter(string $key, array $array): bool
    {
        return in_array(strtolower($key), array_map('strtolower', $array));
    }

    private static function getAgentDetails(): array
    {
        if (!isset(self::$agentDetails)) {
            sync('proxy.useragent', fn () => self::$agentDetails = json_decode(file_get_contents(__DIR__ . '/agent_list.json'), true));
        }

        return self::$agentDetails;
    }
}
