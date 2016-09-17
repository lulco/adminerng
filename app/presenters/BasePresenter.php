<?php

namespace Adminerng\Presenters;

use Adminerng\Core\Exception\ConnectException;
use Nette\Application\BadRequestException;

abstract class BasePresenter extends AbstractBasePresenter
{
    protected function startup()
    {
        parent::startup();
        $drivers = $this->driverStorage->getDrivers();
        $actualDriver = isset($this->params['driver']) ? $this->params['driver'] : current(array_keys($drivers));

        $this->driver = $this->driverStorage->getDriver($actualDriver);
        if (!$this->driver) {
            throw new BadRequestException('Driver "' . $actualDriver . '" not found');
        }

        $credentials = $this->credentialsStorage->getCredentials($actualDriver);
        if (!$credentials) {
            $this->redirect('Homepage:default', $actualDriver);
        }

        $credentials = array_merge($this->driver->defaultCredentials(), $credentials);
        try {
            $this->driver->connect($credentials);
        } catch (ConnectException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redirect('Homepage:default', $actualDriver);
        }
        $this->template->actualDriver = $this->driver;
    }
}
