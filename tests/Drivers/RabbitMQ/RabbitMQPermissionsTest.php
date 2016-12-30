<?php

namespace Adminerng\Tests\Drivers\RabbitMQ;

use Adminerng\Drivers\RabbitMQ\RabbitMQPermissions;
use PHPUnit_Framework_TestCase;

class RabbitMQPermissionsTest extends PHPUnit_Framework_TestCase
{
    public function testPermissions()
    {
        $permissions = new RabbitMQPermissions();

        self::assertFalse($permissions->canCreateDatabase());
        self::assertFalse($permissions->canEditDatabase('database_name'));
        self::assertFalse($permissions->canDeleteDatabase('database_name'));

        self::assertTrue($permissions->canCreateTable('database_name', 'type_name'));
        self::assertFalse($permissions->canEditTable('database_name', 'type_name', 'table_name'));
        self::assertTrue($permissions->canDeleteTable('database_name', 'type_name', 'table_name'));

        self::assertTrue($permissions->canCreateItem('database_name', 'type_name', 'table_name'));
        self::assertFalse($permissions->canEditItem('database_name', 'type_name', 'table_name', 'item_id'));
        self::assertFalse($permissions->canDeleteItem('database_name', 'type_name', 'table_name', 'item_id'));

        self::assertFalse($permissions->canExecuteCommands());
    }
}
