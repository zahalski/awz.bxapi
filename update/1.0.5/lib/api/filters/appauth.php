<?php

namespace Awz\bxApi\Api\Filters;

use Awz\bxApi\Api\Scopes\BaseFilter;
use Awz\bxApi\Api\Scopes\Scope;
use Awz\BxApi\TokensTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class AppAuth extends BaseFilter {

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
        $key = null;
        $domain = null;
        $appId = null;
        if($this->getAction()->getController()->getRequest()->get('signed')){
            try {
                $signer = new Security\Sign\Signer();
                $params = $signer->unsign($this->getAction()->getController()->getRequest()->get('signed'));
                $params = unserialize(base64_decode($params), ['allowed_classes' => false]);

                $domain = $params['domain'];
                $key = $params['key'];
                $appId = $params['app_id'];

            }catch (\Exception $e){

            }
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

        $checkKey = null;
        if(!$key || !$domain || !$appId){
            $checkKey = false;
        }

        if($checkKey !== false){
            $checkKey = TokensTable::checkServiceKey($appId, $domain, $key);
        }

        if($checkKey !== true){
            $this->addError(new Error(
                'Ошибка проверки авторизации',
                'err_auth'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }

        $this->enableScope();

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}