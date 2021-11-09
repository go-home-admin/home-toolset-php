<?php


namespace ProtoParser\Swagger;


class Path
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var
     */
    protected $method;

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param  mixed  $method
     */
    public function setMethod($method, bool $setConsumes = false): void
    {
        $this->method = $method;
    }

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * 什么操作的一个简短的总结。 最大swagger-ui可读性,这一领域应小于120个字符。
     * @var string
     */
    protected $summary;

    /**
     * 详细解释操作的行为。GFM语法可用于富文本表示。
     * @var string
     */
    protected $description;

    /**
     * 独特的字符串用于识别操作。 id必须是唯一的在所有业务中所描述的API。 工具和库可以使用operationId来唯一地标识一个操作,因此,建议遵循通用的编程的命名约定。
     * @var string
     */
    protected $operationId;

    /**
     * MIME类型的列表操作可以使用。 这将覆盖consumes定义在炫耀的对象。 空值可用于全球定义清楚。 值必须是所描述的Mime类型。
     * @var string[]
     */
    protected $consumes = [
        "application/json",
        "multipart/form-data",
        "application/xml"
    ];

    /**
     * MIME类型的列表操作可以产生。 这将覆盖produces定义在炫耀的对象。 空值可用于全球定义清楚。 值必须是所描述的Mime类型。
     * @var string[]
     */
    protected $produces = [
        "application/json",
        "multipart/form-data",
        "application/xml"
    ];

    /**
     * 适用于该操作的参数列表。 如果已经定义了一个参数道路项目新定义将覆盖它,但不能删除它。 必须不包含重复的参数列表。 一个独特的参数定义的组合的名字和位置。 可以使用列表引用对象链接到参数的定义的对象的参数。 可以有一个“身体”参数。
     * @var Parameter[]
     */
    protected $parameters = [];

    /**
     * "405": {
     *     "description": "Invalid input"
     * }
     * @var Response[]
     */
    protected $responses = [];

    /**
     * "security": [
     *    {
     *        "petstore_auth": [
     *           "write:pets",
     *           "read:pets"
     *        ]
     *    }
     * ]
     * @var array[]
     */
    protected $security = [];

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param  string  $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        $got = [];
        foreach ($this->tags as $tag=>$path) {
            $got[] = $tag;
        }
        return $got;
    }

    /**
     * @param  array  $tags
     */
    public function setTags(array $tags): void
    {
        foreach ($tags as $tag=>$path) {
            $this->tags[$tag] = $path;
        }
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * @param  string  $summary
     */
    public function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param  string  $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getOperationId(): string
    {
        return $this->operationId;
    }

    /**
     * @param  string  $operationId
     */
    public function setOperationId(string $operationId): void
    {
        $this->operationId = $operationId;
    }

    /**
     * @return string[]
     */
    public function getConsumes(): array
    {
        return $this->consumes;
    }

    /**
     * @param  string[]  $consumes
     */
    public function setConsumes(array $consumes): void
    {
        $this->consumes = $consumes;
    }

    /**
     * @return string[]
     */
    public function getProduces(): array
    {
        return $this->produces;
    }

    /**
     * @param  string[]  $produces
     */
    public function setProduces(array $produces): void
    {
        $this->produces = $produces;
    }

    /**
     * @return array[]
     */
    public function getParameters(): array
    {
        $got = [];
        foreach ($this->parameters as $parameter) {
            $got[] = $parameter->toArray();
        }
        return $got;
    }

    /**
     * @param  array[]  $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array[]
     */
    public function getResponses(): array
    {
        $got = [];
        foreach ($this->responses as $status => $response) {
            $got[$status] = $response->toArray();
        }
        return $got;
    }

    /**
     * @param  array[]  $responses
     */
    public function setResponses(array $responses): void
    {
        $this->responses = $responses;
    }

    /**
     * @return array[]
     */
    public function getSecurity(): array
    {
        return $this->security;
    }

    /**
     * @param  array[]  $security
     */
    public function setSecurity(array $security): void
    {
        $this->security = $security;
    }

    public function toArray(): array
    {
//        foreach ($this as $k => $v) {
//            echo $k, "\n";
//        }

        $got = [
            "tags"        => $this->getTags(),
            "summary"     => $this->summary,
            "description" => $this->description,
            "operationId" => $this->operationId,
            "consumes"    => $this->consumes,
            "produces"    => $this->produces,
            "parameters"  => $this->getParameters(),
            "responses"   => $this->getResponses(),
            "security"    => $this->security,
        ];
        foreach ($got as $i => $value) {
            if ($value === null) {
                unset($got[$i]);
            }
        }
        return $got;
    }
}