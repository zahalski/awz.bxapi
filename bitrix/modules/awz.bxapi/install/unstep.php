<form action="<?= $APPLICATION->GetCurPage()?>">
	<?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?=LANG?>">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="id" value="awz.bxapi">
    <input type="hidden" name="step" value="2">
    <?CAdminMessage::ShowMessage(GetMessage('MOD_UNINST_WARN'))?>
    <p><?= GetMessage('MOD_UNINST_SAVE')?></p>
    <p>
    	<input type="checkbox" name="save" id="save" value="Y" checked>
    	<label for="save"><?= GetMessage('MOD_UNINST_SAVE_TABLES')?></label>
    </p>
    <input type="submit" value="<?= GetMessage('MOD_UNINST_DEL')?>">
</form>

