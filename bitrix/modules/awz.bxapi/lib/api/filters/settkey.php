<?php

namespace Awz\BxApi\Api\Filters;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use GetOpt\ArgumentException;

Loc::loadMessages(__FILE__);

class SettKey extends Base {

    const SCOPE_ID = 'signed';

    public $settKey = '';

    protected $keys = array();

    public function __construct(array $params = array())
    {
        if(isset($params['settKey']))
            $this->settKey = $params['settKey'];
        parent::__construct();
    }

    public function onBeforeAction(Event $event)
    {
        $this->getAction()->getController()->setScope(self::SCOPE_ID);

        $request = $this->getAction()->getController()->getRequest();
        $sett = Configuration::getInstance('awz.bxapi')->get($this->settKey);
        if(!$sett || !is_array($sett) || !isset($sett['KEY']) || !$sett['KEY']){
            $this->addError(new Error(
                'Нет ключа в .settings',
                'err_sign'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }
        $secretKey = $sett['KEY'];
        $requestKey = $request->get('key');
        if(!$requestKey){
            $requestKey = $request->get('auth');
            if(is_array($requestKey)) $requestKey = $requestKey['application_token'];
        }
        if($requestKey!=$secretKey){
            $this->addError(new Error(
                'Ошибка проверки ключа',
                'err_sign'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}