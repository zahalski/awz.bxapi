<?php

namespace Awz\BxApi\AdminPages;

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
            "FIND"=>array()
        );
        return $arParams;
    }
}