<?php

namespace Adminerng\Drivers\Memcache;

use Adminerng\Core\AbstractDriver;
use Adminerng\Core\Column;
use Adminerng\Core\Exception\ConnectException;
use Adminerng\Drivers\Memcache\Forms\MemcacheKeyForm;
use Memcache;

class MemcacheDriver extends AbstractDriver
{
    const TYPE_KEY = 'key';

    public function extensions()
    {
        return ['memcache'];
    }

    public function type()
    {
        return 'memcache';
    }

    public function defaultCredentials()
    {
        return [
            'servers' => 'localhost:11211',
        ];
    }

    public function connect(array $credentials)
    {
        $this->connection = new Memcache();
        $servers = array_map('trim', explode("\n", trim($credentials['servers'])));
        foreach ($servers as $server) {
            list($host, $port) = explode(':', $server, 2);
            if (!$this->connection->addserver($host, $port)) {
                throw new ConnectException("Couldn't connect to $host:$port");
            }
        }
    }

    public function databasesHeaders()
    {
        $fields = [
            'server' => ['is_numeric' => false],
            'process_id' => ['is_numeric' => false],
            'uptime' => [],
            'current_items' => [],
            'total_items' => [],
            'size' => [],
            'active_slabs' => [],
            'total_malloced' => [],
        ];

        $columns = [];
        foreach ($fields as $key => $settings) {
            $column = (new Column())
                ->setKey($key)
                ->setTitle('memcache.headers.servers.' . $key)
                ->setIsSortable(true);
            if (!isset($settings['is_numeric'])) {
                $column->setIsNumeric(true);
            }
            $columns[] = $column;
        }
        return $columns;
    }

    public function tablesHeaders()
    {
        return [];
    }

    public function columns($type, $table)
    {
        $columns = [];
        if ($type == self::TYPE_KEY) {
            $columns[] = (new Column())
                ->setKey('key')
                ->setTitle('memcache.columns.' . $type . '.key');
            $columns[] = (new Column())
                ->setKey('value')
                ->setTitle('memcache.columns.' . $type . '.value');
            $columns[] = (new Column())
                ->setKey('size')
                ->setTitle('memcache.columns.' . $type . '.size');
            $columns[] = (new Column())
                ->setKey('expiration')
                ->setTitle('memcache.columns.' . $type . '.expiration');
            $columns[] = (new Column())
                ->setKey('compressed')
                ->setTitle('memcache.columns.' . $type . '.compressed');
        }
        return $columns;
    }

    protected function getPermissions()
    {
        return new MemcachePermissions();
    }

    protected function getCredentialsForm()
    {
        return new MemcacheForm();
    }

    protected function getDataManager()
    {
        return new MemcacheDataManager($this->connection, $this->translator);
    }

    public function itemForm($database, $type, $table, $item)
    {
        if ($type === self::TYPE_KEY) {
            return new MemcacheKeyForm($this->connection, $item);
        }
        parent::itemForm($database, $type, $table, $item);
    }
}
