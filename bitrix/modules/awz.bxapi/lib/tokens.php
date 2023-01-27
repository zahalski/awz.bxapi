<?php

namespace Awz\BxApi;

use Awz\BxApi\Helper;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class TokensTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_bxapi_tokens';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_bxapi_tokens` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `PORTAL` varchar(65) NOT NULL,
        `APP_ID` varchar(65) NOT NULL,
        `ACTIVE` varchar(1) NOT NULL,
        `PARAMS` varchar(6255) NOT NULL,
        `TOKEN` varchar(1255) NOT NULL,
        `EXPIRED_TOKEN` datetime NOT NULL,
        `EXPIRED_REFRESH` datetime NOT NULL,
        PRIMARY KEY (`ID`)
        ) AUTO_INCREMENT=1;
        */
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_ID')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_PORTAL')
                )
            ),
            new Entity\StringField('APP_ID', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_APP_ID')
                )
            ),
            new Entity\StringField('ACTIVE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_ACTIVE')
                )
            ),
            new Entity\StringField('PARAMS', array(
                    'required' => true,
                    'serialized' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_PARAMS')
                )
            ),
            new Entity\StringField('TOKEN', array(
                    'required' => true,
                    'serialized' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_TOKEN')
                )
            ),
            new Entity\DatetimeField('EXPIRED_TOKEN', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_EXPIRED_TOKEN')
                )
            ),
            new Entity\DatetimeField('EXPIRED_REFRESH', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_TOKENS_FIELDS_EXPIRED_REFRESH')
                )
            )
        );
    }

    public static function checkServiceKey(string $appId, string $domain, string $key): bool
    {
        if(!$appId || !$domain || !$key)
            return false;
        $query = array(
            'select'=>array('*'),
            'filter'=>array(
                '=PORTAL'=>$domain,
                '=APP_ID'=>$appId,
                '=ACTIVE'=>'Y',
            ),
            'limit'=>1
        );
        $portalData = self::getList($query)->fetch();

        if($portalData && ($portalData['PARAMS']['key'] == $key)){
            return true;
        }
        return false;
    }

    public static function updateParams(string $appId, string $domain, array $params=[], bool $repeat=true)
    {
        if(!$domain) {
            throw new \Bitrix\Main\ArgumentException('bitrix PORTAL (domain) is required');
        }
        if(!$appId) {
            throw new \Bitrix\Main\ArgumentException('bitrix PORTAL (domain) is required');
        }

        $r = self::getList(array(
            'select'=>array('ID','PARAMS'),
            'filter'=>array('=PORTAL'=>$domain, '=APP_ID'=>$appId),
            'limit'=>1
        ))->fetch();

        if(!$r) return false;

        if(!$repeat){
            $r['PARAMS'] = $params;
        }else{
            if(!empty($params)){
                foreach($params as $k=>$v){
                    $r['PARAMS'][$k] = $v;
                }
            }
        }
        return self::update(array('ID'=>$r['ID']),array('PARAMS'=>$r['PARAMS']));
    }

    public static function updateToken($appId, $domain, $token){

        if(!$domain) {
            throw new \Bitrix\Main\ArgumentException('bitrix PORTAL (domain) is required');
        }
        if(!$appId) {
            throw new \Bitrix\Main\ArgumentException('bitrix PORTAL (domain) is required');
        }

        $r = self::getList(array(
            'select'=>array('ID'),
            'filter'=>array('=PORTAL'=>$domain, '=APP_ID'=>$appId),
            'limit'=>1
        ))->fetch();

        if(!$token['expires_refresh']){
            $token['expires_refresh'] = strtotime('+28 days');
        }
        $fields = array(
            'PORTAL'=>$domain,
            'APP_ID'=>$appId,
            'ACTIVE'=>'Y',
            'TOKEN'=>$token,
            'EXPIRED_TOKEN'=>DateTime::createFromTimestamp($token['expires']),
            'EXPIRED_REFRESH'=>DateTime::createFromTimestamp($token['expires_refresh']),
        );

        if($r){
            return self::update(array('ID'=>$r['ID']),$fields);
        }else{
            $fields['PARAMS'] = array();
            return self::add($fields);
        }
    }

}