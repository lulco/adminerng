<?php

namespace UniMan\Drivers\Redis;

use UniMan\Core\Forms\DefaultFormManager;
use UniMan\Drivers\Redis\Forms\RedisCreateHashForm;
use UniMan\Drivers\Redis\Forms\RedisCreateSetForm;
use UniMan\Drivers\Redis\Forms\RedisEditSetForm;
use UniMan\Drivers\Redis\Forms\RedisHashKeyItemForm;
use UniMan\Drivers\Redis\Forms\RedisKeyItemForm;
use UniMan\Drivers\Redis\Forms\RedisRenameHashForm;
use UniMan\Drivers\Redis\Forms\RedisSetMemberForm;
use RedisProxy\RedisProxy;

class RedisFormManager extends DefaultFormManager
{
    private $connection;

    public function __construct(RedisProxy $connection)
    {
        $this->connection = $connection;
    }

    public function itemForm($database, $type, $table, $item)
    {
        if ($type == RedisDriver::TYPE_HASH) {
            return new RedisHashKeyItemForm($this->connection, $table, $item);
        } elseif ($type == RedisDriver::TYPE_KEY) {
            return new RedisKeyItemForm($this->connection, $item);
        } elseif ($type == RedisDriver::TYPE_SET) {
            return new RedisSetMemberForm($this->connection, $table, $item);
        }
    }

    public function tableForm($database, $type, $table)
    {
        if ($type == RedisDriver::TYPE_HASH) {
            if ($table) {
                return new RedisRenameHashForm($this->connection, $table);
            }
            return new RedisCreateHashForm($this->connection);
        } elseif ($type == RedisDriver::TYPE_KEY) {
            return new RedisKeyItemForm($this->connection, $table);
        } elseif ($type == RedisDriver::TYPE_SET) {
            if (!$table) {
                return new RedisCreateSetForm($this->connection);
            }
            return new RedisEditSetForm($this->connection, $table);
        }
    }
}
