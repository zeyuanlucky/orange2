<?php
namespace Plant\Peach;

use Monolog\Handler\IFTTTHandler;
use function Couchbase\defaultEncoder;

class Segment{

    private $__AppCName;

    private $__AppName;

    protected $_container;

    protected $_config;

    protected $_appConfig;

    public function work(){
        $this->_stepOneCheckInput();

        $this->_stepTwoLoadFunctions();

        $this->_stepThreeDefinded();

        $this->_stepFourCheckFrameworkValue();

        $this->_stepFiveGetAppName();

        $this->_stepSexLoadConfig();

        $this->_stepSevenLoadContainer();

        $this->_stepEightEnvSet();

        $this->_stepNineLoadRoute();
    }

    // 第一步-检测输入
    protected function _stepOneCheckInput(){
        // 删除全局变量
        if (ini_get('register_globals')) {
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            foreach ($array as $value) {
                foreach ($GLOBALS[$value] as $key => $var) {
                    if ($var === $GLOBALS[$key]) {
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }

    // 第二步-载入全局函数
    protected function _stepTwoLoadFunctions(){
        require (APP_PATH.DIRECTORY_SEPARATOR.'vendor'.
            DIRECTORY_SEPARATOR.'plant'.
            DIRECTORY_SEPARATOR.'peach'.
            DIRECTORY_SEPARATOR.'src'.
            DIRECTORY_SEPARATOR.'Functions.php'
        );
    }

    // 第三步-定义常量
    protected function _stepThreeDefinded(){
        // 定义框架常量
        define('FRAMEWORK_PATH',__DIR__);

        // 定义框架名称
        define('FRAMEWORK_NAME','Peach');

        // 内存使用情况
        define('MEMORY',memory_get_usage());
    }

    // 第四步-检测环境是否满足框架需求
    protected function _stepFourCheckFrameworkValue(){
        if (!version_compare(PHP_VERSION,'7.0.0')){
            speak([
                'msg'=>'php版本应该大于7.0.0，请配置！',
                'result'=>0,
            ]);
        }
    }

    // 第五步-获取应用名称
    protected function _stepFiveGetAppName(){
        $pateUrl = getPageurl();
        $parseUrlArr = parse_url($pateUrl);

        if (file_exists(APP_PATH.'/config/config.php')){
            $appConfigs = include (APP_PATH.'/config/config.php');
            if (isset($appConfigs['app'])){
                $this->_appConfig = $appConfig = $appConfigs['app'];
            }else{
                speak([
                    'msg'=>'请配置 config/config.php 中app配置！',
                    'result'=>0,
                ]);
            }
        }else{
            speak([
                'msg'=>'请配置 config/config.php 中app配置！',
                'result'=>0,
            ]);
        }

        $appName = 'Home';
        $appCname = 'pc';
        if(isset($parseUrlArr['path'])){
            $processUrl = trim($parseUrlArr['path'],'/');
            if(strpos($processUrl,'/')!==false){
                $processUrl = explode('/',$processUrl);
                if(isset($processUrl[0])){
                    $appName = trim($processUrl[0]);
                    if (isset($appConfig[$appName])){
                        $appCname = $appConfig[$appName];
                    }else{
                        $appCname = 'pc';
                        $appName = 'home';
                    }
                }else{
                    $appCname = 'pc';
                    $appName = 'home';
                }
            }else{
                if($processUrl!=''){
                    $appName = trim($processUrl);
                    if (strpos($processUrl,'/')==false&&strpos($processUrl,'.')){
                        $appName = 'home';
                    }else{
                        $appName = $processUrl;
                        if (isset($appConfig[$appName])){
                            $appCname = $appConfig[$appName];
                        }else{
                            $appName = 'home';
                        }
                    }
                }else{
                    $appName = 'home';
                }
            }
        }

        $this->__AppName = ucfirst($appName);
        $this->__AppCName = ucfirst($appCname);

    }

    // 第六步-载入配置
    protected function _stepSexLoadConfig(){
        $path = APP_PATH . DIRECTORY_SEPARATOR .
            'app' . DIRECTORY_SEPARATOR .
            'src' . DIRECTORY_SEPARATOR .
            $this->__AppCName . DIRECTORY_SEPARATOR.
            $this->__AppName . DIRECTORY_SEPARATOR.
            'Config';

        $this->_config = HelperLoadConfigs($path);

        if (isset($this->_config['mysqli'])){
            define('MYSQLI',$this->_config['mysqli']);
        }
        $this->_config['app'] = $this->_appConfig;
    }

    // 第七步-载入容器
    protected function _stepSevenLoadContainer(){
        $path = APP_PATH . DIRECTORY_SEPARATOR .
            'app' . DIRECTORY_SEPARATOR .
            'src' . DIRECTORY_SEPARATOR .
            $this->__AppCName . DIRECTORY_SEPARATOR .
            'Container.php';

        $this->_container = include $path;
    }

    // 第八步-环境设置
    protected function _stepEightEnvSet(){
        // 此处可加缓存
        if (file_exists(APP_PATH.'/env.txt')){
            $txt = file_get_contents(APP_PATH.'/env.txt');
            if ($txt){
                $txt2 = explode("\r\n", $txt);
                if (count($txt2)==0){
                    speak([
                        'msg'=>'env.txt 配置出错，请联系开发人员！',
                        'result'=>0,
                    ]);
                }

                $newArr = [];
                foreach ($txt2 as $val){
                    $mykey = explode('=',$val);
                    if (isset($mykey[1])){
                        $newArr[$mykey[0]] = $mykey[1];
                    }
                }

                // 定义框架版本号
                if(isset($newArr['version'])){
                    define('FRAMEWORK_VERSION',$newArr['version']);
                }else{
                    speak([
                        'msg'=>'未知框架版本号，请联系开发者！',
                        'result'=>0,
                    ]);
                }

                // 设置时区
                if(isset($newArr['time'])){
                    date_default_timezone_set($newArr['time']);
                }else{
                    speak([
                        'msg'=>'未知框架时区，请联系开发者！',
                        'result'=>0,
                    ]);
                }

                // 错误提示
                if(isset($newArr['display_errors'])){
                    //ini_set("display_errors", $newArr['display_errors']);//打开错误提示
                }

                // 错误级别
                if(isset($newArr['error_reporting'])){
                    //ini_set("error_reporting", "$newArr['error_reporting']");//打开错误提示
                }
            }else{
                speak([
                    'msg'=>'env.txt 配置出错，请联系开发人员！',
                    'result'=>0,
                ]);
            }
        }else{
            speak([
                'msg'=>'env.txt 配置出错，请联系开发人员！',
                'result'=>0,
            ]);
        }
    }

    // 载入路由
    protected function _stepNineLoadRoute(){

        if(!isset($this->_config['route'])){
            speak([
                'msg'=>'app 注册表缺失！',
                'result'=>0,
            ]);
        }

        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = unserialize($this->_config['route']['route'])->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                header('Location:/404/index.html');
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                echo 'METHOD_NOT_ALLOWED';
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];


                $className = '';
                $functionName = '';
                if(strpos($handler,'@')!==false){
                    $processHandlerArr = explode('@',$handler);
                    if (isset($processHandlerArr[0]) && isset($processHandlerArr[1])){
                        $processHandlerArr[1] = preg_replace('/\s+/', '', $processHandlerArr[1]);
                        if ($processHandlerArr[1]==''){
                            $processHandlerArr[1] = 'home';
                        }
                        $functionName = trim($processHandlerArr[1]);
                        $className = $processHandlerArr[0];
                    }
                }else{
                    $functionName = 'home';
                    $className = $handler;
                }

                // 绝对路径和相对路径处理
                if(strpos($className,'\\')===0){
                    // 绝对路径类
                    $appAbsolutePath = $className;
                }else{
                    $appAbsolutePath = '\\App\\'.ucfirst($this->__AppCName).'\\'.ucfirst($this->__AppName).'\\Controller\\'.$className.'Controller';
                }

                //echo $appAbsolutePath;exit;
                // 类是否存在
                if(!class_exists($appAbsolutePath)){
                    throw new \Exception("$appAbsolutePath 不存在，清创建！");
                }

                $parseUrlArr = parse_url(getPageurl());

                // 空值判断
                if(!isset($parseUrlArr['query'])){
                    $parseUrlArr['query'] = '';
                }

                $theme = 'default';
                if (isset($this->appConfig['conf']['app']['theme_name'])){
                    $theme = $this->appConfig['conf']['app']['theme_name'];
                }

                $appRegisterMap = [
                    'action'=>$functionName,
                    'class'=>$className,
                    'currnt_app_name'=>$this->__AppCName,
                    'container'=>$this->_container,
                    'config'=> $this->_config,
                    'framework_version'=>FRAMEWORK_VERSION,

                    'base_domain'=>$parseUrlArr['host'],
                    'base_scheme'=>$parseUrlArr['scheme'],
                    'base_path'=>$parseUrlArr['path'],
                    'base_query'=>$parseUrlArr['query'],
                    'current_module_alias'=>$this->__AppName,
                    'current_page_url'=>getPageurl(),
                    'theme'=>$theme,
                    'app_path'=>str_replace('/',DIRECTORY_SEPARATOR,APP_PATH),
                    'args'=>$vars,
                ];

                $this->__currentClassName = $className;
                $this->__currentFunctionName = $functionName;

                $instance = new $appAbsolutePath($appRegisterMap);

                // 清空容器
                $this->_clearContainer();

                // 配置实例清空
                $this->__configInstance = null;

                // 销毁变量
                $this->appConfig['conf'] = null;

                // 方法是否存在
                if(!method_exists($instance,$functionName)){
                    throw new \Exception("$appAbsolutePath 方法：$functionName 不存在，清创建！");
                }

                $this->__routeBefore();

                $instance->$functionName($vars);

                $this->__routeAfter();
                // ... call $handler with $vars
                break;
        }

    }

    /**
     * @return void
     * 路由前置操作
     */
    private function __routeBefore(){
        $file = 'RouteBefore.php';
        if(isset($this->config['conf']['before']) && !empty($this->config['conf']['before'])){
            $file = $this->appConfig['conf']['before'];
        }

        // 绝对路径
        $path = APP_PATH . DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR .'src'.DIRECTORY_SEPARATOR. ucfirst($this->__AppCName) .DIRECTORY_SEPARATOR.ucfirst($this->__AppName).DIRECTORY_SEPARATOR.$file;
        if (is_file($path)){
            include $path;
        }
    }

    private function __routeAfter(){
        $file = 'RouteAfter.php';
        if(isset($this->config['conf']['after']) && !empty($this->config['conf']['after'])){
            $file = $this->appConfig['conf']['after'];
        }

        // 绝对路径
        $path = APP_PATH . DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR .'src'.DIRECTORY_SEPARATOR. ucfirst($this->__AppCName) .DIRECTORY_SEPARATOR.ucfirst($this->__AppName).DIRECTORY_SEPARATOR.$file;
        if (is_file($path)){
            include $path;
        }
    }

    protected function _clearContainer(){
        $this->_container = null;
    }
}

