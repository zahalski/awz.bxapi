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

class SmartApp extends Controller
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function configureActions()
    {
        $config = [
            'addhook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    )
                ]
            ],
            'listhook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    )
                ]
            ],
            'deletehook'=>[
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

    public function listhookAction(string $domain, string $app){
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

        $hooks = [];
        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['*'],
            'filter'=>[
                '=PORTAL'=>$domain
            ]
        ]);
        while($data = $r->fetch()){
            if(!isset($data['PARAMS']['handler']['app'])) continue;
            if($data['PARAMS']['handler']['app'] != $app) continue;
            $hooks[$data['ID']] = $data;
        }

        return $hooks;

    }

    public function deletehookAction(int $id, string $hash, string $domain, string $app){
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

        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['ID'],
            'filter'=>[
                '=ID'=>$id,
                '=HASH'=>$hash
            ]
        ]);
        if($data = $r->fetch()){
            \Awz\BxApi\HandlersTable::delete($data);
        }

        return null;
    }

    public function addhookAction(string $url, array $params = [], string $domain, string $app){

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
        if(!isset($params['handler']) || !is_array($params['handler'])){
            $params['handler'] = [];
        }
        $params['handler']['app'] = $app;

        $hookUrl = \Awz\BxApi\HandlersTable::createHookUrl($url, $params, $domain);

        return [
            'hook'=>$hookUrl
        ];

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