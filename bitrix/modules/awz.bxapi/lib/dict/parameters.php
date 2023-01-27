<?php

namespace Awz\BxApi\Dict;

abstract class Parameters {

    protected $params = array();

    public function __construct(array $params = array())
    {
        $this->setParameters($params);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function setParameters(array $params)
    {
        foreach($params as $code=>$value){
            $code = (string) $code;
            if($code)
                $this->setParameter($code, $value);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setParameter(string $name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getParameter(string $name, $default=null){
        if(isset($this->params[$name]))
            return $this->params[$name];
        return $default;
    }

    public function getParam(string $name, $default=null)
    {
        return $this->getParameter($name, $default);
    }

    public function setParam(string $name, $value)
    {
        return $this->setParameter($name, $value);
    }

}