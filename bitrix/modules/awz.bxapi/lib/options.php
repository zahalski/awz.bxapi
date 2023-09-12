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

class OptionsTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_bxapi_options';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_OPTIONS_ENTITY_FIELD_ID')
                )
            ),
            new Entity\StringField('NAME', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_OPTIONS_ENTITY_FIELD_NAME')
                )
            ),
            new Entity\StringField('APP', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_OPTIONS_ENTITY_FIELD_APP')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_OPTIONS_ENTITY_FIELD_PORTAL')
                )
            ),
            new Entity\StringField('PARAMS', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_OPTIONS_ENTITY_FIELD_PARAMS'),
                    'save_data_modification' => function(){
                        return [
                            function ($value) {

                                if(isset($value['fields']) && is_array($value['fields'])){
                                    $activeFields = [];
                                    foreach($value['fields'] as $fieldCode=>$fieldVal){
                                        if(isset($fieldVal['isActive']) && $fieldVal['isActive']==='Y'){
                                            $activeFields[$fieldCode] = $fieldVal;
                                        }
                                    }
                                    $value['fields'] = $activeFields;
                                }

                                return serialize($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function(){
                        return [
                            function ($value) {
                                return unserialize($value, ["allowed_classes" => false]);
                            }
                        ];
                    },
                )
            ),
            new Entity\DatetimeField('DATE_ADD', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_OPTIONS_ENTITY_FIELD_DATE_ADD')
                )
            ),
        );
    }

}