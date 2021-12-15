<?php


namespace App\Commands;


use App\OrmHelp\OrmHelp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OrmCommand
 * @package App\Commands
 */
class OrmCommand extends Command
{
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    protected $config = [];

    /**
     * @return \App\Commands\OrmCommand|void
     */
    protected function configure()
    {
        return $this->setName("make:orm")
            ->setDescription("生成go的orm文件")
            ->addArgument("path", InputArgument::OPTIONAL, "使用的mysql配置文件")
            ->addArgument("out", InputArgument::OPTIONAL, "输出到的路径")
            ->setHelp("根据mysql结构生成go的orm文件, 需要指定使用的mysql配置文件");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;
        $outPath      = $this->getOut();
        $this->iniConfig();

        $data    = $this->getDbInfo();
        $outPath = $outPath.'/'.$this->config['dbname'];
        if (!is_dir($outPath)) {
            mkdir($outPath, 0750);
        }

        foreach ($data as $table => $datum) {
            $tableFile = $outPath.'/orm_'.$table.'.go';

            OrmHelp::gen($datum, $tableFile);
        }
        $configDbFile = $outPath.'/config_db.go';
        if (!file_exists($configDbFile)) {
            file_put_contents(
                $configDbFile,
                OrmHelp::getDBConfig($this->config['dbname'])
            );
            $this->output->writeln("<error>已经生成文件 $configDbFile, 请修改并实现DB函数</error>");
        }

        exec("cd {$outPath} && go fmt");
        return 0;
    }

    public function getOut(): string
    {
        $path = $this->input->getArgument('out');
        if (!$path) {
            $path = HOME_PATH.'/app/entity';
            if (!is_dir($path)) {
                mkdir($path, 0750);
            }
            $this->output->writeln("<info>go文件输出路径 $path</info>");
        } else {
            $path = HOME_PATH."/".$path;
            if (!is_dir($path)) {
                mkdir($path, 0750);
            }
        }
        return realpath($path);
    }

    public function getDbInfo()
    {
        $sql   = "
            SELECT
                A.* 
            FROM
                INFORMATION_SCHEMA.COLUMNS A 
            WHERE
                A.TABLE_SCHEMA = '{$this->config['dbname']}' 
            ORDER BY
                A.TABLE_SCHEMA,
                A.TABLE_NAME,
                A.ORDINAL_POSITION
            ";
        $datas = $this->query($sql);
        $got   = [];
        foreach ($datas as $data) {
            $got[$data['TABLE_NAME']]['name']                         = $data['TABLE_NAME'];
            $got[$data['TABLE_NAME']]['db']                           = $data['TABLE_SCHEMA'];
            $got[$data['TABLE_NAME']]['column'][$data['COLUMN_NAME']] = $data;
        }
        return $got;
    }

    public function query(string $sql): array
    {
        $data   = [];
        $mysqli = $this->db();
        /* If we have to retrieve large amount of data we use MYSQLI_USE_RESULT */
        if ($result = $mysqli->query($sql, MYSQLI_USE_RESULT)) {
            while ($obj = $result->fetch_object()) {
                $data[] = (array) $obj;
            }
            $result->close();
        }
        return $data;
    }

    public function iniConfig()
    {
        $path = $this->input->getArgument('path');
        if (!$path) {
            $path = HOME_PATH.'/config/mysql.local.ini';
            if (!file_exists($path)) {
                $path = HOME_PATH.'/config/mysql.ini';
            }
            if (!file_exists($path)) {
                $this->output->writeln("<error>无法读取文件, $path</error>");
            }
        } else {
            $path = realpath(HOME_PATH."/".$path);
        }
        $this->output->writeln("<info>使用配置文件 $path</info>");
        $this->config = parse_ini_file($path, true, INI_SCANNER_RAW);
        foreach ($this->config as $value){
            if (is_array($value)) {
                $this->config = $value;
                break;
            }
        }
    }


    public function db()
    {
        $conn = new \mysqli(
            $this->config['hosts'].":".$this->config['port'],
            $this->config['username'],
            trim($this->config['password'], "`")
        );

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: ".$conn->connect_error);
        }
        $conn->query('set names utf8');
        /* 连接数据库*/
        $conn->select_db($this->config['dbname']);
        return $conn;
    }
}