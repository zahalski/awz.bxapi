<?php
namespace Awz\bxApi\Api\Scopes;

interface IScope
{
    public function enableScope();
    public function disableScope();
    public function checkRequire();
}