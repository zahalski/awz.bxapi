<?php

namespace Awz\BxApi\Api\Filters;

use Awz\BxApi\Api\Scopes\BaseFilter;
use Awz\BxApi\Api\Scopes\Scope;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class Sign extends BaseFilter {

    const ERROR_INVALID_PARAMS = 'invalid_sign';

    /**
     * Sign constructor.
     * @param string[] $keys
     * @param string[] $scopesBx
     * @param Scope[] $scopes
     * @param string[] $scopesRequired
     */
    public function __construct(
        array $keys = array(), array $scopesBx = array(),
        array $scopes = array(), array $scopesRequired = array()
    )
    {
        $params = ['keys'=>$keys];
        parent::__construct($params, $scopesBx, $scopes, $scopesRequired);
    }

    public function onBeforeAction(Event $event): ?EventResult
    {

        if(!$this->checkRequire()){
            return null;
        }
        $this->disableScope();
        try {
            $signer = new Security\Sign\Signer();
            $params = $signer->unsign($this->getAction()->getController()->getRequest()->get('signed'));
            $params = unserialize(base64_decode($params), ['allowed_classes' => false]);
        }catch (\Exception $e){
            $this->addError(new Error(
                'Ошибка проверки подписи',
                self::ERROR_INVALID_PARAMS
            ));

            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }


        if (empty($params))
        {
            $this->addError(new Error(
                'Ошибка проверки подписи',
                self::ERROR_INVALID_PARAMS
            ));

            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }


        $httpRequest = $this->getAction()->getController()->getRequest();
        if ($httpRequest)
        {
            $httpRequest->addFilter(new Request\Sign($this->getKeys(), $params));
        }

        $this->enableScope();

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);

    }

    public function getKeys(){
        return $this->getParams()->get('keys');
    }

}