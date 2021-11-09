<?php


namespace ProtoParser\Swagger;


class Swagger
{
    protected $swagger = "2.0";
    protected $info = [
        'description'    => 'This is a sample server Petstore server.  You can find out more about Swagger at [http://swagger.io](http://swagger.io) or on [irc.freenode.net, #swagger](http://swagger.io/irc/).  For this sample, you can use the api key `special-key` to test the authorization filters.',
        'version'        => '1.0.5',
        'title'          => 'Swagger Petstore',
        'termsOfService' => 'http://swagger.io/terms/',
        'contact'        => [
            'email' => 'apiteam@swagger.io',
        ],
        'license'        => [
            'name' => 'Apache 2.0',
            'url'  => 'http://www.apache.org/licenses/LICENSE-2.0.html',
        ],
    ];
    protected $host = "127.0.0.1";
    protected $basePath = "/v2";

    /**
     * [
     *      {
     *      "name": "pet",
     *      "description": "Everything about your Pets",
     *      "externalDocs": {
     *              "description": "Find out more",
     *              "url": "http://swagger.io"
     *          }
     *      }
     * ]
     * @var Tag[]
     */
    protected $tags = [];
    protected $schemes = [
        "https",
        "http"
    ];
    protected $paths = [];

    /**
     * 安全定义对象 安全方案定义规范,可以使用。
     * 全局定义等等, header token 等等
     * @var array|object
     */
    protected $securityDefinitions = [];

    /**
     * 定义对象    一个对象数据类型生产和使用操作。
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * 额外的外部文档。
     * @var string[]
     */
    protected $externalDocs = [
        "description" => "Find out more about Swagger",
        "url"         => "http://swagger.io"
    ];

    /**
     * @return mixed
     */
    public function getSwagger()
    {
        return $this->swagger;
    }

    /**
     * @param  mixed  $swagger
     */
    public function setSwagger(string $swagger): void
    {
        $this->swagger = $swagger;
    }

    /**
     * @return mixed
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * @param  mixed  $info
     */
    public function setInfo(array $info): void
    {
        $this->info = $info;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param  mixed  $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param  mixed  $basePath
     */
    public function setBasePath($basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            $tags[] = $tag->toArray();
        }
        return $tags;
    }

    /**
     * @param  mixed  $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @return mixed
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * @param  mixed  $schemes
     * @throws \Exception
     */
    public function setSchemes(array $schemes): void
    {
        foreach ($schemes as $scheme) {
            if (!in_array($scheme, ["https", "http"])) {
                throw new \Exception('setSchemes must of "https", "http"');
            }
        }
        $this->schemes = $schemes;
    }

    /**
     * @return mixed
     */
    public function getPaths()
    {
        $got = [];
        foreach ($this->paths as $key => $paths) {
            foreach ($paths as $method=>$path){
                $got[$key][$method] = $path->toArray();
            }
        }
        return $got;
    }

    /**
     * @param  mixed  $paths
     */
    public function setPaths($paths): void
    {
        $this->paths = $paths;
    }

    /**
     * @param  Path  $path
     */
    public function addPath(Path $path): void
    {
        $this->paths[$path->getKey()][$path->getMethod()] = $path;
    }

    /**
     * @return mixed
     */
    public function getSecurityDefinitions()
    {
        return $this->securityDefinitions;
    }

    /**
     * @param  mixed  $securityDefinitions
     */
    public function setSecurityDefinitions($securityDefinitions): void
    {
        $this->securityDefinitions = $securityDefinitions;
    }

    /**
     * @return mixed
     */
    public function getDefinitions()
    {
        $got = [];
        foreach ($this->definitions as $key => $paths) {
            $got[$key] = $paths->toArray();
        }
        return $got;
    }

    /**
     * @param  mixed  $definitions
     */
    public function setDefinitions($definitions): void
    {
        $this->definitions = $definitions;
    }

    public function addDefinition(Definition $definition)
    {
        $this->definitions[$definition->getName()] = $definition;
    }

    /**
     * @return mixed
     */
    public function getExternalDocs()
    {
        return $this->externalDocs;
    }

    /**
     * @param  mixed  $externalDocs
     */
    public function setExternalDocs($externalDocs): void
    {
        $this->externalDocs = $externalDocs;
    }

    public function toArray(): array
    {
        return [
            "swagger"             => $this->swagger,
            "info"                => $this->info,
            "host"                => $this->host,
            "basePath"            => $this->basePath,
            "tags"                => $this->getTags(),
            "schemes"             => $this->schemes,
            "paths"               => $this->getPaths(),
            "securityDefinitions" => $this->securityDefinitions,
            "definitions"         => $this->getDefinitions(),
            "externalDocs"        => $this->externalDocs,
        ];
    }
}