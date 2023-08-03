<?php

namespace Awz\BxApi\Api\Filters;

use Awz\BxApi\Api\Scopes\BaseFilter;
use Awz\BxApi\Api\Scopes\Scope;
use Awz\BxApi\TokensTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class AppUser extends BaseFilter {

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
        $user_id = 0;
        if($this->getAction()->getController()->getRequest()->get('signed')){
            try {
                $signer = new Security\Sign\Signer();
                $params = $signer->unsign($this->getAction()->getController()->getRequest()->get('signed'));
                $params = unserialize(base64_decode($params), ['allowed_classes' => false]);

                $user_id = intval($params['user_id']) ? intval($params['user_id']) : 0;

            }catch (\Exception $e){

            }
        }

        if(!$user_id){
            $this->addError(new Error(
                'В подпись не передан идентификатор пользователя',
                'err_auth'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }

        foreach($this->scopesCollection as $scope){
            $params = $scope->getCustomData();
            if($params instanceof \Bitrix\Main\Type\Dictionary){
                $params->set('userId', $user_id);
            }
        }

        //$this->

        $this->enableScope();

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}