<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

global $APPLICATION;
$dirs = explode('/',dirname(__DIR__ . '../'));
$module_id = array_pop($dirs);
unset($dirs);
Loc::loadMessages(__FILE__);

if(!Loader::includeModule('awz.admin')) return;
if(!Loader::includeModule($module_id)) return;

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

/* "Awz\BxApi\AdminPages\TokensList" replace generator */
use Awz\BxApi\AdminPages\TokensList as PageList;

$APPLICATION->SetTitle(PageList::getTitle());
$arParams = PageList::getParams();

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/awz.admin/include/handler.php");
/* @var bool $customPrint */
if(!$customPrint) {
    $adminCustom = new PageList($arParams);
    $adminCustom->defaultInterface();
}