<?php
namespace Awz\BxApi\Api\Filters\Request;

use Bitrix\Main\Type;

class AppAuthActivity implements Type\IRequestFilter
{

    public function __construct()
    {

    }

    /**
     * @param array $values
     * @return array
     */
    public function filter(array $values)
    {

        if(isset($values['post']['auth']['domain'])){
            $values['post']['domain'] = $values['post']['auth']['domain'];
            $values['get']['domain'] = $values['post']['auth']['domain'];
        }

        return $values;
    }
}