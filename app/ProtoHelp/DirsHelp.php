<?php


namespace App\ProtoHelp;


class DirsHelp
{
    /**
     * 获取所有目录列表
     *
     * @param  string  $path
     * @return array
     */
    public static function getDirs(string $path): array
    {
        $arr = [];
        if (is_dir($path)) {
            $dir = scandir($path);
            foreach ($dir as $value) {
                $sub_path = $path.'/'.$value;
                if ($value == '.' || $value == '..') {
                    continue;
                } else {
                    if (is_dir($sub_path)) {
                        $arr[] = $sub_path;
                        $arr   = array_merge($arr, self::getDirs($sub_path));
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 合并目录
     * @param  string  $source  要合并的文件夹
     * @param  string  $target  要合并的目的地
     * @return int 处理的文件数
     */
    public static function copy(string $source, string $target)
    {
        // 如果目标目录不存在，则创建。
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }
        // 搜索目录下的所有文件
        foreach (glob($source.'/*') as $filename) {
            if (is_dir($filename)) {
                // 如果是目录，递归合并子目录下的文件。
                self::copy($filename, $target. '/' .basename($filename));
            } elseif (is_file($filename)) {
                // 如果是文件，判断当前文件与目标文件是否一样，不一样则拷贝覆盖。
                // 这里使用的是文件md5进行的一致性判断，可靠但性能低，应根据实际情况调整。
                if (!file_exists($target. '/' .basename($filename)) || md5(file_get_contents($filename)) != md5(file_get_contents($target. '/' .basename($filename)))) {
                    copy($filename, $target. '/' .basename($filename));
                }
            }
        }
    }
}