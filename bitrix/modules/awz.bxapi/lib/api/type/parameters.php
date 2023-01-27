<?php

namespace Awz\BxApi\Api\Type;

abstract class Parameters {

    protected array $params = array();

    public function __construct(array $params = array())
    {
        $this->setParameters($params);
    }

    /**
     * @param array $params
     * @return $this
     */
    protected function setParameters(array $params): Parameters
    {
        foreach($params as $code=>$value){
            $code = (string) $code;
            if($code)
                $this->set($code, $value);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set(string $name, $value): Parameters
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, $default=null){
        if(isset($this->params[$name]))
            return $this->params[$name];
        return $default;
    }

}