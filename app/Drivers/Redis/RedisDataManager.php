<?php

namespace UniMan\Drivers\Redis;

use RedisProxy\RedisProxy;
use UniMan\Core\DataManager\AbstractDataManager;
use UniMan\Core\Utils\Filter;
use UniMan\Core\Utils\Multisort;
use UniMan\Drivers\Redis\RedisDatabaseAliasStorage;

class RedisDataManager extends AbstractDataManager
{
    private $connection;

    private $databaseAliasStorage;

    private $itemsCountCache = false;

    public function __construct(RedisProxy $connection, RedisDatabaseAliasStorage $databaseAliasStorage)
    {
        $this->connection = $connection;
        $this->databaseAliasStorage = $databaseAliasStorage;
    }

    public function databases(array $sorting = [])
    {
        $keyspace = $this->connection->info('keyspace');
        $aliases = $this->databaseAliasStorage->loadAll();
        $databases = [];
        foreach ($keyspace as $db => $info) {
            $db = str_replace('db', '', $db);
            $alias = isset($aliases[$db]) ? ' (' . $aliases[$db] . ')' : '';
            $info['database'] = $db . $alias;
            $databases[$db] = $info;
        }
        return Multisort::sort($databases, $sorting);
    }

    protected function getDatabaseNameColumn()
    {
        return 'database';
    }

    public function tablesCount()
    {
        $tables = [
            RedisDriver::TYPE_KEY => 0,
            RedisDriver::TYPE_HASH => 0,
            RedisDriver::TYPE_SET => 0,
        ];
        foreach ($this->connection->keys('*') as $key) {
            $type = $this->connection->type($key);
            switch ($type) {
                case RedisProxy::TYPE_STRING:
                    $tables[RedisDriver::TYPE_KEY]++;
                    break;
                case RedisProxy::TYPE_HASH:
                    $tables[RedisDriver::TYPE_HASH]++;
                    break;
                case RedisProxy::TYPE_SET:
                    $tables[RedisDriver::TYPE_SET]++;
                    break;
                default:
                    break;
            }
        }
        return $tables;
    }

    public function tables(array $sorting = [])
    {
        $tables = [
            RedisDriver::TYPE_KEY => [
                'list_of_all_keys' => [
                    'key' => 'Show all keys',
                    'number_of_keys' => 0,
                ]
            ],
            RedisDriver::TYPE_HASH => [],
            RedisDriver::TYPE_SET => [],
        ];
        foreach ($this->connection->keys('*') as $key) {
            $type = $this->connection->type($key);
            if ($type === RedisProxy::TYPE_STRING) {
                $tables[RedisDriver::TYPE_KEY]['list_of_all_keys']['number_of_keys']++;
            } elseif ($type === RedisProxy::TYPE_HASH) {
                $result = $this->connection->hlen($key);
                $tables[RedisDriver::TYPE_HASH][$key] = [
                    'key' => $key,
                    'number_of_fields' => $result,
                ];
            } elseif ($type === RedisProxy::TYPE_SET) {
                $result = $this->connection->scard($key);
                $tables[RedisDriver::TYPE_SET][$key] = [
                    'key' => $key,
                    'number_of_members' => $result,
                ];
            }
            // TODO list and sorted set
        }
        return [
            RedisDriver::TYPE_KEY => Multisort::sort($tables[RedisDriver::TYPE_KEY], $sorting),
            RedisDriver::TYPE_HASH => Multisort::sort($tables[RedisDriver::TYPE_HASH], $sorting),
            RedisDriver::TYPE_SET => Multisort::sort($tables[RedisDriver::TYPE_SET], $sorting),
        ];
    }

    public function itemsCount($type, $table, array $filter = [])
    {
        if ($this->itemsCountCache !== false) {
            return $this->itemsCountCache;
        }
        if ($type == RedisDriver::TYPE_HASH) {
            if (!$filter) {
                $this->itemsCountCache = $this->connection->hlen($table);
                return $this->itemsCountCache;
            } else {
                $totalItems = 0;
                foreach ($filter as $filterParts) {
                    if (isset($filterParts['key'][Filter::OPERATOR_EQUAL])) {
                        $res = $this->connection->hget($table, $filterParts['key'][Filter::OPERATOR_EQUAL]);
                        if ($res) {
                            $item = [
                                'key' => $filterParts['key'][Filter::OPERATOR_EQUAL],
                                'length' => strlen($res),
                                'value' => $res,
                            ];
                            if (Filter::apply($item, $filter)) {
                                $totalItems++;
                            }
                        }
                        $this->itemsCountCache = $totalItems;
                        return $this->itemsCountCache;
                    }
                }
                $iterator = '';
                do {
                    $pattern = null;
                    $res = $this->connection->hscan($table, $iterator, $pattern, 1000);
                    $res = $res ?: [];
                    foreach ($res as $key => $value) {
                        $item = [
                            'key' => $key,
                            'length' => strlen($value),
                            'value' => $value,
                        ];
                        if (Filter::apply($item, $filter)) {
                            $totalItems++;
                        }
                    }
                } while ($iterator !== 0);
                $this->itemsCountCache = $totalItems;
                return $this->itemsCountCache;
            }
        }
        if ($type == RedisDriver::TYPE_KEY) {
            $totalItems = 0;
            foreach ($this->connection->keys('*') as $key) {
                if ($this->connection->type($key) !== RedisProxy::TYPE_STRING) {
                    continue;
                }
                $result = $this->connection->get($key);
                $item = [
                    'key' => $key,
                    'value' => $result,
                    'length' => strlen($result),
                ];

                if (Filter::apply($item, $filter)) {
                    $totalItems++;
                }
            }
            return $totalItems;
        }
        if ($type == RedisDriver::TYPE_SET) {
            if (!$filter) {
                return $this->connection->scard($table);
            }
            $iterator = '';
            $totalItems = 0;
            do {
                $res = $this->connection->sscan($table, $iterator, null, 1000);
                $res = $res ?: [];
                foreach ($res as $member) {
                    $item = [
                        'member' => $member,
                        'length' => strlen($member),
                    ];
                    if (Filter::apply($item, $filter)) {
                        $totalItems++;
                    }
                }
            } while ($iterator !== 0);
            return $totalItems;
        }
        return 0;
    }

    public function items($type, $table, $page, $onPage, array $filter = [], array $sorting = [])
    {
        $items = [];
        $offset = ($page - 1) * $onPage;
        $skipped = 0;
        if ($type == RedisDriver::TYPE_HASH) {
            foreach ($filter as $filterParts) {
                if (isset($filterParts['key'][Filter::OPERATOR_EQUAL])) {
                    $items = [];
                    $res = $this->connection->hget($table, $filterParts['key'][Filter::OPERATOR_EQUAL]);
                    if ($res) {
                        $item = [
                            'key' => $filterParts['key'][Filter::OPERATOR_EQUAL],
                            'length' => strlen($res),
                            'value' => $res,
                        ];
                        if (Filter::apply($item, $filter)) {
                            $items[$item['key']] = $item;
                        }
                    }
                    return $items;
                }
            }


            $iterator = '';
            do {
                $pattern = null;
                $res = $this->connection->hscan($table, $iterator, $pattern, $onPage * 10);
                $res = $res ?: [];
                foreach ($res as $key => $value) {
                    $item = [
                        'key' => $key,
                        'length' => strlen($value),
                        'value' => $value,
                    ];
                    if (Filter::apply($item, $filter)) {
                        if ($skipped < $offset) {
                            $skipped++;
                        } else {
                            $items[$key] = $item;
                            if (count($items) === $onPage) {
                                break;
                            }
                        }
                    }
                }
            } while ($iterator !== 0 && count($items) < $onPage);
        } elseif ($type == RedisDriver::TYPE_KEY) {
            foreach ($this->connection->keys('*') as $key) {
                if ($this->connection->type($key) !== RedisProxy::TYPE_STRING) {
                    continue;
                }
                $result = $this->connection->get($key);

                $item = [
                    'key' => $key,
                    'value' => $result,
                    'length' => strlen($result),
                ];

                if (Filter::apply($item, $filter)) {
                    if ($skipped < $offset) {
                        $skipped++;
                    } else {
                        $items[$key] = $item;
                        if (count($items) === $onPage) {
                            break;
                        }
                    }
                }
            }
        } elseif ($type == RedisDriver::TYPE_SET) {
            $iterator = '';
            do {
                $pattern = null;
                $res = $this->connection->sscan($table, $iterator, $pattern, $onPage * 10);
                $res = $res ?: [];
                foreach ($res as $member) {
                    $item = [
                        'member' => $member,
                        'length' => strlen($member),
                    ];
                    if (Filter::apply($item, $filter)) {
                        $items[$member] = $item;
                        if (count($items) === $onPage) {
                            break;
                        }
                    }
                }
            } while ($iterator !== 0 && count($items) < $onPage);
        }

        if ($this->itemsCount($type, $table, $filter) <= $onPage) {
            $items = Multisort::sort($items, $sorting);
        } elseif ($sorting) {
            $this->addMessage('Sorting has not been applied because the number of items is greater then the limit. Increase the limit or modify the filter.');
        }

        return $items;
    }

    public function deleteItem($type, $table, $item)
    {
        if ($type == RedisDriver::TYPE_HASH) {
            return $this->connection->hdel($table, $item);
        }
        if ($type == RedisDriver::TYPE_KEY) {
            return $this->connection->del($item);
        }
        if ($type == RedisDriver::TYPE_SET) {
            return $this->connection->srem($table, $item);
        }
        return parent::deleteItem($type, $table, $item);
    }

    public function deleteTable($type, $table)
    {
        return $this->connection->del($table);
    }

    public function selectDatabase($database)
    {
        $this->connection->select($database);
    }

    public function execute($commands)
    {
        $listOfCommands = array_filter(array_map('trim', explode("\n", $commands)), function ($command) {
            return $command;
        });

        $results = [];
        foreach ($listOfCommands as $command) {
            $commandParts = explode(' ', $command);
            $function = array_shift($commandParts);
            $function = strtolower($function);
            $results[$command]['headers'] = $this->headers($function);
            $rows = call_user_func_array([$this->connection, $function], $commandParts);
            $items = $this->getItems($function, $rows);
            $results[$command]['items'] = $items;
            $results[$command]['count'] = count($items);
        }
        return $results;
    }

    private function headers($function)
    {
        if ($function === 'get' || $function === 'hget') {
            return ['value'];
        }
        if ($function === 'keys') {
            return ['key'];
        }
        if ($function === 'hgetall') {
            return ['key', 'value'];
        }
        if ($function === 'hlen') {
            return ['items_count'];
        }
        return [];
    }

    private function getItems($function, $rows)
    {
        $items = [];
        if ($function === 'keys') {
            foreach ($rows as $key) {
                $items[] = [$key];
            }
        } elseif ($function === 'hgetall') {
            foreach ($rows as $key => $value) {
                $items[] = [$key, $value];
            }
        } else {
            return [[$rows]];
        }
        return $items;
    }
}
