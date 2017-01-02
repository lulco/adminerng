<?php

namespace Adminerng\Tests\Drivers\Redis;

use Adminerng\Drivers\Redis\RedisPermissions;
use PHPUnit_Framework_TestCase;

class RedisPermissionsTest extends PHPUnit_Framework_TestCase
{
    public function testPermissions()
    {
        $permissions = new RedisPermissions();

        self::assertFalse($permissions->canCreateDatabase());
        self::assertFalse($permissions->canEditDatabase('database_name'));
        self::assertFalse($permissions->canDeleteDatabase('database_name'));

        self::assertTrue($permissions->canCreateTable('database_name', 'type_name'));
        self::assertTrue($permissions->canEditTable('database_name', 'type_name', 'table_name'));
        self::assertTrue($permissions->canDeleteTable('database_name', 'type_name', 'table_name'));

        self::assertFalse($permissions->canCreateTable('database_name', 'key'));
        self::assertFalse($permissions->canEditTable('database_name', 'key', 'table_name'));
        self::assertFalse($permissions->canDeleteTable('database_name', 'key', 'table_name'));

        self::assertTrue($permissions->canCreateItem('database_name', 'type_name', 'table_name'));
        self::assertTrue($permissions->canEditItem('database_name', 'type_name', 'table_name', 'item_id'));
        self::assertTrue($permissions->canDeleteItem('database_name', 'type_name', 'table_name', 'item_id'));

        self::assertTrue($permissions->canExecuteCommands());
    }
}
