<?php

namespace Awz\BxApi\AdminPages;

use Bitrix\Main\Localization\Loc;
use Awz\Admin\IList;
use Awz\Admin\IParams;

Loc::loadMessages(__FILE__);

class AppsList extends IList implements IParams {

    public function __construct($params){
        parent::__construct($params);
    }

    public function trigerGetRowListAdmin($row){
    }

    public function trigerInitFilter(){
    }

    public function trigerGetRowListActions(array $actions): array
    {
        return $actions;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_BXAPI_APPS_LIST_TITLE');
    }

    public static function getParams(): array
    {
        $arParams = array(
            "ENTITY" => "\\Awz\\BxApi\\AppsTable",
            "FILE_EDIT" => "awz_bxapi_apps_edit.php",
            "BUTTON_CONTEXTS"=>array('btn_new'=>array(
                'TEXT'=>'Добавить',
                'ICON'	=> 'btn_new',
                'LINK'	=> 'awz_bxapi_apps_edit.php?lang='.LANG
            )),
            "ADD_GROUP_ACTIONS"=>array("edit","delete"),
            "ADD_LIST_ACTIONS"=>array("delete","edit"),
            "FIND"=>array()
        );
        return $arParams;
    }
}