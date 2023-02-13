<?
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("BX_SENDPULL_COUNTER_QUEUE_DISABLE", true);
define('BX_SECURITY_SESSION_VIRTUAL', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: text/html; charset=utf-8');
use Bitrix\Main\Loader;
if(!Loader::includeModule('awz.bxapi')) return;

use Awz\BxApi\App;
use Awz\BxApi\Helper;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

$currentDir  = str_replace($_SERVER["DOCUMENT_ROOT"], '', __DIR__).'/';
$appId = 'local.63a75ec574c6c1.67905804';
if($_REQUEST['app']){
    $appId = preg_replace('/([^0-9a-z.])/','',$_REQUEST['app']);
}
global $APPLICATION;
$app = new App(array(
    'APP_ID'=>$appId,
    'APP_SECRET_CODE'=>Helper::getSecret($appId),
    //'LOG_FILENAME'=>'bx_log_save_txt_n_test.txt',
    //'LOG_DIR'=>__DIR__
));
?>
<?include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.bxapi/include/app/head.php');?>
<?
Asset::getInstance()->addCss("/bitrix/css/main/bootstrap.css");
Asset::getInstance()->addCss("/bitrix/css/main/font-awesome.css");
Asset::getInstance()->addCss($currentDir . "style.css");
?>
<?
$fraimeType = '';
$placement = $app->getRequest()->get('PLACEMENT_OPTIONS');
if($placement){
    $placement = Json::decode($placement);
    if($placement['IFRAME_TYPE']){
        $fraimeType = 'slide_'.strtolower($placement['IFRAME_TYPE']);
    }
}
?>
<body class="<?=$fraimeType?>">
<div class="workarea">
<div class="container"><div class="row"><div class="result-block-messages"></div></div></div>
<div class="appWrap" data-page="main">
<?if($app->getRequest()->get('install') == 'Y'){?>
    <?include_once('include/install.php');?>
<?}elseif($app->getRequest()->get('server_domain') == 'oauth.bitrix.info'){?>
    <?include_once('include/authlink.php');?>
<?}else{?>
    <?include_once('include/check.php');?>
    <?include_once('pages/main.php');?>
<?}?>
</div>
</div>
</body>
<?include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.bxapi/include/app/foot.php');?>