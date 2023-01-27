<?php
namespace Awz\BxApi\Activity;

abstract class ActivityBase {

    const TYPE_ROBOT = 'robot';
    const CODE = '';

    protected static function getDefParams(string $name): array
    {
        $defParams = [
            'errorText'=> [
                'Name'=> [
                    'ru'=>'Текст ошибки'
                ],
                'Type'=>'string',
                'Default'=>'',
                'Multiple'=>'N',
            ]
        ];
        return $defParams[$name];
    }

    protected static function getCodeFromCode(string $type, string $code): string
    {
        if($type == self::TYPE_ROBOT)
            return $code.'_r';
        return $code;
    }

    abstract public static function getParams(string $type): array;

    abstract public static function getCode(string $type): string;
    abstract public static function getName(string $type): string;
    abstract public static function getDescription(string $type): string;

    abstract public static function run(string $domain, string $app_id, string $type): \Bitrix\Main\Result;

}
