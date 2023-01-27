<?php
namespace Awz\bxApi\Api\Scopes;

use Bitrix\Main\Engine\ActionFilter\Base;

abstract class BaseFilter extends Base implements IScope{

    /**
     * bitrix Scopes
     * @var string[]
     */
    protected array $scopesBx;
    /**
     * @var ScopeCollection
     */
    protected ScopeCollection $scopesCollection;
    /**
     * Required Scopes codes to start logic
     * @var string[]
     */
    protected array $scopesRequired;

    /**
     * @var Parameters
     */
    protected Parameters $params;

    /**
     * BaseFilter constructor.
     * @param array $params
     * @param string[] $scopesBx
     * @param Scope[] $scopes
     * @param string[] $scopesRequired
     */
    public function __construct(
        array $params = array(), array $scopesBx = array(),
        array $scopes = array(), array $scopesRequired = array()
    )
    {
        parent::__construct();

        $this->scopesBx = $scopesBx;
        $this->scopesCollection = new ScopeCollection();
        $this->scopesCollection->add($scopes);
        $this->scopesRequired = $scopesRequired;
        $this->params = new Parameters($params);
    }

    /**
     * @return Parameters
     */
    protected function getParams(): Parameters
    {
        return $this->params;
    }

    /**
     * @return string[]
     */
    public function listAllowedScopes(): array
    {
        if(!$this->scopesBx){
            return parent::listAllowedScopes();
        }
        return $this->scopesBx;
    }

    //public function getParams

    /**
     * Разрешение Scope из конструктора
     */
    final public function enableScope(){
        /* @var Scope $scope */
        foreach($this->scopesCollection as $scope){
            if($scope->isEnabled()){
                $this->getAction()->getController()->addScopeApi($scope);
            }
        }
    }

    /**
     * Запрет Scope из конструктора
     */
    final public function disableScope(){
        /* @var Scope $scope */
        foreach($this->scopesCollection as $scope){
            if(!$scope->isEnabled()){
                $this->getAction()->getController()->addScopeApi($scope);
            }
        }
    }

    /**
     * @return bool
     */
    final public function checkRequire(): bool
    {
        $scopes = $this->scopesRequired;
        if(empty($scopes)) return true;
        $collection = $this->getAction()->getController()->getScopeCollection();
        /* @var ScopeCollection $collection */
        foreach($scopes as $scopeCode){
            $scope = $collection->getByCode($scopeCode);
            if(!$scope) return false;
            if(!$scope->isEnabled()) return false;
        }
        return true;
    }

}