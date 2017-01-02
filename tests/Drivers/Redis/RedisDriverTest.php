<?php

namespace Adminerng\Tests\Drivers\Redis;

use Adminerng\Core\CredentialsFormInterface;
use Adminerng\Core\ListingHeaders\HeaderManagerInterface;
use Adminerng\Core\Permissions\PermissionsInterface;
use Adminerng\Drivers\Redis\RedisDriver;
use Adminerng\Tests\Drivers\AbstractDriverTest;

class RedisDriverTest extends AbstractDriverTest
{
    public function testDriver()
    {
        $driver = new RedisDriver($this->translator, $this->formatter);
        self::assertTrue(is_string($driver->type()));
        self::assertTrue(is_string($driver->name()));
        self::assertTrue(is_array($driver->classes()));
        self::assertTrue(is_array($driver->extensions()));
        self::assertTrue(is_bool($driver->check()));
        self::assertTrue(is_array($driver->defaultCredentials()));
        self::assertInstanceOf(CredentialsFormInterface::class, $driver->getCredentialsForm());
        self::assertInstanceOf(HeaderManagerInterface::class, $driver->headerManager());
        self::assertInstanceOf(PermissionsInterface::class, $driver->permissions());
    }
}
