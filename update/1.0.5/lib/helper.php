<?php
namespace Awz\BxApi;

use Bitrix\Main\Application;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Security;

class Helper {

    /**
     * constants https://dev.1c-bitrix.ru/rest_help/crm/constants.php
     *
     * @return void
     */
    public static function entityCodes(){
        return [
            ['ID'=>1, 'VALUE'=>'Лид', 'CODE'=>'LEAD', 'MIN_CODE'=>'L'],
            ['ID'=>2, 'VALUE'=>'Сделка', 'CODE'=>'DEAL', 'MIN_CODE'=>'D'],
            ['ID'=>3, 'VALUE'=>'Контакт', 'CODE'=>'CONTACT', 'MIN_CODE'=>'C'],
            ['ID'=>4, 'VALUE'=>'Компания', 'CODE'=>'COMPANY', 'MIN_CODE'=>'CO'],
            ['ID'=>5, 'VALUE'=>'Счет (старый)', 'CODE'=>'INVOICE', 'MIN_CODE'=>'I'],
            ['ID'=>7, 'VALUE'=>'Предложение', 'CODE'=>'QUOTE', 'MIN_CODE'=>'Q'],
            ['ID'=>8, 'VALUE'=>'Реквизит', 'CODE'=>'REQUISITE', 'MIN_CODE'=>'RQ'],
            ['ID'=>31, 'VALUE'=>'Счет (новый)', 'CODE'=>'SMART_INVOICE', 'MIN_CODE'=>'SI'],
        ];
    }

    /**
     * коды для сокращения урла
     *
     * @return void
     */
    public static function getPlacementCode(string $code, bool $short = true): string
    {
        static $shortCodes = [
            'TASK_USER_LIST_TOOLBAR'=>'1'
        ];
        if($short)
            return $shortCodes[$code] ?? $code;
        if(isset($shortCodes[$code])) return $code;
        foreach($shortCodes as $key=>$v){
            if($v === $code) return $key;
        }
        return $code;
    }

    /**
     * html ошибок по тексту и заголовку
     *
     * @param array $errors массив ошибок
     * @param string $title
     * @return string
     */
    public static function errorsHtmlFromText(array $errors, string $title=''){

        $result = new Result();
        foreach($errors as $err)
            $result->addError(new Error($err));

        return self::errorsHtml($result, $title);

    }

    /**
     * html ошибок по объекту результата и заголовку
     *
     * @param Result $result
     * @param string $title
     * @return string
     */
    public static function errorsHtml(Result $result, $title=''){

        if($result->isSuccess()) return '';

        $html = '<div class="center-error-wrap">';
        $html .= '<h2>'.$title.'</h2>';
        $html .= '<div class="tab-content tab-content-list">';
        foreach($result->getErrorMessages() as $message){
            $html .= '<div class="ui-alert ui-alert-danger">'.$message.'</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function getSecret($appId){

        static $secrets = array();
        if(!isset($secrets[$appId])){
            $secret = AppsTable::getList(array(
                'select'=>array('TOKEN'),
                'filter'=>array('=APP_ID'=>$appId, '=ACTIVE'=>'Y'),
                'limit'=>1
            ))->fetch();
            if($secret){
                $secrets[$appId] = $secret['TOKEN'];
            }else{
                $secrets[$appId] = '';
            }
        }

        return isset($secrets[$appId]) ? $secrets[$appId] : '';
    }

    public static function checkRightForDefaultSigned(){

        $result = new Result();
        $request = Application::getInstance()->getContext()->getRequest();
        $signedParams = $request->get('signed');
        try{
            $signer = new Security\Sign\Signer();
            $params = $signer->unsign($request->get('signed'));
            $params = unserialize(base64_decode($params), ['allowed_classes' => false]);
        }catch (\Exception $e){
            $result->addError(new Error('Ошибка проверки подписи'));
            return $result;
        }

        if(!$params['domain'] || !$params['key'] || !$params['s_id'] || !$params['app_id']){
            $result->addError(new Error('Ошибка в параметрах запроса'));
            return $result;
        }

        $checkKey = false;
        $query = array(
            'select'=>array('*'),
            'filter'=>array(
                '=PORTAL'=>$params['domain'],
                '=APP_ID'=>$params['app_id'],
                '=ACTIVE'=>'Y',
            ),
            'limit'=>1
        );
        $portalData = TokensTable::getList($query)->fetch();

        if($portalData && ($portalData['PARAMS']['key'] == $params['key'])){
            $checkKey = true;
        }
        if(!$checkKey){
            $result->addError(new Error('Доступ запрещен'));
            return $result;
        }
        $result->setData($params);

        return $result;
    }

    /**
     * Возвращает текст ошибки или пустую строку
     *
     * @param \Bitrix\Main\ErrorCollection $errors
     * @return string
     */
    public static function getErrorMessages(\Bitrix\Main\ErrorCollection $errors){
        if(empty($errors)) return "";
        $errorTexts = array();
        foreach($errors as $error){
            /* @var $error \Bitrix\Main\Error */
            $errorTexts[] = $error->getMessage();
        }
        return implode("; ", $errorTexts);
    }

}