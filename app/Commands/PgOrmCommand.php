<?php


namespace App\Commands;


use App\OrmHelp\OrmHelp;
use App\OrmHelp\OrmPgHelp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OrmCommand
 * @package App\Commands
 */
class PgOrmCommand extends Command
{
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    protected $config = [];
    protected $json = [];

    /**
     * @return \App\Commands\PgOrmCommand|void
     */
    protected function configure()
    {
        return $this->setName("make:pgorm")
            ->setDescription("生成go的orm文件")
            ->addArgument("path", InputArgument::OPTIONAL, "使用的pgsql配置文件")
            ->addArgument("out", InputArgument::OPTIONAL, "输出到的路径")
            ->setHelp("根据pgsql结构生成go的orm文件, 需要指定使用的mysql配置文件");
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

            OrmPgHelp::gen($datum, $tableFile);
        }
        $configDbFile = $outPath.'/config_db.go';
        if (!file_exists($configDbFile)) {
            file_put_contents(
                $configDbFile,
                OrmPgHelp::getDBConfig($this->config['dbname'])
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
        $sql = "SELECT * FROM pg_tables WHERE schemaname = 'public'";
        $res = $this->query($sql);
        $got = [];
        foreach ($res as $data) {
            $got[$data['tablename']] = $this->getTableInfo($data['tablename']);
        }
        return $got;
    }

    public function getTableInfo(string $tableName):array
    {
        $column = $this->query("
                SELECT
                    a.*,
                    col_description(a.attrelid,a.attnum) as comment,
                    format_type(a.atttypid,a.atttypmod) as type,
                    0 as pkey
                FROM pg_class as c,pg_attribute as a 
                where c.relname = '{$tableName}' and a.attrelid = c.oid and a.attnum > 0 and a.atttypid > 0
            ");
        if ($column) {
            $column = array_column($column, null, 'attname');
            //获取主键
            $pkey = $this->query("
                    SELECT pg_attribute.attname
                    FROM pg_constraint
                    INNER JOIN pg_class ON pg_constraint.conrelid = pg_class.oid
                    INNER JOIN pg_attribute ON pg_attribute.attrelid = pg_class.oid
                    AND pg_attribute.attnum = pg_constraint.conkey [ 1 ]
                    INNER JOIN pg_type ON pg_type.oid = pg_attribute.atttypid
                    WHERE pg_class.relname = '{$tableName}' AND pg_constraint.contype = 'p'
                ");
            if (count($pkey) > 0) {
                foreach ($column as $k => $v) {
                    if ($v['attname'] == $pkey[0]['attname']) {
                        $column[$k]['pkey'] = 1;
                    }
                }
            }
        }
        $info['name']   = $tableName;
        $info['db']     = $this->config['dbname'];
        $info['column'] = $column;
        $info['json']   = $this->json[$tableName] ?? [];

        return $info;
    }

    public function query(string $sql): array
    {
        $data = [];
        $conn = $this->conn();
        if ($result = pg_query($conn, $sql)) {
            while ($arr = pg_fetch_array($result)) {
                $data[] = $arr;
            }
            pg_close();
        }
        return $data;
    }

    public function iniConfig()
    {
        $path = $this->input->getArgument('path');
        if (!$path) {
            $path = HOME_PATH.'/config/pgsql.local.ini';
            if (!file_exists($path)) {
                $path = HOME_PATH.'/config/pgsql.ini';
            }
            if (!file_exists($path)) {
                $this->output->writeln("<error>无法读取文件, $path</error>");
            }
        } else {
            $path = realpath(HOME_PATH."/".$path);
        }
        $this->output->writeln("<info>使用配置文件 $path</info>");
        $this->config = parse_ini_file($path);
        $this->json   = json_decode(@file_get_contents($path.'.json')?:'{}', true);
    }


    public function conn()
    {
        $conn_string = "host={$this->config['hosts']} port={$this->config['port']} dbname={$this->config['dbname']} user={$this->config['username']} password={$this->config['password']}";
        $conn        = pg_connect($conn_string);

        // Check connection
        if (!$conn) {
            die("Connection failed: ".pg_last_error());
        }
        return $conn;
    }
}
