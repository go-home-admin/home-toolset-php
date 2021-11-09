<?php


namespace ProtoParser\FileParser\Service;


class Rpc
{
    protected $doc;
    protected $name;
    protected $parameter;
    protected $response;
    protected $options = [];

    /**
     * @return mixed
     */
    public function getDoc()
    {
        return trim($this->doc, "//");
    }

    /**
     * @param  mixed  $doc
     */
    public function setDoc($doc): void
    {
        $this->doc = $doc;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  mixed  $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * @param  mixed  $parameter
     */
    public function setParameter($parameter): void
    {
        $this->parameter = $parameter;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param  mixed  $response
     */
    public function setResponse($response): void
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param  array  $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}