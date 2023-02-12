<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
use Bitrix\Main\Security;
use Bitrix\Main\Application;

/* @var $app Awz\BxApi\App */
/* @var $authResult Bitrix\Main\Result */
?>
<?if($authResult && $authResult->isSuccess()){

    $signer = new Security\Sign\Signer();

    $signedParameters = $signer->sign(base64_encode(serialize(array(
        'domain'=>htmlspecialchars($app->getRequest()->get('DOMAIN')),
        'key'=>$app->getCurrentPortalOption('auth'),
        's_id'=>htmlspecialchars($app->getRequest()->get('AUTH_ID')),
        'app_id'=>$app->getConfig('APP_ID')
    ))));

    ?>
    <div class="container"><div class="row"><div class="ui-block-wrapper">
        <div class="ui-block-title">
            <div class="ui-block-title-text">Мои активити</div>
            <div class="ui-block-title-actions">
                <a href="#" class="ui-block-title-actions-show-hide">Свернуть</a>
            </div>
        </div>
        <div class="ui-block-content active">
            <form>
                <input type="hidden" id="signed_add" name="signed" value="<?=$signedParameters?>">
            </form>
            <div class="container">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-xs-9"><h4>Название активити</h4></div>
                    <div class="col-xs-3"><h4>Действия</h4></div>
                </div>
            </div>
            <div class="container activity-list">

            </div>
        </div>
    </div></div></div>
    <script>
        $(document).ready(function(){
            window.awz_helper.endpointUrl = 'https://<?=Application::getInstance()->getContext()->getServer()->getHttpHost()?>/bitrix/services/main/ajax.php?action=awz:bxapi.api.fullactivity.';
            window.awz_helper.loadActivityList();
        });
    </script>
<?}?>