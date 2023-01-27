<?php

namespace Awz\BxApi;

use Awz\BxApi\Helper;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class AppsTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_bxapi_apps';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_bxapi_apps` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `NAME` varchar(65) NOT NULL,
        `PORTAL` varchar(65) NOT NULL,
        `APP_ID` varchar(65) NOT NULL,
        `ACTIVE` varchar(1) NOT NULL,
        `PARAMS` varchar(6255) NOT NULL,
        `TOKEN` varchar(1255) NOT NULL,
        `DATE_ADD` datetime NOT NULL,
        PRIMARY KEY (`ID`),
        unique IX_APP_ID (APP_ID),
        ) AUTO_INCREMENT=1;
        */
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_ID')
                )
            ),
            new Entity\StringField('NAME', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_NAME')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_PORTAL')
                )
            ),
            new Entity\StringField('APP_ID', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_APP_ID')
                )
            ),
            new Entity\StringField('ACTIVE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_ACTIVE')
                )
            ),
            new Entity\StringField('PARAMS', array(
                    'required' => true,
                    'serialized' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_PARAMS')
                )
            ),
            new Entity\StringField('TOKEN', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_TOKEN')
                )
            ),
            new Entity\DatetimeField('DATE_ADD', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_APPS_ENTITY_FIELD_DATE_ADD')
                )
            ),
        );
    }

    public static function onBeforeAdd(Entity\Event $event)
    {
        $fields = $event->getParameter("fields");
        $result = new Entity\EventResult();
        $modify = array();
        //if(!$fields['DATE_ADD']){
            $modify['DATE_ADD'] = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
        //}
        if(!$fields['PARAMS']){
            $modify['PARAMS'] = array();
        }
        if(!$fields['PORTAL']){
            $modify['PORTAL'] = 'ALL';
        }
        if(!empty($modify)){
            $result->modifyFields($modify);
        }
        return $result;
    }
}