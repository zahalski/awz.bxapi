<?php
namespace Awz\BxApi\Api\Controller;

use Awz\BxApi\App;
use Awz\BxApi\Helper;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Awz\BxApi\Api\Scopes\Controller;
use Awz\BxApi\Api\Scopes\Scope;
use Awz\BxApi\Api\Filters\AppAuth;
use Awz\BxApi\Api\Filters\AppUser;
use Awz\BxApi\Api\Filters\IsAdmin;
use Awz\BxApi\Api\Filters\Sign;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Request;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Awz\Admin\Grid\Option as GridOptions;

Loc::loadMessages(__FILE__);

class SmartApp extends Controller
{

    public function __construct(Request $request = null)
    {
        Loader::includeModule('awz.admin');
        parent::__construct($request);
    }

    public static function checkUserRightHook($portal, $app, $id, $userId){
        $result = new Result();
        $check = false;
        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['ID','PARAMS'],
            'filter'=>[
                '=ID'=>$id,
                '=PORTAL'=>$portal
            ]
        ]);
        while($data = $r->fetch()){
            if(!isset($data['PARAMS']['handler']['app'])) continue;
            if($data['PARAMS']['handler']['app'] != $app) continue;
            if(isset($data['PARAMS']['hook']['users']) && is_array($data['PARAMS']['hook']['users']) && !empty($data['PARAMS']['hook']['users'])){
                $check = in_array($userId, $data['PARAMS']['hook']['users']);
            }else{
                $check = true;
            }
            if($check) $result->setData($data['PARAMS']);
            break;
        }
        if(!$check){
            $result->addError(new Error('Доступ к данной встройке ограничен для Вас.'));
        }
        return $result;
    }

    public function configureActions()
    {
        $config = [
            'addhook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    ),
                    new AppUser(
                        [], [],
                        Scope::createFromCode(
                            'bxuser',
                            new \Bitrix\Main\Type\Dictionary(['userId'=>0])
                        ),
                        ['user']
                    ),
                    new IsAdmin(
                        [], [],
                        Scope::createFromCode('bxadmin'),
                        ['user', 'bxuser']
                    )
                ]
            ],
            'listhook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    ),
                    new AppUser(
                        [], [],
                        Scope::createFromCode(
                            'bxuser',
                            new \Bitrix\Main\Type\Dictionary(['userId'=>0])
                        ),
                        ['user']
                    ),
                    new IsAdmin(
                        [], [],
                        Scope::createFromCode('bxadmin'),
                        ['user', 'bxuser']
                    )
                ]
            ],
            'deletehook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    ),
                    new AppUser(
                        [], [],
                        Scope::createFromCode(
                            'bxuser',
                            new \Bitrix\Main\Type\Dictionary(['userId'=>0])
                        ),
                        ['user']
                    ),
                    new IsAdmin(
                        [], [],
                        Scope::createFromCode('bxadmin'),
                        ['user', 'bxuser']
                    )
                ]
            ],
            'gethook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    ),
                    new AppUser(
                        [], [],
                        Scope::createFromCode(
                            'bxuser',
                            new \Bitrix\Main\Type\Dictionary(['userId'=>0])
                        ),
                        ['user']
                    ),
                    new IsAdmin(
                        [], [],
                        Scope::createFromCode('bxadmin'),
                        ['user', 'bxuser']
                    )
                ]
            ],
            'dialoglist'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    ),
                    new AppUser(
                        [], [],
                        Scope::createFromCode(
                            'bxuser',
                            new \Bitrix\Main\Type\Dictionary(['userId'=>0])
                        ),
                        ['user']
                    ),
                    new IsAdmin(
                        [], [],
                        Scope::createFromCode('bxadmin'),
                        ['user', 'bxuser']
                    )
                ]
            ],
            'updatehook'=>[
                'prefilters' => [
                    new AppAuth(
                        [], [],
                        Scope::createFromCode('user')
                    ),
                    new AppUser(
                        [], [],
                        Scope::createFromCode(
                            'bxuser',
                            new \Bitrix\Main\Type\Dictionary(['userId'=>0])
                        ),
                        ['user']
                    ),
                    new IsAdmin(
                        [], [],
                        Scope::createFromCode('bxadmin'),
                        ['user', 'bxuser']
                    )
                ]
            ],
            'updategrid'=>[
                'prefilters' => []
            ],
            'forward'=> [
                'prefilters' => []
            ]
        ];

        return $config;
    }

    public function dialoglistAction(string $domain, string $app, array $filter_ID = [], string $filter_QUERY = ''){
        if(!$this->checkRequire(['user', 'bxuser', 'bxadmin'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }
        if(!$domain || !$app || (empty($filter_ID) && !$filter_QUERY)){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $filter = ['=PORTAL'=>$domain];
        foreach($filter_ID as $id){
            $id = (string) $id;
            if(!$id) continue;
            if(!isset($filter['=ID'])) $filter['=ID'] = [];
            $filter['=ID'][] = $id;
        }
        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['ID','PARAMS'],
            'filter'=>$filter
        ]);
        $items = [];
        while($data = $r->fetch()) {
            if (!isset($data['PARAMS']['handler']['app'])) continue;
            if ($data['PARAMS']['handler']['app'] != $app) continue;
            $isItemShow = false;
            if($filter_QUERY == '*') $isItemShow = true;
            if($filter_QUERY){
                if(
                    isset($data['PARAMS']['handler']['name']) && $data['PARAMS']['handler']['name'] &&
                    strpos(mb_strtolower($data['PARAMS']['handler']['name']), mb_strtolower($filter_QUERY))!==false
                ){
                    $isItemShow = true;
                }
                if(
                    isset($data['PARAMS']['hook']['min_name_user']) && $data['PARAMS']['hook']['min_name_user'] &&
                    strpos(mb_strtolower($data['PARAMS']['hook']['min_name_user']), mb_strtolower($filter_QUERY))!==false
                ){
                    $isItemShow = true;
                }
                if(
                    isset($data['PARAMS']['hook']['desc_user']) && $data['PARAMS']['hook']['desc_user'] &&
                    strpos(mb_strtolower($data['PARAMS']['hook']['desc_user']), mb_strtolower($filter_QUERY))!==false
                ){
                    $isItemShow = true;
                }
                if(
                    isset($data['PARAMS']['hook']['desc_admin']) && $data['PARAMS']['hook']['desc_admin'] &&
                    strpos(mb_strtolower($data['PARAMS']['hook']['desc_admin']), mb_strtolower($filter_QUERY))!==false
                ){
                    $isItemShow = true;
                }
            }else{
                $isItemShow = true;
            }
            if(!$isItemShow) continue;
            $item = [
                'id'=>$data['ID'],
                'title'=>$data['PARAMS']['handler']['name'],
                'image'=>'/bitrix/js/ui/forms/images/crm-deal.svg'
            ];
            if(isset($data['PARAMS']['hook']['min_name_user']) && $data['PARAMS']['hook']['min_name_user']){
                $item['title'] = $data['PARAMS']['hook']['min_name_user'];
            }
            if(isset($data['PARAMS']['hook']['desc_icon']) && $data['PARAMS']['hook']['desc_icon']){
                $item['image'] = $data['PARAMS']['hook']['desc_icon'];
            }
            if(isset($data['PARAMS']['hook']['desc_user']) && $data['PARAMS']['hook']['desc_user']){
                $item['subtitle'] = $data['PARAMS']['hook']['desc_user'];
            }
            //$item['link'] = '#app||?params[HOOK]='.$data['ID'];
            $items[] = $item;
        }
        return [
            'result'=> $items
        ];

    }

    public static function setPageParam($pageOptions){
        $item = [
            'NAME'=>$pageOptions['min_name_user'],
            'IMAGE'=>$pageOptions['desc_icon'],
            'DESC'=>$pageOptions['desc_user'],
            'BG'=>$pageOptions['desc_bg_hex'],
        ];
        return $item;
    }

    public static function getPageParam($hookData){

        $item = [
            'min_name_user'=>$hookData['PARAMS']['hook']['min_name_user'],
            'desc_user'=>$hookData['PARAMS']['hook']['desc_user'],
            'desc_icon'=>$hookData['PARAMS']['hook']['desc_icon'],
            'desc_bg_hex'=>$hookData['PARAMS']['hook']['desc_bg_hex'],
            'sort'=>500,
            'active'=>'Y'
        ];
        if(!$item['min_name_user']){
            $item['min_name_user'] = $hookData['NAME'];
        }
        return $item;
    }

    public function listhookAction(string $domain, string $app, int $publicmode=0, int $parentplacement = 0, string $grid_id='', string $key='', int $check_active=0){
        if(!$this->checkRequire(['user', 'bxuser'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }

        $userId = $this->getScopeCollection()->getByCode('bxuser')->getCustomData()->get('userId');
        $isAdmin = $this->getScopeCollection()->getByCode('bxadmin')->isEnabled();

        if(!$domain || !$app || !$userId){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        if(!$publicmode && !$isAdmin){
            $this->addError(
                new Error('publicmode должно быть равным 1 для получения списка ссылок', 100)
            );
            return null;
        }

        $hooks = [];

        if($publicmode){

            $startFilter = [
                '=PORTAL'=>$domain
            ];
            if($parentplacement){
                $startFilter['!ID'] = $parentplacement;
                $r = \Awz\BxApi\HandlersTable::getList([
                    'select'=>['ID','PARAMS'],
                    'filter'=>[
                        '=PORTAL'=>$domain,
                        '=ID'=>$parentplacement
                    ]
                ]);
                while($data = $r->fetch()){
                    if(!isset($data['PARAMS']['handler']['app'])) continue;
                    if($data['PARAMS']['handler']['app'] != $app) continue;
                    if(isset($data['PARAMS']['hook']['users']) && !empty($data['PARAMS']['hook']['users']) &&
                        is_array($data['PARAMS']['hook']['users']) && !in_array($userId, $data['PARAMS']['hook']['users'])){
                        continue;
                    }
                    if(isset($data['PARAMS']['hook']['placements']) &&
                        is_array($data['PARAMS']['hook']['placements']) &&
                        !empty($data['PARAMS']['hook']['placements'])){
                        $startFilter['=ID'] = $data['PARAMS']['hook']['placements'];
                    }
                }
            }

            $r = \Awz\BxApi\HandlersTable::getList([
                'select'=>['ID','PARAMS'],
                'filter'=>$startFilter
            ]);
            while($data = $r->fetch()){
                if(!isset($data['PARAMS']['handler']['app'])) continue;
                if($data['PARAMS']['handler']['app'] != $app) continue;
                if(isset($data['PARAMS']['hook']['users']) && !empty($data['PARAMS']['hook']['users']) &&
                is_array($data['PARAMS']['hook']['users']) && !in_array($userId, $data['PARAMS']['hook']['users'])){
                    continue;
                }
                $item = [
                    'ID'=>$data['ID'],
                    'NAME'=>$data['PARAMS']['handler']['name'],
                    'IMAGE'=>'/bitrix/js/ui/forms/images/crm-deal.svg'
                ];
                if(isset($data['PARAMS']['hook']['min_name_user']) && $data['PARAMS']['hook']['min_name_user']){
                    $item['NAME'] = $data['PARAMS']['hook']['min_name_user'];
                }
                if(isset($data['PARAMS']['hook']['desc_user']) && $data['PARAMS']['hook']['desc_user']){
                    $item['DESC'] = $data['PARAMS']['hook']['desc_user'];
                }
                if(isset($data['PARAMS']['hook']['desc_icon']) && $data['PARAMS']['hook']['desc_icon']){
                    $item['IMAGE'] = $data['PARAMS']['hook']['desc_icon'];
                }
                if(isset($data['PARAMS']['hook']['mlink']) && $data['PARAMS']['hook']['mlink']){
                    $item['MLINK'] = $data['PARAMS']['hook']['mlink'];
                }
                $item['MENU'] = 'Y';
                if(isset($data['PARAMS']['hook']['main_menu']) && $data['PARAMS']['hook']['main_menu']){
                    $item['MENU'] = $data['PARAMS']['hook']['main_menu']!='N' ? 'Y' : 'N';
                }
                if($parentplacement){
                    $item['MENU'] = 'Y';
                }
                if(isset($data['PARAMS']['hook']['desc_bg_hex']) && $data['PARAMS']['hook']['desc_bg_hex']){
                    $item['BG'] = $data['PARAMS']['hook']['desc_bg_hex'];
                }else{
                    $item['BG'] = 'transparent';
                }
                $item['SORT'] = 500;
                $hooks[$data['ID']] = $item;
            }

            $gridOptions = new GridOptions($grid_id);
            $userOptions = $gridOptions->getCustomOptions()->getParameter('pages', []);
            if(!empty($userOptions)){
                $optionsPrepared = [];
                foreach($hooks as $pageId=>$hookData){
                    $tmp = [
                        'min_name_user'=>$hookData['MAME'],
                        'desc_user'=>$hookData['DESC'],
                        'desc_icon'=>$hookData['IMAGE'],
                        'desc_bg_hex'=>$hookData['BG'],
                        'sort'=>500,
                        'active'=>'Y'
                    ];
                    $optionsPrepared[$pageId] = $tmp;
                }
                $options = \Awz\Admin\Helper::applyGridOptionsToCustomGrid($optionsPrepared, $userOptions);

                foreach($options as $pageId=>$pageOption){
                    if($pageOption['active']=='Y' || !$check_active){
                        $newItem = self::setPageParam($pageOption);
                        $newItem['ID'] = $pageId;
                        if(!$newItem['BG']) $newItem['BG'] = 'transparent';
                        if(!$newItem['IMAGE']) $newItem['IMAGE'] = '/bitrix/js/ui/forms/images/crm-deal.svg';
                        if(!$newItem['NAME']) $newItem['NAME'] = $hooks[$pageId]['NAME'];
                        if($hooks[$pageId]['MLINK']){
                            $newItem['MLINK'] = $hooks[$pageId]['MLINK'];
                        }
                        $newItem['MENU'] = ($hooks[$pageId]['MENU'] != 'N') ? 'Y' : 'N';
                        $newItem['SORT'] = $pageOption['sort'];
                        $hooks[$pageId] = $newItem;
                    }else{
                        unset($hooks[$pageId]);
                    }
                }

            }
            uasort($hooks,
                function ($itm1, $itm2) {
                    return (intval($itm1["SORT"]) > intval($itm2["SORT"])) ? 1 : -1;
                }
            );

        }else{

            $r = \Awz\BxApi\HandlersTable::getList([
                'select'=>['*'],
                'filter'=>[
                    '=PORTAL'=>$domain
                ]
            ]);
            while($data = $r->fetch()){
                if(!isset($data['PARAMS']['handler']['app'])) continue;
                if($data['PARAMS']['handler']['app'] != $app) continue;
                $hooks[$data['ID']] = $data;
            }

        }

        return $hooks;

    }

    public function validateDescName(string $descName=''){
        return $this->lenFix($descName, 120);
    }
    public function validateMinName(string $minName=''){
        return $this->lenFix($minName, 60);
    }
    public function lenFix(string $text, int $len){
        return mb_substr(trim($text),0, $len);
    }
    public function validateIcon(string $url=''){
        $url = $this->lenFix($url, 256);
        if($url){
            if(mb_substr($url,0,8)!=='https://'){
                $this->addError(
                    new Error('Ошибка в параметрах, ссылка на изображение должна быть по https протоколу', 100)
                );
                return '';
            }
        }
        return $url;
    }
    public function validateHex(string $hex=''){
        $hex = mb_substr(trim(str_replace('#','',$hex)),0,6);
        $hex = preg_replace('/([^0-9A-Za-z])/is','', $hex);
        $hex = '#'.$hex;
        if($hex === '#') $hex = '';
        if($hex && mb_strlen($hex)!=7){
            $this->addError(
                new Error('Цвет задан неверно, например #FFF000', 100)
            );
            return '';
        }
        return $hex;
    }

    public function updategridAction(string $grid_id, string $key, array $params){

        if(!Loader::includeModule('awz.admin')){
            $this->addError(
                new Error('Метод запрещен', 105)
            );
            return null;
        }
        if(!$grid_id || !$key || empty($params)){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $authData = \awzAdminHandlers::getAuth();
        if(empty($authData)){
            $this->addError(
                new Error('Неверный ключ', 105)
            );
            return null;
        }

        if(isset($params['pages']) && is_array($params['pages'])){

            $pagesParams = [];
            foreach($params['pages'] as $pageId=>$pageParams){
                $item = [
                    'active'=>$pageParams['active'] === 'Y' ? 'Y' : 'N',
                    'min_name_user'=>$this->validateMinName($pageParams['min_name_user']),
                    'desc_user'=>$this->validateDescName($pageParams['desc_user']),
                    'desc_icon'=>$this->validateIcon($pageParams['desc_icon']),
                    'desc_bg_hex'=>$this->validateHex($pageParams['desc_bg_hex']),
                    'sort'=>intval($pageParams['sort']) > 999 ? 500 : intval($pageParams['sort']),
                ];
                if(!$item['min_name_user']) unset($item['min_name_user']);
                if(!$item['desc_user']) unset($item['desc_user']);
                if(!$item['desc_icon']) unset($item['desc_icon']);
                if(!$item['desc_bg_hex']) unset($item['desc_bg_hex']);

                if($this->getErrors()){
                    $this->addError(
                        new Error('Ошибка в параметрах элемента с ID = '.$pageId, 100)
                    );
                    return null;
                }

                $pagesParams[$pageId] = $item;
            }

            $gridOptions = new GridOptions($grid_id);
            $gridOptions->getCustomOptions()->setParameter('pages', $pagesParams);
            $gridOptions->save();

        }

        return null;
    }

    public function updatehookAction(int $id, string $hash, string $domain, string $app,
                                     array $params, string $type='hook')
    {
        if(!$this->checkRequire(['user', 'bxuser', 'bxadmin'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }
        if($type == 'hook_params') $type = 'hook';
        if($type && !in_array($type,['hook'])){
            $type = '';
        }
        if(!$domain || !$app || !$type){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['ID','PARAMS'],
            'filter'=>[
                '=ID'=>$id,
                '=HASH'=>$hash,
                '=PORTAL'=>$domain
            ]
        ]);
        if($data = $r->fetch()){
            if($data['PARAMS']['handler']['app'] != $app){
                $this->addError(
                    new Error('Обработчик не доступен для данного приложения', 100)
                );
                return null;
            }
            if(!isset($data['PARAMS']['hook'])){
                $data['PARAMS']['hook'] = [];
            }
            if(!isset($data['PARAMS']['hook']['users'])){
                $data['PARAMS']['hook']['users'] = [];
            }
            if($type == 'hook'){
                if(isset($params['users']) && is_array($params['users'])){
                    $data['PARAMS']['hook']['users'] = [];
                    foreach($params['users'] as $userId){
                        $userId = intval($userId);
                        if(!$userId) continue;
                        $data['PARAMS']['hook']['users'][] = $userId;
                    }
                }
                if(isset($data['PARAMS']['handler']['type']) &&
                    $data['PARAMS']['handler']['type']==='REST_APP_WRAP' &&
                    isset($params['placements']) && is_array($params['placements'])
                ){
                    $data['PARAMS']['hook']['placements'] = [];
                    foreach($params['placements'] as $userId){
                        $userId = intval($userId);
                        if(!$userId) continue;
                        $data['PARAMS']['hook']['placements'][] = $userId;
                    }
                }
                if(isset($params['desc_admin'])){
                    $data['PARAMS']['hook']['desc_admin'] = $this->lenFix($params['desc_admin'], 256);
                }
                if(isset($params['min_name_user'])){
                    $data['PARAMS']['hook']['min_name_user'] = $this->validateMinName($params['min_name_user']);
                }
                if(isset($params['desc_user'])){
                    $data['PARAMS']['hook']['desc_user'] = $this->validateDescName($params['desc_user']);
                }
                if(isset($params['desc_icon'])){
                    $data['PARAMS']['hook']['desc_icon'] = $this->validateIcon($params['desc_icon']);
                }
                if(isset($params['desc_bg_hex'])){
                    $data['PARAMS']['hook']['desc_bg_hex'] = $this->validateHex($params['desc_bg_hex']);
                }
                if(isset($params['mlink'])){
                    $data['PARAMS']['hook']['mlink'] = $this->lenFix($params['mlink'],256);
                }
                if(isset($params['main_menu'])){
                    $data['PARAMS']['hook']['main_menu'] = ($params['main_menu']!='N') ? 'Y' : 'N';
                }

                if($this->getErrors()){
                    return null;
                }

                $tst = serialize($data['PARAMS']);
                $tstAr = unserialize($tst, ['allowed_classes' => false]);
                if(!isset($tstAr['hook']['users'])){
                    $this->addError(
                        new Error('Ошибка в параметрах, запрещенные символы', 100)
                    );
                    return null;
                }
            }
            $r = \Awz\BxApi\HandlersTable::update(['ID'=>$data['ID']],['PARAMS'=>$data['PARAMS']]);
            if($r->isSuccess()){
                return [
                    'params'=>[
                        'hook'=>$data['PARAMS']['hook']
                    ]
                ];
            }else{
                $this->addError(
                    new Error('Ошибка записи параметров', 100)
                );
                return null;
            }
        }else{
            $this->addError(
                new Error('Обработчик не доступен для данного портала', 100)
            );
            return null;
        }
    }

    public function gethookAction(int $id, string $hash, string $domain, string $app){
        if(!$this->checkRequire(['user', 'bxuser', 'bxadmin'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }
        if(!$domain || !$app){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['*'],
            'filter'=>[
                '=ID'=>$id,
                '=HASH'=>$hash,
                '=PORTAL'=>$domain
            ]
        ]);
        if($data = $r->fetch()){
            if($data['PARAMS']['handler']['app'] != $app){
                $this->addError(
                    new Error('Обработчик не доступен для данного приложения', 100)
                );
                return null;
            }
            return $data;
        }else{
            $this->addError(
                new Error('Обработчик не доступен для данного портала', 100)
            );
            return null;
        }
    }

    public function deletehookAction(int $id, string $hash, string $domain, string $app){
        if(!$this->checkRequire(['user', 'bxuser', 'bxadmin'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }

        if(!$domain || !$app){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }

        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['ID'],
            'filter'=>[
                '=ID'=>$id,
                '=HASH'=>$hash
            ]
        ]);
        if($data = $r->fetch()){
            \Awz\BxApi\HandlersTable::delete($data);
        }

        return null;
    }

    public function addhookAction(string $url, array $params = [], string $domain, string $app){

        if(!$this->checkRequire(['user', 'bxuser', 'bxadmin'])){
            $this->addError(
                new Error('Авторизация не найдена', 105)
            );
            return null;
        }

        if(!$domain || !$app){
            $this->addError(
                new Error('Ошибка в параметрах запроса', 100)
            );
            return null;
        }
        if(!isset($params['handler']) || !is_array($params['handler'])){
            $params['handler'] = [];
        }
        $params['handler']['app'] = $app;

        $r = \Awz\BxApi\HandlersTable::getList([
            'select'=>['ID'],
            'filter'=>[
                '=PORTAL'=>$domain,
                '=URL'=>$url
            ]
        ]);
        if($r->fetch()){
            $this->addError(
                new Error('Встройка с таким URL уже есть, поменяйте название встройки для уникализации URL.', 100)
            );
            return null;
        }
        $hookUrl = \Awz\BxApi\HandlersTable::createHookUrl($url, $params, $domain);

        return [
            'hook'=>$hookUrl
        ];

    }

    public function forwardAction(string $method){

        $configMethods = $this->configureActions();
        unset($configMethods['forward']);

        if(!in_array($method, array_keys($configMethods))){
            $this->addError(new Error('method not found', 200));
            return array(
                'enabled'=>array_keys($configMethods)
            );
        }

        return $this->run($method, $this->getSourceParametersList());

    }

}