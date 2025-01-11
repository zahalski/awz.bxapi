<?php

namespace Awz\bxApi\Api\Filters;

use Awz\bxApi\Api\Scopes\BaseFilter;
use Awz\bxApi\Api\Scopes\Scope;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class ModuleRight extends BaseFilter {

    /**
     * AppAuth constructor.
     * @param array $params
     * @param string[] $scopesBx
     * @param Scope[] $scopes
     * @param string[] $scopesRequired
     */
    public function __construct(
        array $params = array(), array $scopesBx = array(),
        array $scopes = array(), array $scopesRequired = array()
    ){
        parent::__construct($params, $scopesBx, $scopes, $scopesRequired);
    }

    public function onBeforeAction(Event $event)
    {
        if(!$this->checkRequire()){
            return null;
        }
        $this->disableScope();

        $module_id = $this->getParams()->get('module_id');
        if(!$module_id){
            $this->addError(new Error(
                'Не указан параметр module_id',
                'err_auth'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }
        global $APPLICATION;
        $MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
        if (! ($MODULE_RIGHT >= "R")){
            $this->addError(new Error(
                'Доступ запрещен',
                'err_auth'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }

        $this->enableScope();

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}