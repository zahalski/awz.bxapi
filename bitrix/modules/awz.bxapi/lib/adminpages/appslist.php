<?php

namespace Awz\BxApi\AdminPages;

use Awz\Admin\Helper;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IList;
use Awz\Admin\IParams;

Loc::loadMessages(__FILE__);

class AppsList extends IList implements IParams {

    public function __construct($params){
        parent::__construct($params);
    }

    public function trigerGetRowListAdmin($row){
        Helper::viewListField($row, 'ID', ['type'=>'entity_link'], $this);
        Helper::viewListField($row, 'NAME', ['type'=>'entity_link'], $this);
        Helper::editListField($row, 'NAME', ['type'=>'string'], $this);
        Helper::editListField($row, 'APP_ID', ['type'=>'string'], $this);
        Helper::editListField($row, 'PORTAL', ['type'=>'string'], $this);
        Helper::editListField($row, 'TOKEN', ['type'=>'string'], $this);
        Helper::editListField($row, 'ACTIVE', ['type'=>'checkbox'], $this);
        Helper::editListField($row, 'DATE_ADD', ['type'=>'datetime'], $this);
        $row->AddViewField('PARAMS', '<pre>'.print_r($row->arRes['PARAMS'], true).'</pre>');
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
                'TEXT'=>Loc::getMessage('AWZ_BXAPI_APPS_LIST_ADD_BTN'),
                'ICON'	=> 'btn_new',
                'LINK'	=> 'awz_bxapi_apps_edit.php?lang='.LANG
            )),
            "ADD_GROUP_ACTIONS"=>array("edit","delete"),
            "ADD_LIST_ACTIONS"=>array("delete","edit"),
            "FIND"=>[],
            "FIND_FROM_ENTITY"=>['ID'=>[],'NAME'=>[],'PORTAL'=>[],'APP_ID'=>[],'TOKEN'=>[],'DATE_ADD'=>[]]
        );
        return $arParams;
    }
}