<?php

namespace Adminerng\Core\Driver;

use Adminerng\Core\DataManager\DataManagerInterface;
use Adminerng\Core\Forms\DefaultFormManager;
use Adminerng\Core\Forms\FormManagerInterface;
use Adminerng\Core\ListingHeaders\HeaderManagerInterface;
use Adminerng\Core\Permissions\DefaultPermissions;
use Adminerng\Core\Permissions\PermissionsInterface;
use Nette\Localization\ITranslator;

abstract class AbstractDriver implements DriverInterface
{
    protected $translator;

    private $permissions;

    private $formManager;

    private $headerManager;

    private $dataManager;

    public function __construct(ITranslator $translator)
    {
        $this->translator = $translator;
    }

    public function name()
    {
        return $this->type() . '.name';
    }

    /**
     * check if driver can be used
     * @return boolean
     */
    public function check()
    {
        foreach ($this->extensions() as $extension) {
            if (!extension_loaded($extension)) {
                return false;
            }
        }
        foreach ($this->classes() as $class) {
            if (!class_exists($class)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array list of php extensions which should be loaded
     */
    abstract public function extensions();

    /**
     *
     * @return array list of php classes which should exist
     */
    public function classes()
    {
        return [];
    }

    /**
     * @return PermissionsInterface
     */
    final public function permissions()
    {
        if ($this->permissions === null) {
            $this->permissions = $this->getPermissions();
        }
        return $this->permissions;
    }

    /**
     * @return PermissionsInterface
     */
    abstract protected function getPermissions();

    /**
     * @return FormManagerInterface
     */
    final public function formManager()
    {
        if ($this->formManager === null) {
            $this->formManager = $this->getFormManager();
        }
        return $this->formManager;
    }

    /**
     * @return FormManagerInterface
     */
    abstract protected function getFormManager();

    /**
     * @return HeaderManagerInterface
     */
    final public function headerManager()
    {
        if ($this->headerManager === null) {
            $this->headerManager = $this->getHeaderManager();
        }
        return $this->headerManager;
    }

    /**
     * @return HeaderManagerInterface
     */
    abstract protected function getHeaderManager();

    /**
     * @return DataManagerInterface
     */
    public function dataManager()
    {
        if ($this->dataManager === null) {
            $this->dataManager = $this->getDataManager();
        }
        return $this->dataManager;
    }

    /**
     * @return DataManagerInterface
     */
    abstract protected function getDataManager();
}
