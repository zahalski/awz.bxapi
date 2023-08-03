<?
$moduleId = "awz.bxapi";
if(IsModuleInstalled($moduleId)) {
try{
$connection = \Bitrix\Main\Application::getConnection();
$sql = 'ALTER TABLE `b_awz_bxapi_handlers` MODIFY `PARAMS` longtext';
$connection->queryExecute($sql);
}catch (\Exception $e){
}
}