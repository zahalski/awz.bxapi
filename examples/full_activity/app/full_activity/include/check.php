<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
use Bitrix\Main\UI\Extension as UIExt;
use Awz\bxApi\Helper;
use Bitrix\Main\Page\Asset;

/* @var $app Awz\BxApi\App */
$signedParameters = '';
CJsCore::init('jquery');
UIExt::load("ui.buttons");
UIExt::load("ui.buttons.icons");
UIExt::load("ui.forms");
UIExt::load("ui.alerts");
UIExt::load("ui.fonts.opensans");
UIExt::load("ui.hint");
UIExt::load("ui.icons.b24");
Asset::getInstance()->addJs($currentDir."script.js");
$portalData = $app->getCurrentPortalData();
$authResult = null;
?>
<div class="container"><div class="row"><div class="ui-block-wrapper">
<div class="ui-block-title">
    <div class="ui-block-title-text">Настройки синхронизации портала и сервиса списка активити</div>
    <div class="ui-block-title-actions">
        <a href="#" class="ui-block-title-actions-show-hide">Свернуть</a>
    </div>
</div>
<div class="ui-block-content active">
    <?if(!$portalData){?>
        <?if($app->getRequest()->get('DOMAIN')){?>
            <div data-page="list" class="tab-content tab-content-list">
                <div class="ui-alert ui-alert-warning">
<span class="ui-alert-message">
<strong>Внимание!</strong> Авторизуйте приложение на вашем портале,
необходима синхронизация ключей доступа к порталу и сервису.
</span>
                </div>
                <div class="col-xs-12" style="padding:10px 0;">
                    <a target="_blank" href="<?=$app->getAuthUrl()?>" class="ui-btn ui-btn-success ui-btn-icon-success">Авторизация</a>
                </div>
            </div>
        <?}?>
    <?}else{
        $authResult = $app->checkCurrentPortalSignKey();
        if($authResult->isSuccess()){
            ?>
            <div class="ui-alert ui-alert-success">Доступ к сервису активен</div>
            <?
        }else{
            echo Helper::errorsHtml($authResult, 'Ошибка получения опций приложения');
        }
    }?>
</div>
</div></div></div>