<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
use Bitrix\Main\UI\Extension as UIExt;

CJsCore::init('jquery');
UIExt::load("ui.alerts");
?>
<script>
    BX24.ready(function() {
        BX24.init(function () {
            BX24.callMethod('app.info', {}, function(res){
                BX24.installFinish();
            });
        });
    });
</script>