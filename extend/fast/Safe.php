<?php

namespace fast;

use think\Config;

/**
 * 通用的树型类
 * @author XiaoYao <476552238li@gmail.com>
 */
class Safe
{
	protected static $instance;
    //默认配置
    //protected $config = [];
    //public $options = [];

    protected $keys,$iv;
 
    // 初始化
    function __construct(){
		$this->keys='e9c8e878ee8e2658';
		$this->iv='d89fb057f6d4f03g';
    }
 
    // 编码
    function encode($nums){
		$strEncrypted = openssl_encrypt($nums,"AES-128-CBC", $this->keys,OPENSSL_RAW_DATA, $this->iv);
        return base64_encode($strEncrypted);
    }
    //解码 
    function decode($strEncryptCode){
		$strEncrypted = base64_decode($strEncryptCode);
        return openssl_decrypt($strEncrypted,"AES-128-CBC",$this->keys,OPENSSL_RAW_DATA,$this->iv);
	}

    public static function instance()
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static();
        }

        return self::$instance;
    }

}
