<?php
namespace Awz\bxApi\Api\Scopes;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\Controller as BxController;
use Bitrix\Main\Error;
use Bitrix\Main\Request;

class Controller extends BxController
{
    public bool $processingError = false;
    public ScopeCollection $scopesCollection;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->scopesCollection = new ScopeCollection();
    }

    protected function runProcessingThrowable(\Throwable $throwable)
    {
        $this->processingError = true;
        if ($throwable instanceof BinderArgumentException)
        {
            $currentControllerErrors = $this->getErrors();
            $errors = $throwable->getErrors();
            if ($errors)
            {
                foreach ($errors as $error)
                {
                    if (in_array($error, $currentControllerErrors, true)) continue;
                    $this->errorCollection[] = $error;
                }
            }elseif ($throwable instanceof ArgumentNullException){
                $this->errorCollection[] = new Error(
                    $throwable->getMessage(), self::ERROR_REQUIRED_PARAMETER
                );
            }else{
                $this->errorCollection[] = new Error($throwable->getMessage(), $throwable->getCode());
            }
        }
        elseif ($throwable instanceof \Exception)
        {
            if ($throwable instanceof ArgumentNullException)
            {
                $this->errorCollection[] = new Error(
                    $throwable->getMessage(), self::ERROR_REQUIRED_PARAMETER
                );
            }else{
                $this->errorCollection[] = new Error($throwable->getMessage(), $throwable->getCode());
            }
        }
        elseif ($throwable instanceof \Error)
        {
            $this->errorCollection[] = new Error('php error', $throwable->getCode());
        }
    }

    protected function addError(Error $error): Controller
    {
        if($this->processingError) {
            $this->processingError = false;
            return $this;
        }

        $this->errorCollection[] = $error;

        return $this;
    }

    public function getScopeCollection(): ScopeCollection
    {
        return $this->scopesCollection;
    }

    public function addScopeApi(Scope $scope){
        $scopeCurrent = $this->getScopeCollection()->getByCode($scope->getCode());
        if($scopeCurrent){
            $scopeCurrent->setStatus($scope->getStatus());
        }else{
            $this->getScopeCollection()->add([$scope]);
        }
    }

    /**
     * @param string[] $scopes
     * @return bool
     */
    final public function checkRequire(array $scopes = array()): bool
    {
        if(empty($scopes)) return true;
        $collection = $this->getScopeCollection();
        /* @var ScopeCollection $collection */
        foreach($scopes as $scopeCode){
            $scope = $collection->getByCode($scopeCode);
            if(!$scope) return false;
            if(!$scope->isEnabled()) return false;
        }
        return true;
    }

}