<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('bnk.propimporter')) {
    return false;
}

$aModuleMenu = [
    "parent_menu" => "global_menu_content",
    "section" => "bnk_propimporter",
    "sort" => 1000,
    "text" => Loc::getMessage("BNK_PROPIMPORTER_MENU_TEXT"),
    "title" => Loc::getMessage("BNK_PROPIMPORTER_MENU_TITLE"),
    'icon' => 'sys_menu_icon',
    "page_icon" => "sys_page_icon",
    "items_id" => "menu_bnk_propimporter",
    "items" => [
        [
            "text" => Loc::getMessage("BNK_PROPIMPORTER_MENU_ITEM"),
            "title" => Loc::getMessage("BNK_PROPIMPORTER_MENU_ITEM"),
            "url" => "bnk_propimporter.php?lang=" . LANGUAGE_ID,
        ]
    ]
];

return $aModuleMenu;