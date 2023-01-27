<?php

namespace Awz\BxApi\AdminPages;

use Awz\Admin\Helper;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IForm;
use Awz\Admin\IParams;

Loc::loadMessages(__FILE__);

class AppsEdit extends IForm implements IParams {

    public function __construct($params){
        parent::__construct($params);
    }

    public function trigerCheckActionAdd($func){
        return $func;
    }

    public function trigerCheckActionUpdate($func){
        return $func;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_BXAPI_APPS_EDIT_TITLE');
    }

    public static function getParams(): array
    {
        $arParams = array(
            "ENTITY" => "\\Awz\\BxApi\\AppsTable",
            "BUTTON_CONTEXTS"=>array('btn_list'=>false),
            "LIST_URL"=>'/bitrix/admin/awz_bxapi_apps_list.php',
            "TABS"=>array(
                "edit1" => array(
                    "NAME"=>Loc::getMessage('AWZ_BXAPI_APPS_EDIT_EDIT1'),
                    "FIELDS" => array(
                        'NAME',
                        'PORTAL',
                        'APP_ID',
                        'ACTIVE'=>array('TYPE'=>'BOOL','NAME'=>'ACTIVE'),
                        'TOKEN',
                        //'DATE_ADD'=>array('BTIME'=>'Y','NAME'=>'DATE_ADD','TYPE'=>'DATE','REQUIRED'=>'Y'),
                    )
                )
            )
        );
        return $arParams;
    }
}