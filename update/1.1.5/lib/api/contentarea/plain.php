<?php
namespace Awz\BxApi\Api\ContentArea;

use Bitrix\Main\Engine\Response\ContentArea\ContentAreaInterface;

class Plain implements ContentAreaInterface {

    private $content = null;

    public function __construct($text)
    {
        $this->content = $text;
    }

    public function getHtml(){
        return $this->content;
    }
}