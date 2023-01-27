<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

global $APPLICATION;
$POST_RIGHT = $APPLICATION->GetGroupRight("awz.bxapi");
if ($POST_RIGHT == "D") return;

if(Loader::includeModule('awz.bxapi')){
    $aMenu[] = array(
        "parent_menu" => "global_menu_services",
        "section" => "awz_bxapi",
        "sort" => 100,
        "module_id" => "awz.bxapi",
        "text" => "Битрикс24 прилаги",
        "title" => "Битрикс24 прилаги",
        "items_id" => "awz_bxapi",
        "items" => array(
            array(
                "text" => "Токены приложений",
                "url" => "awz_bxapi_tokens_list.php?lang=".LANGUAGE_ID,
                "more_url" => Array("awz_bxapi_tokens_edit.php?lang=".LANGUAGE_ID),
                "title" => "Токены приложений",
                "sort" => 100,
            ),
            array(
                "text" => "Список приложений",
                "url" => "awz_bxapi_apps_list.php?lang=".LANGUAGE_ID,
                "more_url" => Array("awz_bxapi_apps_edit.php?lang=".LANGUAGE_ID),
                "title" => "Список приложений",
                "sort" => 110,
            )
        ),
    );
    return $aMenu;
}