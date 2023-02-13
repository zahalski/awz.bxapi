<?php
namespace Awz\BxApi\Activity\Types;

use Awz\BxApi\Activity\ActivityBase;
use Awz\BxApi\Helper;
use Bitrix\Main\Application;

class Telegramm extends ActivityBase {

    /* код активити (табличка выше) */
    const CODE = 'awz_tg';

    const CL = 'Telegramm';

    const API_URL = 'https://utf8.zahalski.dev/bitrix/services/main/ajax.php?action=awz:bxapi.api.fullactivity.activity&method=';

    /* метод должен вернуть код активити в общий контроллер api */
    public static function getCode(string $type): string
    {
        return parent::getCodeFromCode($type, self::CODE);
    }

    /* метод должен вернуть название в общий контроллер api
    будет доступно в приложении в списке доступных активити
     */
    public static function getName(string $type): string
    {
        if($type === self::TYPE_ROBOT){
            return 'Робот: Телега';
        }
        return 'Активити: Телега';
    }

    /* метод должен вернуть название в общий контроллер api
    будет доступно в приложении в списке доступных активити
    */
    public static function getDescription(string $type): string
    {
        if($type === self::TYPE_ROBOT){
            return 'Робот Телега';
        }
        return 'Активити Телега';
    }

    /* параметры нашего активити согласно доке битрикса
    для ifraime приложения в битрикс24 для добавления активити
    */
    public static function getParams(string $type): array
    {
        $activityParams = [
            'CODE'=>self::getCode($type),
            'HANDLER'=>self::API_URL.self::CL.'&type='.$type.'&app_id=#APP_ID#',
            'USE_SUBSCRIPTION'=>'Y',
            'NAME'=>self::getName($type),
            'DESCRIPTION'=>self::getDescription($type),
            'PROPERTIES'=> [
                'Comment'=> [
                    'Name'=>'Текст сообщения в Телеграм',
                    'Type'=>'string',
                    'Required'=>'Y',
                    'Multiple'=>'N',
                ],
                'TaskId'=> [
                    'Name'=>'Ид задачи',
                    'Type'=>'int',
                    'Required'=>'Y',
                    'Multiple'=>'N',
                ]
            ],
            'RETURN_PROPERTIES'=> [
                'errorText'=>self::getDefParams('errorText')
            ]
        ];
        //\Bitrix\Main\Diag\Debug
        return $activityParams;
    }

    public static function run(string $domain, string $app_id, string $type, $context=null): \Bitrix\Main\Result
    {
        /* @var $context \Awz\BxApi\Api\Scopes\Controller */
        $log = null;
        if($context){
            $log = $context->getLogger();
        }
        if($log){
            $log->debug(
                "[fullactivity.activity.{code}]\n{date}\n{domain}|{app_id}|{type}\n",
                [
                    'domain' => $domain,
                    'app_id' => $app_id,
                    'type' => $type,
                    'code'=> self::CODE
                ]
            );
        }

        $result = new \Bitrix\Main\Result;

        /* проверка прав доступа на действие
        acl строго рекомендуется реализовывать в самой точке доступа
        Awz\bxApi\Api\Controller\FullActivity -> activityLists
        т.к. мы держим в методе секретку с чата, не помешает и тут
        */
        if($domain != 'zahalski.bitrix24.by'){
            $result->addError(new \Bitrix\Main\Error("Активити запрещен для ".$domain));
            return $result;
        }

        /* возвращаемые в БП параметры */
        $returnParams = [];

        $request = Application::getInstance()->getContext()->getRequest();
        $requestData = $request->toArray();
        /* входящие параметры с битрикс24 */
        $params = $requestData['properties'];

        if($log){
            $log->debug(
                "[requestData]\n{date}\n{requestData}\n",
                ['requestData' => $requestData]
            );
        }

        /* отправляем сообщение */
        $tokenAr = array(
            'token'=>'123456789:AAGFfAAGFfAAGFfAAGFfAAGFfAAGFfAAGFfAAGFf',
            'chat_id'=>123456789
        );
        $url = 'https://api.telegram.org/bot'.$tokenAr['token'].'/sendMessage';

        $httpClient = new \Bitrix\Main\Web\HttpClient();
        $httpClient->disableSslVerification();
        $r = $httpClient->post($url, array(
            'chat_id'=>$tokenAr['chat_id'],
            'text'=>$params['Comment']."\n\n".'https://'.$domain.'/company/personal/user/0/tasks/task/view/'.$params['TaskId'].'/',
        ));
        if(!$r){
            $result->addError(new \Bitrix\Main\Error("Чтото пошло не так"));
        }

        if(!$result->isSuccess()){
            $returnParams['errorText'] = implode("; ",$result->getErrorMessages());
        }

        $app = new \Awz\bxApi\App(array(
            'APP_ID'=>$app_id,
            'APP_SECRET_CODE'=>Helper::getSecret($app_id),
        ));

        $retArr = array(
            'event_token'=>$app->getRequest()->get('event_token'),
            'return_values'=>$returnParams
        );
        $app->setAuth($requestData['auth']);
        $resultBp = $app->postMethod('bizproc.event.send', $retArr);

        /* чтото пошло не так, возвращаем в общий контроллер ошибку */
        if(!$resultBp->isSuccess()) {
            foreach ($resultBp->getErrors() as $err) {
                $result->addError($err);
            }
        }else{
            if($log){
                $log->debug(
                    "[resultBp]\n{date}\n{resultBp}\n",
                    ['resultBp' => $resultBp->getData()]
                );
            }
        }

        return $result;
    }

}