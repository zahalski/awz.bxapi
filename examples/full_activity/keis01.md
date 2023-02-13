<!-- main-start -->
## Задача
> Реализовать API для клиентов, позволяющее быстро поднять любой 
> катомный активити для БП Битрикс24 (облако)

- локальные приложения для каждого портала могут быть свои
- возможность реализации общих активити для всех порталов
- права доступа

### Требования

> наличие Битрикс Старт и выше (UTF-8) 


<!-- main-end -->

<!-- keis-start -->
### Выдумаем кейс для условного портала

> после постановки задачи в Битрикс24 отправим ее название и ссылку в телеграмм
> 
> отправляем только задачи с заголовком срочно

## Реализация выдуманного кейса
<!-- keis-end -->

<!-- install-start -->
### 1. Устанавливаем модуль awz.admin
Модуль хелпер для организации списков в админ панели 

[ссылка на модуль](https://github.com/zahalski/awz.admin)

### 2. Устанавливаем модуль awz.bxapi
Модуль хелпер для взаимодействия с Битрикс24

[ссылка на модуль](https://github.com/zahalski/awz.bxapi)

### 3. Добавляем локальное приложение в Битрикс24

3.1. ``Маркет`` -> ``Разработчикам`` -> ``Другое`` -> ``Локальное приложение``

3.2. Обзываем, сохраняем, запоминаем ид приложения и секретку

![](https://zahalski.dev/images/modules/keis01/001.png)

### 4. Добавляем приложение на нашем БУС сайте

4.1. ``Сервисы`` - ``Битрикс24 прилаги`` - ``Список приложений`` - ``Добавить``

4.2. Обзываем, вписываем данные прилоежния с Битрикс24, вписываем портал

Для локальных приложений обязательно вписать домен (портал)

Для опубликованных приложений в Битрикс24 маркете задаем ALL (все порталы)

![](https://zahalski.dev/images/modules/keis01/002.png)

4.3. Загружаем само приложение для Битрикс24, например в папку /bx24/full_activity/

[ссылка на приложение](https://github.com/zahalski/awz.bxapi/tree/main/examples/full_activity/app)

4.4 Можно прокинуть свои роуты для апи (или оставить стандартную точку доступа через модуль main), например

```php
// path /local/routes/api.php
use Awz\BxApi\Api\Controller;
$routes->any('/fullactivity/{method}',
    [Controller\FullActivity::class, 'forward']
)->where('method', '[a-zA-Z]+');
```

```php
// path /bitrix/settings.php
[
    'routing' => [
        'value' => [
            'config' => ['api.php']
        ]
    ]
]
```

В случае своих routes - вписываем в pages/main.php точку api

```php
window.awz_helper.endpointUrl = 
    'https://<?=Application::getInstance()->getContext()->getServer()->getHttpHost()?>/fullactivity/';
```

### 5. Приложение есть, вписываем урлы в интерфейсе битрикс24

| параметр             | значение                                                                     |
|----------------------|------------------------------------------------------------------------------|
| ссылка на приложение | https://domain/bx24/full_activity/index.php?app=<ид_прилаги_в_б24>           |
| ссылка на установку  | https://domain/bx24/full_activity/index.php?app=<ид_прилаги_в_б24>&install=Y |

5.1. Жмем установку или переходим в приложение

5.2. Связываем наш апи и приложение в Битрикс24 (будет сгенерирован **токен доступа** к нашему апи и записан в настройки приложения в битрикс24)

_Данным токеном будут подписываться все запросы на наш апи с Битрикс24, также он автоматически будет добавляться в эндпоинты наших активити_

_На стороне апи также сохраняется токен доступа в Битрикс24, необходимости в генерации многочисленных вебхуков в последствии нет. 
Доступ в Битрикс24 будет осуществляться с данного приложения._
<!-- install-end -->

<!-- robot-start -->
## Каркас готов. Пишем наш активити (робот)

### 1. Подготавливаем параметры

| параметр | значение |
|--|----------|
| Код активити | awz_tg |
| Класс | Telegramm |
| Точка доступа api | https://api.zahalski.dev/bitrix/services/main/ajax.php?action=awz:bxapi.api.fullactivity.activity&method= |

### 2. создаем файл с именем нашего класса ``telegramm.php`` в папке /bitrix/modules/awz.bxapi/lib/activity/types/

```php
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

        return $activityParams;
    }
    
    public static function run(string $domain, string $app_id, string $type, $context=null): \Bitrix\Main\Result
    {
        $result = new \Bitrix\Main\Result;
        return $result;
    }
    
}
```

### 3. Реализуем логику нашего активити в методе ``run``

```php
namespace Awz\BxApi\Activity\Types;

use Awz\BxApi\Activity\ActivityBase;
use Awz\BxApi\Helper;
use Bitrix\Main\Application;

class Telegramm extends ActivityBase {

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
```

### 4. Конфигурируем доступ в главном контроллере

/bitrix/modules/awz.bxapi/lib/api/controller/fullactivity.php

```php
class FullActivity extends Controller
{
    public function activityLists(string $domain = ''){
        $codes = [
            'zahalski.bitrix24.by' => [
                'Telegramm'=>[self::TYPE_BP, self::TYPE_ROBOT]
            ],
            'all'=>[]
        ];

        if($domain === '' || !isset($codes[$domain])){
            return $codes['all'];
        }else{
            return array_merge($codes['all'], $codes[$domain]);
        }
    }
}
```

### 5. Устанавливаем робота в нашем приложении в интерфейсе Битрикс24

![](https://zahalski.dev/images/modules/keis01/004.png)

### 6. Добавляем робота для задач (пункт Выдумаем кейс для условного портала)

![](https://zahalski.dev/images/modules/keis01/003.png)
<!-- robot-end -->