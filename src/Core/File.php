<?php
namespace Seeruo\Core;

use Exception;
use \Seeruo\Core\Log;

/**
 * 文件操作
 */
class File
{
    /**
     * 创建文件
     * @param  [type] $fileName [文件路径]
     * @param  string $content  [文件内容]
     */
    static public function createFile($fileName, $content='')
    {
        $fileDirc = dirname($fileName);
        $fileNameRc = $fileName;
        if (strstr(PHP_OS, 'WIN')) {
            $fileDirc   = mb_convert_encoding($fileDirc, 'gbk', 'UTF-8');
            $fileNameRc = mb_convert_encoding($fileName, 'gbk', 'UTF-8');
        }
        file_exists($fileDirc) || mkdir($fileDirc, 0777, true);
        $status = file_put_contents($fileNameRc, $content, LOCK_EX);
        if ($status === false) {
            throw new Exception('Create '.$fileName.' failed!');
        }
        
        Log::info('Create '.$fileName.' done!');
    }
    /**
     * 复制文件
     * @param  [type] $path   [源路径]
     * @param  [type] $topath [目的路径]
     */
    static public function copyFolder($path, $topath)
    {
        file_exists(dirname($topath)) || mkdir(dirname($topath), 0777, true);
        copy($path, $topath);
    }
    /**
     * 获取文件内容
     * @param  [type] $fileName [文件名]
     */
    static public function getContent($file_path)
    {
        $file = []; // 文件解析完之后的数据
        $file_path = trim($file_path);
        // 初始配置
        $file_set = [
            'file_name' => basename($file_path),
            'file_dire' => dirname($file_path),
            'file_path' => dirname($file_path). DIRECTORY_SEPARATOR . basename($file_path),
            'title'     => '',
            'date'      => date('Y-m-d'),
            'tags'      => '',
            'type'      => '',
            'author'    => '',
            'desc'      => '',
            'keywords'  => '',
            'from'      => '',
        ];
        // 检查是否为md文件，不是的话不做后续解析
        $string = file_get_contents($file_path);
        $string = explode("---", trim($string));
        $string = array_filter($string);
        $setting = isset($string[1]) ? $string[1] : ''; // 文章配置
        $content = isset($string[2]) ? $string[2] : ''; // 文章内容

        // 文章配置
        $setting = explode("\n", trim($setting));
        $setting = array_filter($setting);
        foreach ($setting as $key => $v) {
            $s = explode(":", trim($v));
            $key = trim($s[0]);
            unset($s[0]);
            $value = trim( implode($s, ':') );
            if (!empty($value)) {
                $file_set[$key] = $value;
            }
        }

        // 处理文章标签
        if (isset($file_set['tags'])) {
            $file_set['tags'] = array_filter(explode(',', $file_set['tags']));
        }

        $file['setting'] = $file_set;
        $file['content'] = $content;
        return $file;
    }

    /**
     * 获取文件列表
     * @param  [type] $directory [文件仓库路径]
     * @return [type]            [所有文件列表]
     */
    static public function getFiles($directory='') {
        if (empty($directory)) {
            Log::info( 'Miss folder path', 'error');
        }

        if (substr($directory, -1, 1) === '/') {
            $directory = substr($directory, 0, strlen($directory)-1);
        }

        if($dir = opendir($directory)) {
            $tmp = Array();
            while($file = readdir($dir)) {
                if($file != "." && $file != ".." && $file[0] != '.') {
                    if(is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                        $tmp2 = self::getFiles($directory . DIRECTORY_SEPARATOR . $file);
                        if(is_array($tmp2)) {
                            $tmp = array_merge($tmp, $tmp2);
                        }
                    } else {
                        $file_path = $directory .DIRECTORY_SEPARATOR. $file;
                        // 解析文件内容及配置
                        $content = self::getContent($file_path);
                        $config = $content['setting']; // 文章配置
                        $tmp[] = $content['setting'];
                    }
                }
            }
            closedir($dir);
            return $tmp;
        }
    }
}