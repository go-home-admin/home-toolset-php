<?php


namespace App\Commands;


use App\CurdHelp\GoCurd;
use App\ProtoHelp\MysqlToProto;
use App\ProtoHelp\PgSqlToProto;
use ProtoParser\StringHelp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CurlCommand extends OrmCommand
{
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    protected $config = [];

    /**
     * @return \App\Commands\BeanCommand|\App\Commands\CurlCommand
     */
    protected function configure()
    {
        return $this->setName("make:curd")
            ->setDescription("生成go的curd基础文件")
            ->addArgument("path", InputArgument::OPTIONAL, "使用的pgsql配置文件")
            ->addOption("table", "t", InputOption::VALUE_OPTIONAL, "使用的数据表名称")
            ->addOption("model", "m", InputOption::VALUE_OPTIONAL, "生成的proto目录, 归属模块名称")
            ->addOption("controller", "c", InputOption::VALUE_OPTIONAL, "控制器名称")
            ->addOption("app", "a", InputOption::VALUE_OPTIONAL, "app", 'admin')
            ->addOption("doc", "", InputOption::VALUE_OPTIONAL, "备注", '')
            ->setHelp("根据mysql结构生成go的curd基础文件");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;
        $this->iniConfig();

        $helper    = $this->getHelper('question');
        $app       = $input->getOption('app');
        $tableName = $input->getOption('table');
        if (!$tableName) {
            $tables = $this->getTables();
            usleep(1);
            $question  = new Question('输入数据表名称ID: ');
            $tableName = $helper->ask($input, $output, $question);
            $tableName = $tables[$tableName];
            $this->output->writeln("使用了表名: {$tableName}");
            usleep(1);
        }

        $model = $input->getOption('model');
        if (!$model) {
            $question = new Question('输入代码放到哪个模块下('.$app.'/? 输入问号部分): ');
            $model    = $helper->ask($input, $output, $question);
            if (trim($model) == '') {
                $model = '';
            }
        }

        $doc = $input->getOption('doc');
        if (!$doc) {
            $question = new Question('输入模块注释(中文说明): ');
            $doc = $helper->ask($input, $output, $question);
            if (trim($doc) == '') {
                $doc = $tableName;
            }
        }

        $controller = $input->getOption('controller');
        if (!$controller) {
            $question   = new Question('输入控制器名称,小写下划线命名(空等于表名): ');
            $controller = $helper->ask($input, $output, $question);
            if (trim($controller) == '') {
                $controller = $tableName;
            }
        }

        $tableInfo = $this->getTableInfo($tableName);
        $info      = (new MysqlToProto($tableInfo, $model, $controller))->gen();

        GoCurd::init();
        GoCurd::gen([$this->config["dbname"], $tableName], $model, $controller, $info, $app, $doc);

        return 0;
    }

    public function getTables(): array
    {
        $sql = "select table_name from information_schema.tables where table_schema='".$this->config["dbname"]."'";
        $res = $this->query($sql);
        $got = $tows = [];

        $table = new Table($this->output);
        $i     = 1;
        foreach ($res as $data) {
            $tows[]    = [$i, $data['table_name']];
            $got[$i++] = $data['table_name'];
        }
        $table
            ->setHeaders(array('id', 'table_name'))
            ->setRows($tows);
        $table->render();
        return $got;
    }

    public static function toName(string $name): string
    {
        $name = StringHelp::toCamelCase($name);
        return ucfirst($name);
    }

    public function getTableInfo(string $tableName): array
    {
        $dbInfo = $this->getTableDbInfo($tableName);

        $info['name']   = $tableName;
        $info['db']     = $this->config['dbname'];
        $info['column'] = $dbInfo['column'];
        $info['json']   = $this->json[$tableName] ?? [];

        return $info;
    }

    public function getTableDbInfo(string $tableName): array
    {
        $sql   = "
            SELECT
                A.* 
            FROM
                INFORMATION_SCHEMA.COLUMNS A 
            WHERE
                A.TABLE_SCHEMA = '{$this->config['dbname']}' AND A.TABLE_NAME = '{$tableName}'
            ORDER BY
                A.TABLE_SCHEMA,
                A.TABLE_NAME,
                A.ORDINAL_POSITION
            ";
        $got   = [];
        foreach ($this->query($sql) as $data) {
            $got[$data['TABLE_NAME']]['name']                         = $data['TABLE_NAME'];
            $got[$data['TABLE_NAME']]['db']                           = $data['TABLE_SCHEMA'];
            $got[$data['TABLE_NAME']]['column'][$data['COLUMN_NAME']] = $data;
        }
        return end($got);
    }
}