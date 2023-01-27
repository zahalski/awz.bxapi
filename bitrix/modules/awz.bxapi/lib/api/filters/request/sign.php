<?php
namespace Awz\bxApi\Api\Filters\Request;

use Bitrix\Main\Type;

class Sign implements Type\IRequestFilter
{
    protected $params = array();
    protected $values = array();

    public function __construct(array $params = array(), array $values = array())
    {
        $this->params = $params;
        $this->values = $values;
    }

    public function getKeys(){
        return $this->params;
    }

    public function getValues(){
        return $this->values;
    }

    /**
    * @param array $values
    * @return array
    */
    public function filter(array $values)
    {
        $valuesData = $this->getValues();
        $keys = $this->getKeys();

        if(isset($values['get']['signed'])){

            foreach($keys as $key){
                if(isset($valuesData[$key])){
                    $values['get'][$key] = $valuesData[$key];
                }
            }

        }

        if(isset($values['post']['signed'])){

            foreach($keys as $key){
                if(isset($valuesData[$key])){
                    $values['post'][$key] = $valuesData[$key];
                }else{
                    $values['post'][$key] = '';
                }
            }

        }

        return $values;
    }
}