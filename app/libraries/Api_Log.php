<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: philips
 * Date: 17/2/16
 * Time: 上午11:48
 */

class Api_Log
{


    protected $_log_path;

    protected $_err_path;

    /**
     * File permissions
     *
     * @var    int
     */
    protected $_file_permissions = 0644;


    /**
     * Array of threshold levels to log
     *
     * @var array
     */
    protected $_threshold_array = array();

    /**
     * Format of timestamp for log files
     *
     * @var string
     */
    protected $_date_fmt = 'Y-m-d H:i:s';

    /**
     * Filename extension
     *
     * @var    string
     */
    protected $_file_ext;

    /**
     * Whether or not the logger can write to the log files
     *
     * @var bool
     */
    protected $_enabled = TRUE;

    protected $_levels = array('ERROR','INFO');

    /**
     * Api_Log constructor.
     */
    public function __construct()
    {
        $config =& get_config();

        $this->_log_path = ($config['log_path'] !== '') ? $config['log_path'] : APPPATH . 'logs/';
        $this->_file_ext = (isset($config['log_file_extension']) && $config['log_file_extension'] !== '')
            ? ltrim($config['log_file_extension'], '.') : 'log';

        file_exists($this->_log_path) OR mkdir($this->_log_path, 0755, TRUE);

        if (!is_dir($this->_log_path) OR !is_really_writable($this->_log_path)) {
            $this->_enabled = FALSE;
        }

        $this->_err_path = $this->_log_path . '/error_logs/' ;
        file_exists($this->_err_path) OR mkdir($this->_err_path, 0755, TRUE);


        if (!empty($config['log_file_permissions']) && is_int($config['log_file_permissions'])) {
            $this->_file_permissions = $config['log_file_permissions'];
        }
    }

    // --------------------------------------------------------------------

    /**
     *  * Write Log File
     * Generally this function will be called using the global log_message() function
     * @param $method
     * @param $level
     * @param $content
     * @return bool
     */
    public function write_log($method, $content,  $level='INFO')
    {
        if(!$method)
        {
            return FALSE;
        }

        $level = strtoupper($level);
        if ( ! in_array($level,$this->_levels))
        {
            return FALSE;
        }

        $method_name= str_replace('.','_',$method);
        $full_log_path = $this->_log_path . '/' . $method_name . '/';
        file_exists($full_log_path) OR mkdir($full_log_path, 0755, TRUE);

        $content = '['.date("Y/m/d H:i:s",time()).'] '.$content;


        if ($this->_enabled === FALSE) {
            return FALSE;
        }

        if ($level == 'INFO') {
            $filepath = $full_log_path . $method_name  . '-' . date('Y-m-d') . '.' . $this->_file_ext;
        } else {
            $filepath = $this->_err_path . $method_name . '_err-'  . date('Y-m-d') . '.' . $this->_file_ext;
        }

        $message = '';

        if (!file_exists($filepath)) {
            $newfile = TRUE;
            // Only add protection to php files
            if ($this->_file_ext === 'php') {
                $message .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
            }
        }

        if (!$fp = @fopen($filepath, 'a+')) {
            return FALSE;
        }

        flock($fp, LOCK_EX);

        $content = $content . "\n";
        for ($written = 0, $length = strlen($content); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($content, $written))) === FALSE) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if (isset($newfile) && $newfile === TRUE) {
            chmod($filepath, $this->_file_permissions);
        }

        return is_int($result);
    }
}
