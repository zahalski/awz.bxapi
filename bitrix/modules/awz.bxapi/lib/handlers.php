<?php

namespace Awz\BxApi;

use Awz\BxApi\Helper;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Security\Random;

Loc::loadMessages(__FILE__);

class HandlersTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_bxapi_handlers';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_bxapi_handlers` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `URL` varchar(1255) NOT NULL,
        `HASH` varchar(64) NOT NULL,
        `PARAMS` varchar(6255) NOT NULL,
        `PORTAL` varchar(65) NOT NULL,
        `DATE_ADD` datetime NOT NULL,
        PRIMARY KEY (`ID`),
        unique IX_HASH (HASH),
        ) AUTO_INCREMENT=1;
        */
        //params longtext
        //ALTER TABLE `b_awz_bxapi_handlers` MODIFY `PARAMS` longtext
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_HANDLERS_ENTITY_FIELD_ID')
                )
            ),
            new Entity\StringField('URL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_HANDLERS_ENTITY_FIELD_URL')
                )
            ),
            new Entity\StringField('HASH', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_HANDLERS_ENTITY_FIELD_HASH')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_HANDLERS_ENTITY_FIELD_PORTAL')
                )
            ),
            new Entity\StringField('PARAMS', array(
                    'required' => true,
                    'serialized' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_HANDLERS_ENTITY_FIELD_PARAMS')
                )
            ),
            new Entity\DatetimeField('DATE_ADD', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_HANDLERS_ENTITY_FIELD_DATE_ADD')
                )
            ),
        );
    }

    public static function createHookUrl(string $url, array $params, string $portal){

        $token = Random::getStringByAlphabet(
            64,
            Random::ALPHABET_NUM|Random::ALPHABET_ALPHALOWER|Random::ALPHABET_ALPHAUPPER,
            true
        );

        $res = self::add([
            'URL'=>$url,
            'HASH'=>$token,
            'PORTAL'=>$portal,
            'PARAMS'=>$params,
            'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time())
        ]);

        return [
            'ID'=>$res->getId(),
            'TOKEN'=>$token
        ];

    }

}