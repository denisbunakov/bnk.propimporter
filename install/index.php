<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class bnk_propimporter extends CModule
{
    public $MODULE_ID = "bnk.propimporter";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        include __DIR__ . "/version.php";

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("BNK_PROPIMPORTER_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("BNK_PROPIMPORTER_MODULE_DESC");
        $this->PARTNER_NAME = "DenisBunakov.RU";
        $this->PARTNER_URI = "https://denisbunakov.ru";
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . "/../admin",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin",
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(
            __DIR__ . "/../admin",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin"
        );

        return true;
    }
}