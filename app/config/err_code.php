<?php
/**
 * Created by PhpStorm.
 * User: philips
 * Date: 17/1/19
 * Time: 下午3:52
 */

$ERROR_CODE = array(
    ERR_SIGNATURE_INVALID           => '签名不合法',
    ERR_PARAMETER_MISSING           => '缺少参数',
    ERR_PARAMETER_INCORRECT         => '参数错误',
    ERR_PARAMETER_FORMAT_INCORRECT  => '参数格式错误',
    ERR_RETURN_INFO_EMPTY           => '无记录',
    ERR_METHOD_INCORRECT            => '方法名不合法',
    ERR_UNEXPECTED                  => '未知错误',
    ERR_MAGENTO_TIMEOUT             => 'Magento请求超时',
    ERR_MAGENTO_REQUEST             => 'Magento请求失败',
);
$config['error_code'] = $ERROR_CODE;