<?
$moduleId = "awz.bxapi";
if(IsModuleInstalled($moduleId)) {
$connection = \Bitrix\Main\Application::getConnection();
$sql = 'CREATE TABLE IF NOT EXISTS `b_awz_bxapi_handlers` (`ID` int(18) NOT NULL AUTO_INCREMENT, `URL` varchar(1255) NOT NULL, `HASH` varchar(64) NOT NULL, `PARAMS` varchar(6255) NOT NULL, `PORTAL` varchar(65) NOT NULL, `DATE_ADD` datetime NOT NULL, PRIMARY KEY (`ID`), unique IX_HASH (HASH));';
$connection->queryExecute($sql);
}