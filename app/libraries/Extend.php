<?php
/**
 * Created by PhpStorm.
 * User: philips
 * Date: 16/12/7
 * Time: 下午5:50
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Extend
{
    function __construct()
    {
        $this->CI =& get_instance();

    }

    /** 验证验签的合法性
     * @param $data
     * @param $token
     * @return bool
     */
    public function validateToken($token,$data=null)
    {
//        $signStr = '';
//        foreach($data as $k=>$v)
//        {
//            $signStr .= $v;
//        }
//        $signStr .= API_KEY;
//        $validator = md5($signStr);
//
//        if(strtoupper($token) === strtoupper($validator))
//        {
//            return true;
//        }

        $config =& get_config();
        if(strtoupper($token) === strtoupper($config['e3_token']))
        {
            return true;
        }
        return false;
    }

    /**
     * 创建magento接口的验签
     * @param $params
     * @return string
     */
    private function _createSign($params)
    {
        ksort($params);
        reset($params);
        $params['secret'] = MAGENTO_KEY;

        $arg = "";
        while (list($index, $val) = each($params)) {
            if($val == '') continue;
            $val = (is_array($val)) ? json_encode($val) : $val;
            $arg .= $index . "=" . $val . "&";
        }

        $str = substr($arg, 0, count($arg) - 2);

        return md5($str);
    }

    /**
     * get 请求方法
     * @param $url
     * @param $param
     * @param int $timeout
     * @return array
     */
    public function curl_post($url,$param,$timeout=6)
    {
        $result = array();

        if((!$param) || (!$url))
        {
            return $result;
        }

        $sign = $this->_createSign($param);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,$timeout);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("SIGNATURE: $sign"));
        //执行并获取HTML文档内容
        $result = json_decode(curl_exec($ch),true);
        curl_close($ch);
        return $result;
    }
}