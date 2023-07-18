<?php
namespace Awz\BxApi\Api\Filters\Request;

use Bitrix\Main\Type;

class SetFilter implements Type\IRequestFilter
{
    protected $name;
    protected $value;
    protected $type;

    public function __construct(string $name, $value = null, string $type = 'post')
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    public function filter(array $values)
    {
        if(!isset($values[$this->type])) $values[$this->type] = [];
        $values[$this->type][$this->name] = $this->value;
        return $values;
    }
}
