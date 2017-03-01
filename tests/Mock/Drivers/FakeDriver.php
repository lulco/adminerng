<?php

namespace UniMan\Tests\Mock\Drivers;

use UniMan\Core\Driver\DriverInterface;
use UniMan\Core\Permissions\DefaultPermissions;

class FakeDriver implements DriverInterface
{
    public function type()
    {
        return 'fake';
    }

    public function name()
    {
        return 'Fake driver';
    }

    public function check()
    {
        return true;
    }

    public function connect(array $credentials)
    {
        return null;
    }

    public function dataManager()
    {
        return null;
    }

    public function defaultCredentials()
    {
        return [];
    }

    public function formManager()
    {
        return null;
    }

    public function getCredentialsForm()
    {
        return null;
    }

    public function headerManager()
    {
        return null;
    }

    public function permissions()
    {
        return new DefaultPermissions();
    }
}
