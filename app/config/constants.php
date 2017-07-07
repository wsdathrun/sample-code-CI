<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

// API key
define('MAGENTO_KEY', '90upW884ZQn5H0x4qAb2A7DN3AYWW91y');
define('RETURN_SUCCESS', 0);

// page info
define('DEFAULT_PAGE_NUM', 1);
define('DEFAULT_PAGE_SIZE', 40);

// global error code
define('ERR_SIGNATURE_INVALID', 102);
define('ERR_PARAMETER_MISSING', 103);
define('ERR_PARAMETER_INCORRECT', 104);
define('ERR_PARAMETER_FORMAT_INCORRECT', 105);
define('ERR_RETURN_INFO_EMPTY', 106);
define('ERR_METHOD_INCORRECT', 107);
define('ERR_MAGENTO_TIMEOUT', 201);
define('ERR_MAGENTO_REQUEST', 202);
define('ERR_UNEXPECTED',    999);

// success message
define('MSG_SUCCESS', 'success');

// url of item
define('ITEM_URL','catalog/product/view/id/');

// method list
define('METHOD_ITEM_LIST','item.list.items');
define('METHOD_ITEM_DETAIL','item.get.item.by.sn');
define('METHOD_ITEM_SET_INVENTORY','item.set.inventory');
define('METHOD_ITEM_SET_SALE','item.set.on.sell');
define('METHOD_ORDER_LIST','order.list.order');
define('METHOD_ORDER_DETAIL','order.trade.get');
define('METHOD_ORDER_DELIVER','order.delivery.feedback');
define('METHOD_ORDER_RETURN_APPLY','order.list.return.apply');
define('METHOD_ORDER_RETURN_UPDATE','order.returns.order');
define('METHOD_ORDER_STATUS_UPDATE','order.change.status');

// shipping company code
define('SHIPPING_COMPANY_YZGN', 'youzhengguonei');
define('SHIPPING_COMPANY_EMS', 'ems');
define('SHIPPING_COMPANY_SHUNFENG', 'shunfeng');
define('SHIPPING_COMPANY_SHENTONG', 'shentong');
define('SHIPPING_COMPANY_YUANTONG', 'yuantong');
define('SHIPPING_COMPANY_ZHONGTONG', 'zhongtong');
define('SHIPPING_COMPANY_HUITONG', 'huitongkuaidi');
define('SHIPPING_COMPANY_YUNDA', 'yunda');
define('SHIPPING_COMPANY_ZHAIJISONG', 'zhaijisong');
define('SHIPPING_COMPANY_TIANTIAN', 'tiantian');
define('SHIPPING_COMPANY_DEBANG', 'debangwuliu');
define('SHIPPING_COMPANY_GUOTONG', 'guotongkuaidi');
define('SHIPPING_COMPANY_SUER', 'suer');
define('SHIPPING_COMPANY_ZHONGTIE', 'zhongtiewuliu');
define('SHIPPING_COMPANY_GZDN', 'ganzhongnengda');
define('SHIPPING_COMPANY_YOUSU', 'youshuwuliu');
define('SHIPPING_COMPANY_QUANFENG', 'quanfengkuaidi');
define('SHIPPING_COMPANY_JINGDONG', 'jd');
define('SHIPPING_COMPANY_HEIMAO', 'zhaijibian');
define('SHIPPING_COMPANY_RUFENGDA', 'rufengda');
define('SHIPPING_COMPANY_QUANYI', 'quanyikuaidi');
define('SHIPPING_COMPANY_YAFENG', 'yafengsudi');
define('SHIPPING_COMPANY_QUANRITONG', 'quanritongkuaidi');
define('SHIPPING_COMPANY_XINBANG', 'xinbangwuliu');
define('SHIPPING_COMPANY_LONGBANG', 'longbanwuliu');
define('SHIPPING_COMPANY_LIANHAOTONG', 'lianhaowuliu');
define('SHIPPING_COMPANY_TIANDIHUAYU', 'tiandihuayu');
define('SHIPPING_COMPANY_CHANGYU', 'changyuwuliu');
define('SHIPPING_COMPANY_XINFENG', 'xinfengwuliu');
define('SHIPPING_COMPANY_KUAIJIE', 'kuaijiesudi');
define('SHIPPING_COMPANY_JIAJI', 'jiajiwuliu');
define('SHIPPING_COMPANY_LIANBANG', 'lianbangkuaidi');
define('SHIPPING_COMPANY_CHENGSHI100', 'city100');
define('SHIPPING_COMPANY_HUIQIANG', 'huiqiangkuaidi');
define('SHIPPING_COMPANY_FANYU', 'fanyukuaidi');
define('SHIPPING_COMPANY_FEIYUAN', 'feiyuanvipshop');
define('SHIPPING_COMPANY_BAISHI', 'baishiwuliu');
define('SHIPPING_COMPANY_CDDJ', 'dongjun');

