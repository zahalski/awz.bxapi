<?php

namespace Awz\BxApi\AdminPages;

use Awz\Admin\Helper;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IList;
use Awz\Admin\IParams;

Loc::loadMessages(__FILE__);

class TokensList extends IList implements IParams {

    public function __construct($params){
        parent::__construct($params);
    }

    public function trigerGetRowListAdmin($row){
        $row->AddCheckField("ACTIVE");
        Helper::editListField($row, 'PORTAL', ['type'=>'string'], $this);
        Helper::editListField($row, 'APP_ID', ['type'=>'string'], $this);
        Helper::editListField($row, 'EXPIRED_TOKEN', ['type'=>'datetime'], $this);
        Helper::editListField($row, 'EXPIRED_REFRESH', ['type'=>'datetime'], $this);
        $row->AddViewField('PARAMS', '<pre>'.print_r($row->arRes['PARAMS'], true).'</pre>');
        $row->AddViewField('TOKEN', '<pre>'.print_r($row->arRes['TOKEN'], true).'</pre>');

    }

    public function trigerInitFilter(){
    }

    public function trigerGetRowListActions(array $actions): array
    {
        return $actions;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_BXAPI_TOKENS_LIST_TITLE');
    }

    public static function getParams(): array
    {
        $arParams = array(
            "ENTITY" => "\\Awz\\BxApi\\TokensTable",
            "BUTTON_CONTEXTS"=>array(),
            "ADD_GROUP_ACTIONS"=>array("edit","delete"),
            "ADD_LIST_ACTIONS"=>array("delete"),
            "FIND"=>array(),
            "FIND_FROM_ENTITY"=>['ID'=>[],'PORTAL'=>[],'APP_ID'=>[],'EXPIRED_TOKEN'=>[],'EXPIRED_REFRESH'=>[]]
        );
        return $arParams;
    }
}