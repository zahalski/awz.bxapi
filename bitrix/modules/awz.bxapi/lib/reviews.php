<?php

namespace Awz\BxApi;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Access\Exception\AccessException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\IO\File;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Data\Cache;

Loc::loadMessages(__FILE__);

class ReviewsTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_bxapi_reviews';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_ID')
                )
            ),
            new Entity\StringField('HASH', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_HASH')
                )
            ),
            new Entity\IntegerField('MARK', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_MARK')
                )
            ),
            new Entity\StringField('ACTIVE', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_ACTIVE')
                )
            ),
            new Entity\StringField('APP', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_APP')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_PORTAL')
                )
            ),
            new Entity\StringField('MESSAGE', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_MESSAGE')
                )
            ),
            new Entity\StringField('ANSWER', array(
                    'required' => false,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_ANSWER')
                )
            ),
            new Entity\DatetimeField('DATE_ADD', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_DATE_ADD')
                )
            ),
            new Entity\DatetimeField('DATE_ANSWER', array(
                    'required' => false,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_DATE_ANSWER')
                )
            ),
            new Entity\DatetimeField('DATE_UPDATE', array(
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_BXAPI_REVIEWS_ENTITY_FIELD_DATE_UPDATE')
                )
            ),
        );
    }

    public static function isReviewClearCache(string $portal, string $app)
    {
        $cache = Application::getInstance()->getManagedCache();
        $cacheId = md5(serialize([$portal, $app]));
        $cache->clean($cacheId);
    }

    public static function isReview(string $portal, string $app): array
    {
        $cache = Application::getInstance()->getManagedCache();
        $cacheTtl = 36000000;
        $cacheId = md5(serialize([$portal, $app]));
        if ($cache->read($cacheTtl, $cacheId)) {
            $vars = $cache->get($cacheId);
        }else{
            $r = self::getList([
                'select'=>['ID','MARK','ACTIVE']
            ])->fetch();
            if($r){
                $vars = $r;
            }else{
                $vars = ['ID'=>0];
            }
            $cache->set($cacheId, $vars);
        }
        return $vars;
    }

    public static function agentGetReviews($pythonPath)
    {
        $tg_func = ['\Awz\BxApi\Helper', 'sendTelegramError'];
        try{
            $moduleId = 'awz.bxapi';
            $path = Application::getDocumentRoot().'/bitrix/modules/'.$moduleId.'/include/reviews/';
            $login = Option::get($moduleId, 'bx_market_login', '', '');
            $password = Option::get($moduleId, 'bx_market_psw', '', '');

            $command = escapeshellcmd($pythonPath.' '.$path.'main.py'.' '.$login.' '.$password);
            $output = shell_exec($command);
            if(!$output){
                throw new InvalidOperationException('shell_exec error '.$command);
            }elseif(mb_strpos($output, 'auth not found')!==false){
                throw new AccessException('auth not found');
            }
            $fileJson = new File($path.'res.json');
            if($fileJson->isExists()){
                $jsonData = Json::decode($fileJson->getContents());
                $fileJson->delete();
                foreach($jsonData as $item){
                    if(isset($item[2], $item[3]) && mb_strpos($item[2],'.')!==false && mb_strpos($item[3],'.')!==false){
                        $hash = md5(serialize($item));
                        foreach($item as &$v){
                            $v = trim($v);
                        }
                        unset($v);
                        $res = self::getList([
                            'select'=>['ID','HASH'],
                            'filter'=>[
                                //'=HASH'=>$hash,
                                '=PORTAL'=>$item[3],
                                '=APP'=>$item[2]
                            ],
                            'limit'=>1
                        ])->fetch();
                        $fields = [
                            'ACTIVE'=>$item[1],
                            'HASH'=>$hash,
                            'PORTAL'=>$item[3],
                            'APP'=>$item[2],
                            'MESSAGE'=>$item[4],
                            'ANSWER'=>$item[6],
                            'DATE_ADD'=>DateTime::createFromTimestamp(strtotime($item[5])),
                            'DATE_ANSWER'=>$item[7] ? DateTime::createFromTimestamp(strtotime($item[7])) : '',
                            'DATE_UPDATE'=>DateTime::createFromTimestamp(time()),
                            'MARK'=>$item[8]
                        ];
                        if($res){
                            if($res['HASH'] != $hash){
                                $r = self::update(['ID'=>$res['ID']], $fields);
                                if($r->isSuccess()) {
                                    self::isReviewClearCache($fields['PORTAL'], $fields['APP']);
                                    if (method_exists($tg_func[0], $tg_func[1])) {
                                        call_user_func_array($tg_func, [$item[2] . ' [' . $item[3] . '] -> update: ' . $item[4]]);
                                    }
                                }else{
                                    throw new ArgumentException($r->getErrorMessages());
                                }
                            }
                        }else{
                            $r = self::add($fields);
                            if($r->isSuccess()){
                                self::isReviewClearCache($fields['PORTAL'], $fields['APP']);
                                if(method_exists($tg_func[0], $tg_func[1])){
                                    call_user_func_array($tg_func, [$item[2].' ['.$item[3].'] -> add: '.$item[4]]);
                                }
                            }else{
                                throw new ArgumentException($r->getErrorMessages());
                            }
                        }
                    }else{
                        throw new ArgumentException("unknown review format");
                    }
                }
            }else{
                throw new AccessException('res.json not found');
            }
        }catch(\Exception $e){
            if(method_exists($tg_func[0], $tg_func[1])){
                call_user_func_array($tg_func, [$e->getMessage()]);
            }
        }

        return '\Awz\BxApi\ReviewsTable::agentGetReviews("'.$pythonPath.'");';

    }
}