<?php
namespace Awz\BxApi\Api\Controller;

use Awz\BxApi\App;
use Awz\BxApi\Helper;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Awz\BxApi\Api\Scopes\Controller;
use Awz\BxApi\Api\Scopes\Scope;
use Awz\BxApi\Api\Filters\AppAuth;
use Awz\BxApi\Api\Filters\Sign;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class IblockEl extends Controller
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function configureActions()
    {
        $config = [
            'list'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    )
                ]
            ],
            'forward'=> [
                'prefilters' => []
            ]
        ];

        return $config;
    }

    public function listAction(array $order = [], array $filter = [], array $select = [], string $domain, string $app){

        if(!$this->checkRequire(['user'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }

        if(!$domain || !$app){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        return [];
    }

    public function forwardAction(string $method){

        $configMethods = $this->configureActions();
        unset($configMethods['forward']);

        if(!in_array($method, array_keys($configMethods))){
            $this->addError(new Error('method not found', 200));
            return array(
                'enabled'=>array_keys($configMethods)
            );
        }

        return $this->run($method, $this->getSourceParametersList());

    }

}