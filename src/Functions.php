<?php
    namespace Plant\Peach;

    function speak($val){
        if (isset($val['code'])){
            $val['status'] = $val['code'];
        }
        if (isset($val['msg'])){
            $val['message'] = $val['msg'];
        }
        if(isAjax()){
            echo json_encode($val);exit;
        }else{
            print_r($val);
        }
    }

    function getPageurl(){
        return getHttpurl();
    }

    //feiniaomy.com
    function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    function getDomain($noport = false) {
        if (isHttps()) {
            $url = 'https://' . $_SERVER['HTTP_HOST'];
        } else {
            $url = 'http://' . $_SERVER['HTTP_HOST'];
        }
        if ($noport) {
            $url = str_replace(':' . $_SERVER['SERVER_PORT'], '', $url);
        }
        return $url;
    }

    function getHttpurl($noport = false) {
        if (isHttps()) {
            $url = 'https://' . $_SERVER['HTTP_HOST'];
        } else {
            $url = 'http://' . $_SERVER['HTTP_HOST'];
        }
        if ($noport) {
            $url = str_replace(':' . $_SERVER['SERVER_PORT'], '', $url);
        }
        return $url . $_SERVER["REQUEST_URI"];
    }

    function isHttps() {
        if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')) {
            return true;
        } elseif (isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) == 'https') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https') {
            return true;
        } else {
            return false;
        }
    }


    function createGuid($namespace = '') {
        static $guid = '';

        $uid = uniqid("", true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['PHP_SELF'];
        $data .= $_SERVER['REMOTE_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $data .= generateGuid($namespace);
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = '{' .
            substr($hash, 0, 8) .
            '-' .
            substr($hash, 8, 4) .
            '-' .
            substr($hash, 12, 4) .
            '-' .
            substr($hash, 16, 4) .
            '-' .
            substr($hash, 20, 12) .
            '}';
        return $guid;
    }

    /**
     * @param $prefix
     * @return string
     * 生成唯一id
     */
    function generateGuid($prefix=''){
        //假设一个机器id
        $machineId = mt_rand(100000,999999);

        //41bit timestamp(毫秒)
        $time = floor(microtime(true) * 1000);

        //0bit 未使用
        $suffix = 0;

        //datacenterId  添加数据的时间
        $base = decbin(pow(2,40) - 1 + $time);

        //workerId  机器ID
        $machineid = decbin(pow(2,9) - 1 + $machineId);

        //毫秒类的计数
        $random = mt_rand(1, pow(2,11)-1);

        $random = decbin(pow(2,11)-1 + $random);
        //拼装所有数据
        $base64 = $suffix.$base.$machineid.$random;
        //将二进制转换int
        $base64 = bindec($base64);

        $id = sprintf('%.0f', $base64);

        return $prefix.$id;
    }

    /**
     * @param $path
     * @return array
     * 载入某个目录下所有配置文件
     */
    function HelperLoadConfigs($path){
        if(!is_dir($path)){
            return [];
        }
        $arr = array();
        $data = scandir($path);
        $configs = [];
        $settingConfigs = [];

        foreach ($data as $value){
            if($value != '.' && $value != '..'){
                $arr[] = $value;
                if(is_dir($path . DIRECTORY_SEPARATOR . $value)){
                    $folder_list = [];
                    helper_find_files($path . DIRECTORY_SEPARATOR . $value , $folder_list);

                    foreach($folder_list as $key=>$val){
                        foreach($val as $vval){
                            $settingConfigs = '';
                            if(strpos($vval,'.php')){
                                $keyName = substr($vval,0,strpos($vval,'.'));
                                $settingConfigs = include($key . DIRECTORY_SEPARATOR . $vval);
                                $processName = $key . DIRECTORY_SEPARATOR . $vval;
                                $targetNameArr = explode('Config',$processName);
                                if(isset($targetNameArr[1])){
                                    $targetNameArr = str_replace(DIRECTORY_SEPARATOR,'/',$targetNameArr[1]);
                                    $processTargetNameArr = explode('.php',$targetNameArr);
                                    $targetNem = trim($processTargetNameArr[0],'/');
                                }else{
                                    $targetNem = $vval;
                                }

                                if(is_array($settingConfigs)){
                                    $configs[$targetNem] = $settingConfigs;
                                    //$configs[$targetNem]['file_md5'] = md5_file($key . DIRECTORY_SEPARATOR . $vval);
                                }
                            }
                        }
                    }
                }else{
                    if(is_file($path . DIRECTORY_SEPARATOR . $value)){
                        if(strpos($path . DIRECTORY_SEPARATOR . $value,'.php')){
                            $processName = $path . DIRECTORY_SEPARATOR . $value;
                            $targetNameArr = explode('Config',$processName);
                            if(isset($targetNameArr[1])){
                                $targetNameArr = str_replace(DIRECTORY_SEPARATOR,'/',$targetNameArr[1]);
                                $processTargetNameArr = explode('.php',$targetNameArr);
                                $targetNem = trim($processTargetNameArr[0],'/');
                            }else{
                                $targetNem = $value;
                            }

                            $settingConfigs = include($path . DIRECTORY_SEPARATOR . $value);
                            $configs[$targetNem] = $settingConfigs;
                            //$configs[$targetNem]['file_md5'] = md5_file($path . DIRECTORY_SEPARATOR . $value);
                        }
                    }

                }
            }
        }

        return $configs;
    }