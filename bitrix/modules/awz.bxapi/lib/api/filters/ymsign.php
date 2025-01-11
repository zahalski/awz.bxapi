<?php

namespace Awz\BxApi\Api\Filters;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class YmSign extends Base {

    const SCOPE_ID = 'signed';

    public $signKeys = [];

    protected $keys = array();

    public function __construct(array $params = array())
    {
        $this->signKeys = [
            'notification_type','operation_id',
            'amount','currency',
            'datetime','sender','codepro',
            'notification_secret','label'
        ];
        parent::__construct();
    }

    public function onBeforeAction(Event $event)
    {
        $this->getAction()->getController()->setScope(self::SCOPE_ID);

        $str = '';
        $request = $this->getAction()->getController()->getRequest();
        $secretKey = Configuration::getInstance('awz.bxapi')->get('yoomoney')['SECRET'];
        foreach($this->signKeys as $docParameter){
            if($docParameter == 'notification_secret'){
                $str .= $secretKey.'&';
            }else{
                $str .= $request->get($docParameter).'&';
            }
        }
        $str = substr($str,0,-1);
        $hash = sha1($str,false);
        if(!$request->get('sha1_hash') || ($request->get('sha1_hash') != $hash)){
            $this->addError(new Error(
                'Ошибка проверки подписи запроса',
                'err_sign'
            ));
            return new EventResult(EventResult::ERROR, null, 'awz.bxapi', $this);
        }

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}