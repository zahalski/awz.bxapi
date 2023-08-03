<?php
namespace Awz\BxApi\Api\Filters\Request;

use Bitrix\Main\Type;
use Awz\BxApi\HandlersTable;

class ParseHandler implements Type\IRequestFilter
{
    protected string $paramName;
    protected string $paramNameId;
    protected string $paramNameHash;
    protected string $paramType;
    protected string $paramUrlNameId;
    protected string $paramUrlNameHash;

    public function __construct(
        string $paramName,
        string $paramNameId, string $paramNameHash,
        string $paramType='post',
        string $paramUrlNameId='ID',
        string $paramUrlNameHash='TOKEN'
    )
    {
        $this->paramName = $paramName;
        $this->paramNameId = $paramNameId;
        $this->paramNameHash = $paramNameHash;
        $this->paramType = $paramType;
        $this->paramUrlNameId = $paramUrlNameId;
        $this->paramUrlNameHash = $paramUrlNameHash;
    }

    public function filter(array $values)
    {
        if(isset($values['get'][$this->paramName]) || isset($values['post'][$this->paramName])){
            $url = isset($values['post'][$this->paramName]) ? $values['post'][$this->paramName] : $values['get'][$this->paramName];
            if($url){
                $urlAr = explode("&", str_replace('?','&',$url));
                foreach($urlAr as $v){
                    $urlParamAr = explode('=',$v);
                    if(count($urlParamAr)==2){
                        if($urlParamAr[0]===$this->paramUrlNameId){
                            $values[$this->paramType][$this->paramNameId] = preg_replace("/[^0-9]/is",'',$urlParamAr[1]);
                        }else if($urlParamAr[0]===$this->paramUrlNameHash){
                            $values[$this->paramType][$this->paramNameHash] = preg_replace("/[^0-9A-Za-z]/is",'',$urlParamAr[1]);
                        }
                    }
                }
            }
        }
        return $values;
    }
}
