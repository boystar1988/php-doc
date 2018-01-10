<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * @name 文档生成器
 * @desc 可根据PHP注释生成开发文档
 * @author zhaozhuobin
 * @date 2017-12-21
 */
class DocCommand  extends Command
{

    /**
     * @name 是否需要推送的远程服务器管理
     * @var $needPath
     */
    protected $needPush;

    /**
     * @name 输出目录
     * @var $outputDir
     */
    protected $outputDir;

    /**
     * @name 模板目录
     * @var $templateDir
     */
    protected $templateDir;

    /**
     * @name API推送域名
     * @var string
     */
    protected $apiDomain = 'http://xxx.com';

    /**
     * @name API推送Key
     * @var string
     */
    protected $apiKey = '';

    /**
     * @name API推送Token
     * @var string
     */
    protected $apiToken = '';

    /**
     * @name 反射类
     * @var ReflectionClass
     */
    protected $reflectClass;

    /**
     * @name 配置
     * @command php app/console app:create-doc <class> [<type>] [<template>]
     * @return null
     */
    protected function configure ()
    {
        $this->setName('app:create-doc')
            ->addArgument('class',InputArgument::REQUIRED,'完整类名(包含命名空间)')
            ->addArgument('template',InputArgument::OPTIONAL,'模板主题','default')
            ->addOption('push',null,null,'加上该参数表示推送到服务器API管理')
            ->addUsage('--push "demo\User"')
            ->setDescription("自动生成文档工具");
    }

    /**
     * @name 文档生成器
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    public function execute ( InputInterface $input , OutputInterface $output )
    {
        $class = $input->getArgument('class');
        $template = $input->getArgument('template');
        $this->needPush = $input->getOption('push');
        $this->templateDir = realpath(dirname(__FILE__).'/../template');
        $this->outputDir = realpath(dirname(__FILE__)."/../documents");
        try{
            if(!$this->outputDir){
                throw new \Exception('输出目录不存在');
            }
            if(!class_exists($class)){
                throw new \Exception('未找到该类');
            }
            $this->reflectClass = new ReflectionClass($class);
            $path = $this->export($template);
            if(empty($path)){
                throw new \Exception('文件保存失败');
            }
            $output->writeln("<info>执行成功,文件保存路径:{$path}</info>");
        }catch (\Exception $e){
            $output->writeln("<error>出错了:".$e->getMessage()."</error>");
        }
    }

    /**
     * @name 提取注释参数
     * @param string $docStr 注释字符串
     * @return array
     */
    private function extract($docStr='')
    {
        $data = [];
        $pattern = "/(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)/";
        preg_match_all($pattern, $docStr, $matches, PREG_PATTERN_ORDER);
        $res = $matches[0];
        unset($matches);
        foreach($res as $k=>$v){
            $pattern = "/@([a-zA-Z]+)\s*(.*)/";
            preg_match_all($pattern, $docStr, $matches, PREG_PATTERN_ORDER);
            if(!empty($matches[1])){
                if(in_array($matches[1][$k],['param'])){
                    $tmpParamsArr = preg_split('/\s+/',$matches[2][$k]??'');
                    $tmpParams['paramType'] = $tmpParamsArr[0]??'';
                    $tmpParams['paramName'] = preg_replace('/\$/','',$tmpParamsArr[1]??'');
                    $tmpParams['paramComment'] = $tmpParamsArr[2]??'';
                    $data[$matches[1][$k]][] = $tmpParams;
                }else{
                    $data[$matches[1][$k]] = ($matches[2][$k]??'');
                }
            }
        }
        return $data;
    }

    /**
     * @name 模板渲染
     * @param string $tplName 模板名称
     * @return string
     * @throws \Exception
     */
    private function export($tplName)
    {
        $tpl = $this->templateDir.DIRECTORY_SEPARATOR.$tplName;
        $path = $this->exportApi($tpl.DIRECTORY_SEPARATOR.'api.md');
        return $path;
    }

    /**
     * @name Api模板渲染
     * @param string $tpl 模板文件
     * @return string
     */
    private function exportApi($tpl)
    {
        $formatContent = file_get_contents($tpl);
        preg_match("/{{block-method}}((.|\s)*){{endBlock-method}}/",$formatContent,$match);
        preg_match("/{{block-param}}\s((.|\s)*){{endBlock-param}}/",$formatContent,$matchParam);
        $methodContent = '';
        if(!$this->reflectClass instanceof ReflectionClass){
            return '';
        }
        $classDoc = $this->reflectClass->getDocComment();
        $classDoc = $this->extract($classDoc);
        $param['author']        = $classDoc['author']??'未命名作者';
        $param['date']          = $classDoc['date']??date('Y-m-d');
        $param['apiTitle']      = $classDoc['name']??'未命名API';
        $param['apiName']       = $classDoc['desc']??'未命名功能模块';
        $param['module']        = $classDoc['module']??'';
        $paramBlock = $matchParam[1]??'';
        $methods = $this->reflectClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach($methods as $k=>$v){
            //过滤魔术方法和内置方法
            if(preg_match('/(__|init)/',$v->getName()) || !$v->isUserDefined()){
                continue;
            }
            $methodParams = $v->getParameters();
            $tempMethodDoc = $this->extract($v->getDocComment());
            preg_match('/@return\s+([a-zA-Z]+)\s+([^@]*)(@|\*\/)/',$v->getDocComment(),$returnMatch);
            if(isset($returnMatch[2])){
                $returnMatch[1] = $returnMatch[2];
                unset($returnMatch[2]);
            }
            $temParam['methodTitle']    = $tempMethodDoc['name']??'方法名';
            $temParam['methodDesc']     = $tempMethodDoc['desc']??'方法描述';
            $temParam['methodComment']  = $v->getDocComment();
            $temParam['methodName']     = $v->getName();
            $temParam['methodRoute']    = $tempMethodDoc['route']??'路由名';
            $temParam['methodParams']   = (count($methodParams)>0 ? '$' : '').str_replace(',',',$',implode(',',array_column($methodParams,'name')));
            if(preg_match('/\[/',$tempMethodDoc['return']??'')){
                $temParam['methodReturn']   = preg_replace('/\*/','',$returnMatch[1]??'null');
                $temParam['methodReturn']   = eval("return var_export({$temParam['methodReturn']}??null,true);");
                $temParam['methodReturn']   = str_replace(['array (',')'],['[',']'],$temParam['methodReturn']);
            }else{
                $temParam['methodReturn']   = $tempMethodDoc['return']??'null';
            }
            $temParam['params']         = $tempMethodDoc['param']??[];
            $param['method'][$k] = $temParam;
            unset($tempMethodDoc,$temParam,$returnMatch);
        }
        foreach($param['method'] as $mv){
            $methodContent .= str_replace([
                '{{$methodTitle}}',
                '{{$methodDesc}}',
                '{{$methodComment}}',
                '{{$methodName}}',
                '{{$methodRoute}}',
                '{{$methodParams}}',
                '{{$methodReturn}}',
                '{{$method}}',
            ],[
                $mv['methodTitle']??'',
                $mv['methodDesc']??'',
                $mv['methodComment']??'',
                $mv['methodName']??'',
                $mv['methodRoute']??'',
                $mv['methodParams']??'',
                $mv['methodReturn']??'',
                $mv['method']??'POST',
            ],$match[1]??'');
            $paramContent = '';
            foreach($mv['params'] as $kk=>$vv){
                $paramContent .= str_replace([
                    '{{$paramType}}',
                    '{{$paramName}}',
                    '{{$paramComment}}',
                ],[
                    $vv['paramType']??'类型',
                    $vv['paramName']??'参数名',
                    $vv['paramComment']??'参数描述',
                ],$paramBlock);
            }
            $methodContent = preg_replace('/{{block-param}}(\S|\s)*{{endBlock-param}}/',$paramContent,$methodContent);
        }

        $contents = str_replace([
            '{{$author}}',
            '{{$date}}',
            '{{$apiTitle}}',
            '{{$apiName}}',
        ],[
            $param['author']??'',
            $param['date']??'',
            $param['apiTitle']??'',
            $param['apiName']??'',
        ],$formatContent);
        $contents = preg_replace('/{{block-method}}(.|\s)*{{endBlock-method}}/',$methodContent,$contents);
        if($this->needPush){
            $res = $this->push([
                'title'=>"{$param['apiTitle']}",
                'content'=>$contents,
                'catName'=>'API文档'
            ]);
            if($res['error_code']!=0){
                $path = '';
            }else {
                $path = $this->apiDomain . '/index.php?s=/' . ($res['data']['item_id']??0) . '&page_id=' . ($res['data']['page_id']??0);
            }
        }else{
            $dirPath = $this->outputDir.DIRECTORY_SEPARATOR."API文档".
                (empty($param['module']) ? '' : (DIRECTORY_SEPARATOR.$param['module']));
            $this->createDirectory($dirPath);
            $path = $dirPath.DIRECTORY_SEPARATOR."README.md";
            file_put_contents($path,$contents);
            if(!file_exists($path)) $path = '';
        }
        unset($param);
        return $path;
    }

    /**
     * @name 推送远程服务器
     * @param array $param 参数
     * $param['title']      string  标题
     * $param['content']    string  标题
     * $param['cat']        string  标题
     * $param['cat']        string  标题
     * $param['cat']        string  标题
     * @return array [
     *   'error_code'=>0,
     *   'data'=>[
     *      'page_id'=>"1",
     *      'author_uid'=>null,
     *      'author_username'=>"from_api",
     *      'item_id'=>"1",
     *      'cat_id'=>"1",
     *      'page_title'=>"文档标题",
     *      'page_comments'=>"",
     *      'page_content'=>"文档内容",
     *      's_number'=>"99",
     *      'addtime'=>"1513954913",
     *   ]
     * ]
     */
    private function push($param)
    {
        $url = $this->apiDomain.'/server/index.php?s=/api/item/updateByApi';
        $data['api_key']      = $this->apiKey;
        $data['api_token']    = $this->apiToken;
        $data['page_title']   = $param['title'];
        $data['page_content'] = $param['content'];
        $data['cat_name']     = $param['catName'];
        $data['cat_name_sub'] = $param['catNameSub']??'';
        $data['s_number']     = $param['sNumber']??'99';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11");
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res,true);
    }


    /**
     * @name 创建文件夹
     * @param string $path 路径
     * @param int $mode 权限模式
     * @param bool $recursive 递归选项
     * @return bool
     * @throws \Exception
     */
    private function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            static::createDirectory($parentDir, $mode, true);
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {
                throw new \Exception("创建文件夹失败 \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \Exception("切换文件夹权限失败 \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

}