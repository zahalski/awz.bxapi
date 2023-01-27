<?php
namespace Awz\bxApi\Api\Scopes;

use Bitrix\Main\Type\Dictionary;

class ScopeCollection extends Dictionary {
    /**
     * Constructor ErrorCollection.
     * @param Scope[] $values Initial scopes in the collection.
     */
    public function __construct(array $values = null)
    {
        if($values)
        {
            $this->add($values);
        }
    }

    /**
     * Adds an array of scopes to the collection.
     * @param Scope[] $scopes
     * @return void
     */
    public function add(array $scopes)
    {
        foreach($scopes as $scope)
        {
            $this->setScope($scope);
        }
    }

    /**
     * Adds an scope to the collection.
     * @param Scope $scope An scope object.
     * @param mixed $offset Offset in the array.
     * @return void
     */
    public function setScope(Scope $scope, $offset = null)
    {
        parent::offsetSet($offset, $scope);
    }

    /**
     * \ArrayAccess thing.
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->setScope($value, $offset);
    }

    /**
     * @param string $code
     * @return null||Scope
     */
    public function getByCode(string $code): ?Scope
    {
        foreach($this->getValues() as $scope){
            if($scope->getCode() === $code) {
                return $scope;
            }
        }
        return null;
    }
}