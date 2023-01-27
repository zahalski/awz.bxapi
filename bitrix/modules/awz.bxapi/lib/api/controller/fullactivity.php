<?php
namespace Awz\bxApi\Api\Controller;

use Awz\bxApi\Api\Scopes\Controller;
use Awz\bxApi\Api\Scopes\Scope;
use Awz\bxApi\Api\Filters\Sign;
use Awz\bxApi\Api\Filters\AppAuth;
use Awz\bxApi\Api\Filters\AppAuthActivity;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FullActivity extends Controller
{

    const TYPE_BP = 'bp';
    const TYPE_ROBOT = 'robot';
    const ACTIVITY_NS = "\\Awz\\BxApi\\Activity\\Types\\";

    public function activityLists(){
        return array(
            /*'AwzVipiskaFix',
            'AwzDocsId',
            'AwzDealFCmpPrice',
            'AwzReestrIds',
            'Telegramm'*/
        );
    }

    public function configureActions()
    {
        $config = [
            'activity'=>[
                'prefilters' => [
                    new AppAuthActivity()
                ]
            ],
            'getActivity'=>[
                'prefilters' => [
                    new Sign(
                        ['domain','key','s_id','app_id'], [],
                        Scope::createFromCode('signed')
                    ),
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user'), ['signed']
                    )
                ]
            ],
            'list'=> [
                'prefilters' => [
                    new Sign(
                        ['domain','key','s_id','app_id'], [],
                        Scope::createFromCode('signed')
                    ),
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user'), ['signed']
                    )
                ]
            ],
            'forward'=> [
                'prefilters' => []
            ]
        ];

        return $config;
    }

    protected function getMinParams(string $className, string $type): array
    {
        $classNameNs = self::ACTIVITY_NS.$className;
        return array(
            'code'=>$classNameNs::getCode($type),
            'name'=>$classNameNs::getName($type),
            'desc'=>$classNameNs::getDescription($type),
            'method'=>$className
        );
    }

    public function forwardAction(string $method){

        $configMethods = $this->configureActions();
        unset($configMethods['forward']);
        $activeMethods = array_keys($configMethods);

        $startMethod = $method;
        $activityList = $this->activityLists();
        if(in_array($method, $activityList)){
            $method = 'activity';
        }

        if(!in_array($method, $activeMethods)){
            $this->addError(new Error('method not found', 200));
            return array(
                'enabled'=>array_merge($activeMethods, $activityList)
            );
        }

        return $this->run($method, $this->getSourceParametersList());
    }

    public function getActivityAction(string $domain, string $app_id, string $key, string $type, string $code){
        if(!$domain || !$app_id || !$key || !$code || !$type){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $className = self::ACTIVITY_NS.$code;
        if(!class_exists($className)){
            $this->addError(
                new Error('Активити '.$code. 'не найдено', 100)
            );
            return null;
        }
        $params = $className::getParams($type);
        $params['HANDLER'] = str_replace('#APP_ID#', $app_id, $params['HANDLER']);
        $params['HANDLER'] .= '&key='.$key;
        return array(
            'activity'=>$params
        );

    }

    public function activityAction(string $domain, string $app_id, string $method, string $type){

        $className = self::ACTIVITY_NS.$method;
        if(!class_exists($className)){
            $this->addError(
                new Error('Активити с кодом '.$method. 'не найдено', 100)
            );
            return null;
        }

        $result = $className::run($domain, $app_id, $type);
        /* @var $result \Bitrix\Main\Result */

        if($result->isSuccess()){
            return $result->getData();
        }else{
            foreach($result->getErrors() as $err){
                $this->addError($err);
            }
            return null;
        }

    }

    public function listAction(string $domain, string $app_id){
        if(!$domain || !$app_id){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $items = array();
        $activityList = $this->activityLists();
        foreach($activityList as $code){
            $items[] = $this->getMinParams($code, self::TYPE_BP);
            $items[] = $this->getMinParams($code, self::TYPE_ROBOT);
        }

        return array(
            'items'=>$items
        );
    }

}