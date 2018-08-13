<?php

namespace UniMan\Tests\Core\Driver;

use PHPUnit\Framework\TestCase;
use UniMan\Core\Driver\DriverInterface;
use UniMan\Core\Driver\DriverStorage;
use UniMan\Tests\Mock\Drivers\FakeDriver;

class DriverStorageTest extends TestCase
{
    public function testDriverStorage()
    {
        $driverStorage = new DriverStorage();
        self::assertTrue(is_array($driverStorage->getDrivers()));
        self::assertEmpty($driverStorage->getDrivers());
        self::assertNull($driverStorage->getDriver('someDriver'));

        $fakeDriver = new FakeDriver();
        self::assertInstanceOf(DriverStorage::class, $driverStorage->add($fakeDriver));
        self::assertTrue(is_array($driverStorage->getDrivers()));
        self::assertCount(1, $driverStorage->getDrivers());
        self::assertInstanceOf(DriverInterface::class, $driverStorage->getDriver($fakeDriver->type()));
    }
}
