<?php
namespace Awz\BxApi\Api\Filters\Request;

use Bitrix\Main\Type;
use Awz\BxApi\HandlersTable;

class ParseHook implements Type\IRequestFilter
{
    public function __construct()
    {

    }

    public function filter(array $values)
    {
        //if(isset($values['get']['HASH']))
        if(isset($values['get']['ID']) && isset($values['get']['TOKEN'])){
            $r = HandlersTable::getRowById($values['get']['ID']);
            if($r && ($r['HASH'] === $values['get']['TOKEN'])){
                $values['get']['h_ID'] = $values['get']['ID'];
                unset($values['get']['ID']);
                unset($values['get']['TOKEN']);
                $arExt = explode('&ext=', $r['URL']);
                $ar = explode(".php?",$arExt[0]);
                //echo'<pre>';print_r($ar);echo'</pre>';
                //die();
                if(isset($ar[1])){
                    parse_str($ar[1], $Q_ar);
                    foreach($Q_ar as $k=>$v){
                        $values['get'][$k] = $v;
                    }
                    unset($Q_ar);
                    if(isset($arExt[1])){
                        $values['get']['ext'] .= $arExt[1];
                    }
                }
            }
        }
        //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/test.txt', print_r([$values['get'], $values['post'], $r], true)."\n", FILE_APPEND);

        return $values;
    }
}
