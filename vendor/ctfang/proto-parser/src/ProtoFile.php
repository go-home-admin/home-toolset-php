<?php


namespace ProtoParser;


class ProtoFile
{
    protected $file;
    protected $package;
    protected $imports = [];
    protected $options = [];
    protected $services = [];
    protected $messages = [];
    protected $enums = [];

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param  mixed  $file
     * @return ProtoFile
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param  mixed  $package
     * @return ProtoFile
     */
    public function setPackage($package)
    {
        $this->package = $package;
        return $this;
    }

    /**
     * @return array
     */
    public function getImports(): array
    {
        return $this->imports;
    }

    /**
     * @param  array  $imports
     * @return ProtoFile
     */
    public function setImports(array $imports): ProtoFile
    {
        $this->imports = $imports;
        return $this;
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
     * @return ProtoFile
     */
    public function setOptions(array $options): ProtoFile
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param  array  $services
     * @return ProtoFile
     */
    public function setServices(array $services): ProtoFile
    {
        $this->services = $services;
        return $this;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param  array  $messages
     * @return ProtoFile
     */
    public function setMessages(array $messages): ProtoFile
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * @return array
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    /**
     * @param  array  $enums
     * @return ProtoFile
     */
    public function setEnums(array $enums): ProtoFile
    {
        $this->enums = $enums;
        return $this;
    }
}