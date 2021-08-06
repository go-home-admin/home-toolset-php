<?php


namespace GoLang\Parser;

class GolangParser
{
    protected $file;
    protected $fileInfo;
    private $imports;

    public function parser(GolangToArray $goArray): self
    {
        $self       = clone $this;
        $self->file = $goArray->file;

        $self->fileInfo = ArrayToFileInfo::toInfo($goArray);
        return $self;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }


    /**
     * @return string
     */
    public function getPackage(): string
    {
        /** @var \GoLang\Parser\FileParser\Package $item */
        foreach ($this->fileInfo['package'] ?? [] as $item) {
            return $item->getValue();
        }
        return 'main';
    }

    /**
     * @return \GoLang\Parser\FileParser\Type[]
     */
    public function getType(): array
    {
        return $this->fileInfo['type'] ?? [];
    }

    /**
     * @return \GoLang\Parser\FileParser\Func[]
     */
    public function getFunc(): array
    {
        return $this->fileInfo['func'] ?? [];
    }


    /**
     * @param  string  $name
     * @return bool
     */
    public function hasFunc(string $name): bool
    {
        foreach ($this->getFunc() as $func){
            if ($name == $func->getName()){
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getImports(): array
    {
        if ($this->imports === null) {
            $got = [];
            foreach ($this->fileInfo['import'] ?? [] as $import) {
                $values = $import->getValue();
                foreach ($values as $alias => $value) {
                    $got[$alias] = $value;
                }
            }
            $this->imports = $got;
        }

        return $this->imports;
    }


    /**
     * @param  string  $alias
     * @return string
     */
    public function getImport(string $alias): string
    {
        $all = $this->getImports();
        foreach ($all as $name => $import) {
            if ($alias == $name) {
                return $import;
            }
        }
        return "";
    }

    /**
     * 修改本文件所有的引入别名
     * @param  string  $alias
     * @param  string  $newAlias
     */
    public function changeAliasName(string $alias, string $newAlias)
    {
        // 结构体属性修改
        foreach ($this->getType() as $type) {
            $attrs = $type->getAttributes();
            foreach ($attrs as $attr) {
                $aliasCheck = $attr->getStructAlias();
                if ($aliasCheck == $alias) {
                    $attr->setStructAlias($newAlias);
                    $attr->setType("{$newAlias}.".$attr->getStruct());
                }
            }
        }

        // 引入修改
        $this->imports = null;
        /** @var \GoLang\Parser\FileParser\Import $import */
        foreach ($this->fileInfo['import'] ?? [] as $import) {
            $values = $import->getValue();
            foreach ($values as $aliasCheck => $value) {
                if ($aliasCheck == $alias) {
                    unset($values[$aliasCheck]);
                    $values[$newAlias] = $value;
                }
            }
            $import->setValue($values);
        }
    }
}