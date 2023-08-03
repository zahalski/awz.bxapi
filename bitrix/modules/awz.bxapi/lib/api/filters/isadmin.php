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

class IsAdmin extends BaseFilter {

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
        $admin = 0;
        if($this->getAction()->getController()->getRequest()->get('signed')){
            try {
                $signer = new Security\Sign\Signer();
                $params = $signer->unsign($this->getAction()->getController()->getRequest()->get('signed'));
                $params = unserialize(base64_decode($params), ['allowed_classes' => false]);

                $admin = intval($params['admin']) ? intval($params['admin']) : 0;

            }catch (\Exception $e){

            }
        }

        if($admin)
            $this->enableScope();

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}