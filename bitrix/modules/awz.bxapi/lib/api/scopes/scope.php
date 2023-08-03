<?php
namespace Awz\BxApi\Api\Scopes;

class Scope implements \JsonSerializable
{
    /** @var string */
    protected string $code;
    protected string $status;

    const STATUS_OK = 'ok';
    const STATUS_ERR = 'no';

    /**
     * @var null
     */
    protected $customData;

    /**
     * Creates a new Scope.
     *
     * @param string $code Code of the scope.
     * @param string $status Status of the scope.
     * @param mixed|null $customData Data typically of key/value pairs that provide additional
     * user-defined information about the scope.
     */
    public function __construct(string $code, string $status = self::STATUS_OK, $customData = null)
    {
        $this->code = $code;
        $this->status = ($status === self::STATUS_OK) ? self::STATUS_OK : self::STATUS_ERR;
        $this->customData = $customData;
    }

    public static function createFromCode(string $code, $customData = null): array
    {
        return [
            new Scope($code, Scope::STATUS_OK, $customData),
            new Scope($code, Scope::STATUS_ERR, $customData)
        ];
    }

    /**
     * Returns the code of the scope.
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Returns the status of the scope.
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = ($status === self::STATUS_OK) ? self::STATUS_OK : self::STATUS_ERR;
    }

    /**
     * Разрешен ли данный scope
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    public function enable()
    {
        $this->status = self::STATUS_OK;
    }

    public function disable()
    {
        $this->status = self::STATUS_ERR;
    }

    /**
     * @return mixed|null
     */
    public function getCustomData()
    {
        return $this->customData;
    }

    public function __toString(): string
    {
        return $this->getCode();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->getCode(),
            'status' => $this->getStatus(),
            'customData' => $this->getCustomData(),
        ];
    }
}
