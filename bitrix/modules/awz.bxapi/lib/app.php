<?php
namespace Awz\BxApi;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;
use Awz\BxApi\Log as OldLog;
use Awz\BxApi\Api\Filters\Request\SetFilter;

use Psr\Log;
use Bitrix\Main\Diag;
use Psr\Log\LoggerInterface;

class App implements Log\LoggerAwareInterface {

    use Log\LoggerAwareTrait;

    const JSON_REQUEST = 'json';

    /**
     * Папка для кеша в /bitrix/cache/
     */
    const CACHE_DIR = '/awz/bxapi/';
    const CACHE_TYPE_RESPONSE = 'cache';

    /**
     * @var array|mixed
     */
    protected $config;

    protected $domain;

    protected $request;

    /**
     * @deprecated
     */
    private $log;

    private $auth;
    private $lastResponse;
    private $lastResponseType;

    private array $cacheParams = array();

    public function __construct(array $config)
    {

        $this->config = $config;
        $this->request = Application::getInstance()->getContext()->getRequest();

        $logger = $this->getLogger();

        /* @deprecated */
        if(!$logger && isset($config['LOG_FILENAME'], $config['LOG_DIR'])){
            $this->setLog(array(
                'FILE_NAME' => $config['LOG_FILENAME'],
                'FILE_DIR' => $config['LOG_DIR']
            ));
        }

    }

    /**
     * очистка параметров для кеша
     * должна вызываться после любого запроса через кеш
     */
    public function clearCacheParams()
    {
        $this->cacheParams = array();
    }

    /**
     * параметры для кеша результата запроса
     *
     * @param $cacheId ид кеша
     * @param $ttl время действия в секундах
     */
    public function setCacheParams($cacheId, $ttl=36000000)
    {
        $this->cacheParams = array(
            'id'=>$cacheId,
            'ttl'=>$ttl
        );
    }

    public function getCacheDir(): string
    {
        return self::CACHE_DIR.$this->getConfig('APP_ID').'/';
    }

    public function cleanCache($cacheId='')
    {
        $obCache = Cache::createInstance();
        if(!$cacheId && $this->cacheParams && isset($this->cacheParams['id'])){
            $cacheId = $this->cacheParams['id'];
        }
        if($cacheId)
            $obCache->clean($cacheId, $this->getCacheDir());
    }

    public function getStartToken(): Result
    {

        $result = new Result();

        try{
            $state = $this->getRequest()->get('state');
            if($state){
                $param = Json::decode(base64_decode($state));
                if(($param['portal'] && $param['app'] && $param['sign']) &&
                    $this->createStateSign($param) == $param['sign'])
                {
                    if($param['key']){
                        //$this->getRequest()->set('app_key', $param['key']);
                        $this->getRequest()->addFilter(new SetFilter('app_key', $param['key']));
                    }
                    $this->auth = $this->request->toArray();
                    $url = 'https://oauth.bitrix.info/oauth/token/';
                    $prepareData = array(
                        'grant_type'=>'authorization_code',
                        'client_id'=>$this->getConfig('APP_ID'),
                        'client_secret'=>$this->getConfig('APP_SECRET_CODE'),
                        'code'=>$this->getRequest()->get('code'),
                    );
                    $result = $this->sendRequest($url, $prepareData, HttpClient::HTTP_GET);
                    if($result->isSuccess()){
                        $resultData = $result->getData();
                        if(!isset($resultData['result']['access_token'])){
                            $rCheck = new Result();
                            $rCheck->addError(
                                new Error('invalid token format')
                            );
                            $rCheck->setData($result->getData());
                            return $rCheck;
                        }
                    }

                    return $result;
                }
            }

            $result->addError(new Error('Ошибка проверки запроса на авторизацию'));

        }catch (\Exception $e){

            $result->addError(new Error('Ошибка проверки запроса на авторизацию', $e->getCode()));

        }

        return $result;

    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getConfig($param=false, $def=false)
    {
        if(!$param) return $this->config;
        return isset($this->config[$param]) ? $this->config[$param] : $def;
    }

    public function getRequest()
    {
        return $this->request;
    }
    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function createStateSign($data)
    {
        if(isset($data['sign'])){
            $newData = [];
            foreach($data as $k=>$v){
                if($k !== 'sign'){
                    $newData[$k] = $v;
                }
            }
            $data = $newData;
        }
        return md5(implode($data).'.'.$this->getConfig('APP_SECRET_CODE'));
    }

    public function createState(string $key="")
    {
        $domain = $this->getRequest()->get('DOMAIN');
        $appId = $this->getConfig('APP_ID');
        $secret = $this->getConfig('APP_SECRET_CODE');
        $stateParams = array();
        if($domain && $appId && $secret){
            $stateParams = array(
                'portal'=>$domain,
                'app'=>$appId,
            );
            if($key){
                $stateParams['key'] = $key;
            }
            $stateParams['sign'] = $this->createStateSign($stateParams);
            return base64_encode(json_encode($stateParams));
        }
    }

    public function getAuthUrl(string $key=""): string
    {

        $auth_link = 'https://'.$this->getRequest()->get('DOMAIN').'/oauth/authorize/' .
            '?client_id=' . urlencode($this->getConfig('APP_ID')) .
            '&state='.$this->createState($key);

        return $auth_link;

    }

    public function getToken(): string
    {
        $authData = $this->auth;
        if(isset($authData['access_token']))
            return $authData['access_token'];
        return '';
    }

    public function setAuth($authData)
    {
        $this->auth = $authData;

        $result = new Result();

        if(isset($authData['expires']) && $authData['expires']<time()){

            //обновить токен

            $url = 'https://oauth.bitrix.info/oauth/token/';
            $params = array(
                'grant_type'=>'refresh_token',
                'client_id'=>$this->getConfig('APP_ID'),
                'client_secret'=>$this->getConfig('APP_SECRET_CODE'),
                'refresh_token'=>$authData['refresh_token']
            );

            $resNewToken = $this->sendRequest($url, $params, \Bitrix\Main\Web\HttpClient::HTTP_GET);

            if($resNewToken->isSuccess()){

                $dataResp = $resNewToken->getData();
                $this->auth = $dataResp['result'];

                $portal = str_replace(array('/rest/','https://'),'',$dataResp['result']['client_endpoint']);

                TokensTable::updateToken($this->getConfig('APP_ID'), $portal, $dataResp['result']);

            }else{

                $result->addError(new Error('Ошибка продления токена'));
                foreach($resNewToken->getErrors() as $error){
                    $result->addError($error);
                }

            }

        }

        return $result;

    }

    public function getEndpoint(): string
    {
        $authData = $this->auth;
        if(isset($authData['client_endpoint']))
            return $authData['client_endpoint'];
        return '';
    }

    public function getMethod($method, array $params=array())
    {
        $result = $this->sendRequest($method, $params, HttpClient::HTTP_GET);
        return $result;
    }

    public function postMethod($method, $params=array())
    {
        $result = $this->sendRequest($method, $params);
        return $result;
    }

    public function getAppInfo()
    {
        $result = $this->sendRequest('app.info.json',array(), HttpClient::HTTP_GET);
        if($result->isSuccess()){
            $data = $result->getData();
            if(isset($data['result'])){
                $result->setData($data['result']);
            }
        }
        return $result;
    }

    private function sendRequest($url, $data = array(), $type=HttpClient::HTTP_POST)
    {

        $result = new Result();

        $startTime = microtime(true);

        if(strpos($url, 'https://')===false){
            $endpoint = $this->getEndpoint();
            if(!$endpoint || strpos($endpoint, 'https://')===false){
                $result->addError(new Error('Неверный адрес портала'));
                return $result;
            }
            $url = $endpoint.$url;
            $authData = $this->getAuth();
            if($authData['access_token']){
                $url .= '?auth='.$this->getToken();
            }

        }
        //print_r($data);

        if(!empty($this->cacheParams)){
            $obCache = Cache::createInstance();
            if( $obCache->initCache($this->cacheParams['ttl'],$this->cacheParams['id'],$this->getCacheDir()) ){
                $res = $obCache->getVars();
            }
            $this->clearCacheParams();
        }

        $tracker = null;
        $trackerPortal = null;

        if(!$res){
            $httpClient = new HttpClient();
            $httpClient->disableSslVerification();
            if($type == HttpClient::HTTP_GET){
                if(!empty($data)) {
                    $url .= strpos($url, '?')!==false ? '&' : '?';
                    $url .= http_build_query($data);
                }
                $res = $httpClient->get($url);
            }elseif($type == self::JSON_REQUEST){
                $res = $httpClient->post($url, Json::encode($data));
            }else{
                $res = $httpClient->post($url, $data);
            }
            $this->setLastResponse($httpClient);

            if(\Bitrix\Main\Loader::includeModule('awz.bxapistats')){
                $tracker = \Awz\BxApiStats\Tracker::getInstance();
                if($tracker->getAppId() && $tracker->getPortal()){
                    $trackerPortal = \Awz\BxApiStats\Tracker::getInstance($tracker->getPortal(), $tracker->getAppId());
                }
            }

            if($tracker){
                $tracker->addCount();
            }
            if($trackerPortal){
                $trackerPortal->addCount();
            }
        }else{
            $this->setLastResponse(null, self::CACHE_TYPE_RESPONSE);
        }


        if(!$res){
            $result->addError(
                new Error('empty request')
            );
        }else{
            try {

                $json = Json::decode($res);
                $result->setData(array('result'=>$json));

                if(isset($json['error']) && $json['error']){
                    $errText = $json['error'];
                    if(isset($json['error_description']) && $json['error_description']){
                        $errText = $json['error'].': '.$json['error_description'];
                    }
                    $result->addError(
                        new Error($errText)
                    );
                }elseif(isset($json['error_description']) && $json['error_description']){
                    $result->addError(
                        new Error($json['error_description'])
                    );
                }

                if(isset($json['time']['duration'])){
                    if($tracker){
                        $tracker->addBxTime($json['time']['duration']);
                    }
                    if($trackerPortal){
                        $trackerPortal->addBxTime($json['time']['duration']);
                    }
                }

            }catch (\Exception  $ex){
                $result->addError(
                    new Error($ex->getMessage(), $ex->getCode())
                );
            }
        }

        if($result->isSuccess() && $this->lastResponse){
            if($obCache){
                if($obCache->startDataCache()){
                    $obCache->endDataCache($res);
                }
            }
        }

        return $result;

    }

    public function setAuthFromRequest()
    {
        $tkn = array();
        $tkn['access_token'] = htmlspecialchars($this->getRequest()->get('AUTH_ID'));
        $tkn['client_endpoint'] = 'https://' .htmlspecialchars($this->getRequest()->get('DOMAIN')). '/rest/';
        $this->setAuth($tkn);
        return null;
    }

    public function checkCurrentPortalSignKey(string $domain="")
    {

        $result = new Result();

        $portalData = $this->getCurrentPortalData($domain);

        if(!isset($portalData['PARAMS']['key']) || !$portalData['PARAMS']['key']){
            $result->addError(new Error('Ключ для доступа к сервису не генерировался', 501));
        }else{
            $optionsDataResult = $this->getCurrentPortalOptions();
            if($optionsDataResult->isSuccess()){
                $optionsData = $optionsDataResult->getData();
                if(!isset($optionsData['auth']) || !$optionsData['auth']){
                    $result->addError(new Error('Ключ для доступа к сервису не найден в настройках приложения', 502));
                    return $result;
                }

                if($portalData['PARAMS']['key'] != $optionsData['auth']){
                    $result->addError(new Error('Неверный токен доступа', 503));
                    return $result;
                }

                return $result;

            }
            return $optionsDataResult;
        }

        return $result;

    }

    public function getCurrentPortalOption($optionName, $defValue = null)
    {

        $optionsRes = $this->getCurrentPortalOptions();
        if($optionsRes->isSuccess()){
            $options = $optionsRes->getData();
            if(isset($options[$optionName])) return $options[$optionName];
        }
        return $defValue;

    }

    public function getCurrentPortalOptions()
    {

        $result = new Result();
        $this->setAuthFromRequest();

        static $portalOptionsData = null;

        if(is_array($portalOptionsData)){
            $result->setData($portalOptionsData);
            return $result;
        }

        $resOptions = $this->getMethod('app.option.get', array('option'=>array()));

        if($resOptions->isSuccess()){

            $optionsDataPrepare = $resOptions->getData();

            $portalOptionsData = array();

            if(isset($optionsDataPrepare['result']['result'])){
                $portalOptionsData = $optionsDataPrepare['result']['result'];
            }

            $result->setData($portalOptionsData);

            return $result;

        }else{

            return $resOptions;

        }

    }

    public function getCurrentPortalData(string $domain="", string $active = 'Y'): ?array
    {
        static $portalData = [];

        if(!$domain) $domain = $this->getRequest()->get('DOMAIN');
        if(!$domain) return null;

        if($active === 'Y'){
            if(isset($portalData[$domain]) && is_array($portalData[$domain]))
                return $portalData[$domain];
        }

        $query = array(
            'select'=>array('*'),
            'filter'=>array(
                '=PORTAL'=>$domain,
                '=APP_ID'=>$this->getConfig('APP_ID'),
                '=ACTIVE'=>$active,
            ),
            'limit'=>1
        );
        $curData = TokensTable::getList($query)->fetch();
        if($active === 'Y' && $curData){
            $portalData[$domain] = $curData;
        }
        if($curData) return $curData;

        return null;

    }

    /**
     * Получение последнего запроса
     *
     * @return null|HttpClient
     */
    public function getLastResponse(): ?HttpClient
    {
        return $this->lastResponse;
    }

    public function getLastResponseType(){
        return $this->lastResponseType;
    }

    public function getLogger(): ?LoggerInterface
    {
        if ($this->logger === null)
        {
            $logger = Diag\Logger::create('awz.bxapi.App', [$this]);

            if ($logger !== null)
            {
                $this->setLogger($logger);
            }
        }

        return $this->logger;
    }

    /**
     * Запись последнего запроса
     *
     * @param null $resp
     * @param string $type
     * @return HttpClient|null
     */
    private function setLastResponse($resp = null, $type=''): ?HttpClient
    {
        if($resp && !($resp instanceof HttpClient)){
            $resp = null;
        }
        $this->lastResponse = $resp;
        $this->lastResponseType = $type;
        return $this->lastResponse;
    }

    /**
     * @deprecated
     * use $this->getLogger()
     * @return mixed
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @deprecated
     * use $this->getLogger()
     * @return LoggerInterface|null
     */
    public function getPsrLog()
    {
        if($this->log instanceof \Psr\Log\LoggerInterface)
            return $this->log;
        return null;
    }

    /**
     * @deprecated
     * use .settings.php
     *   'loggers' => [
     *       'value' => [
     *           'awz.bxapi.App' => [
     *               'className' => '\\Bitrix\\Main\\Diag\\FileLogger',
     *               'constructorParams' => ['/var/www/log.txt'],
     *               'level' => \Psr\Log\LogLevel::DEBUG
     *           ]
     *       ],
     *       'readonly' => true
     *   ]
     * @return LoggerInterface|null
     */
    public function setPsrLog(\Psr\Log\LoggerInterface $logger)
    {
        $this->log = $logger;
    }

    /**
     * @deprecated
     * use .settings.php
     * @return LoggerInterface|null
     */
    public function setLog($params)
    {
        $this->log = new OldLog($params);
    }

    /**
     * @deprecated
     * use $this->getLogger()->debug()
     * @return LoggerInterface|null
     */
    public function log($data, $title='')
    {

        $log = $this->getLog();

        if($log instanceof OldLog){
            $log->add($data, $title);
        }elseif($log instanceof Log\LoggerInterface){
            $log->debug('---'.$title."---\n".print_r($data, true));
        }

    }

}