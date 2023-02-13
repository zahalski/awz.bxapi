<?php

namespace Awz\bxApi\Api\Filters;

use Awz\BxApi\TokensTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class AppAuthActivity extends Base {

    protected $scopes = null;
    protected $checkError;

    public function __construct(array $params = array(), array $scopes = array(), bool $checkError = false)
    {
        parent::__construct();

        if(!empty($scopes)){
            $this->scopes = $scopes;
        }
        $this->checkError = $checkError;
    }

    public function listAllowedScopes()
    {
        if(!$this->scopes){
            return parent::listAllowedScopes();
        }
        return $this->scopes;
    }

    public function onBeforeAction(Event $event)
    {

        if($this->checkError && !empty($this->getAction()->getController()->getErrors())){
            return null;
        }

        $key = null;
        $domain = null;
        $appId = null;

        $httpRequest = $this->getAction()->getController()->getRequest();
        if ($httpRequest)
        {
            $httpRequest->addFilter(new Request\AppAuthActivity());
        }


        if(!$key){
            $key = $this->getAction()->getController()->getRequest()->get('key');
        }
        if(!$domain){
            $domain = $this->getAction()->getController()->getRequest()->get('domain');
        }
        if(!$appId){
            $appId = $this->getAction()->getController()->getRequest()->get('app');
        }
        if(!$appId){
            $appId = $this->getAction()->getController()->getRequest()->get('app_id');
        }

        $checkKey = null;
        if(!$key || !$domain || !$appId){
            $checkKey = false;
        }

        if($checkKey !== false){
            $query = array(
                'select'=>array('*'),
                'filter'=>array(
                    '=PORTAL'=>$domain,
                    '=APP_ID'=>$appId,
                    '=ACTIVE'=>'Y',
                ),
                'limit'=>1
            );
            $portalData = TokensTable::getList($query)->fetch();

            if($portalData && $portalData['PARAMS']['key'] == $key){
                $checkKey = true;
            }
        }


        if($checkKey !== true){
            $this->addError(new Error(
                'Ошибка проверки авторизации',
                'err_auth'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }


        return null;
    }

}