<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
use Bitrix\Main\Security\Random;
use Bitrix\Main\UI\Extension as UIExt;
use Awz\bxApi\Helper;
use Awz\BxApi\TokensTable;

/* @var $app Awz\BxApi\App */
UIExt::load("ui.buttons");
UIExt::load("ui.buttons.icons");
UIExt::load("ui.alerts");
$result = $app->getStartToken();
if($result->isSuccess()){
    $resultData = $result->getData();
    $app->setAuth($resultData['result']);
    $appInfoResult = $app->getAppInfo();
    if($appInfoResult->isSuccess()){
        $appInfo = $appInfoResult->getData();
        if(isset($appInfo['result']['LICENSE'])/* &&
            strpos($appInfo['result']['LICENSE'], 'by_')!==false*/)
        {
            $authData = $app->getAuth();
            $authKey = Random::getString(32);

            $resKeyAdd = $app->postMethod('app.option.set.json',
                array(
                    'options'=>array(
                        'auth'=>$authKey
                    )
                )
            );
            if($resKeyAdd->isSuccess()){
                $portal = str_replace(
                        array('/rest/','https://'),
                        '',
                        $authData['client_endpoint']
                );
                TokensTable::updateToken(
                    $app->getConfig('APP_ID'), $portal,
                    $authData
                );
                TokensTable::updateParams(
                    $app->getConfig('APP_ID'), $portal,
                    array('key'=>$authKey)
                );
                ?>
                <div class="center-error-wrap">
                    <h2>Авторизация принята, токен успешно записан.</h2>
                    <div class="tab-content tab-content-list">
                        <div class="ui-alert ui-alert-success">
                            Перейдите на портал и обновите страницу для настройки приложения.
                        </div>
                    </div>
                </div>
                <?
            }else{
                echo Helper::errorsHtml($resKeyAdd, 'Ошибка записи ключа доступа');
            }
        }else{
            echo Helper::errorsHtmlFromText([
                'Приложение доступно только для Беларуси',
                'Лицензия '.$appInfo['result']['LICENSE']
            ]);
        }
    }else{
        echo Helper::errorsHtml($result, 'Произошла ошибка при проверке токена');
    }
    //echo'<pre>';print_r($appInfoResult);echo'</pre>';
}else{
    echo Helper::errorsHtml($result, 'Произошла ошибка при авторизации');
}