<?php
namespace Awz\BxApi\Api\Scopes;

use Awz\BxApi\Api\Type\Parameters as ParametersType;

class Parameters extends ParametersType {

    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }

}