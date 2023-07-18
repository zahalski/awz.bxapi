<?php

namespace Awz\BxApi\Api\Filters;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class PublicScope extends Base {

    const SCOPE_PUBLIC = 'public';

    protected $keys = array();

    public function __construct(array $params = array())
    {
        parent::__construct();
    }

    public function onBeforeAction(Event $event)
    {
        $this->getAction()->getController()->setScope(self::SCOPE_PUBLIC);

        return new EventResult(EventResult::SUCCESS, null, 'awz.bxapi', $this);
    }

}