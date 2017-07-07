<?php
/**
 * Created by PhpStorm.
 * User: Eathan
 * Date: 17/2/15
 * Time: 下午5:39
 */
defined('BASEPATH') OR exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';
class Ntalker extends REST_Controller
{

    // 定义订单状态常量
    const ORDER_STATUS_PROCESSING      = 1;
    const ORDER_STATUS_READY_SHIPMENT  = 1;
    const ORDER_STATUS_COMPLETE        = 1;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Utility_model', 'utility');
        $this->load->model('Order_model', 'order');
        $this->load->model('Item_model','item');
        $this->load->model('Ntalker_model','ntalker');
    }

    public function index_post()
    {
        header("Access-Control-Allow-Origin: *");
        $method = $this->post('method');
        $apiToken = $this->post('api_token');

        $this->_log->write_log($method,"started \n" . json_encode($_POST));
        if(!$this->extend->validateToken($apiToken)) {
            $this->returnError(ERR_SIGNATURE_INVALID,$this->config->item(ERR_SIGNATURE_INVALID,'error_code'));
            return ;
        }

        switch ($method) {
            case METHOD_ITEM_LIST:
                $this->doItemList();
                break;
            case METHOD_ITEM_DETAIL:
                $this->doItemDetail();
                break;
            case METHOD_ITEM_SET_INVENTORY:
                $this->doItemSetInventory();
                break;
            case METHOD_ITEM_SET_SALE:
                break;
            case METHOD_ORDER_LIST:
                $this->doOrderList();
                break;
            case METHOD_ORDER_DETAIL:
                $this->doOrderDetail();
                break;
            case METHOD_ORDER_DELIVER:
                $this->doOrderDelivery();
                break;
            case METHOD_ORDER_RETURN_APPLY:
                $this->doOrderReturnApply();
                break;
            case METHOD_ORDER_RETURN_UPDATE:
                $this->doOrderReturnUpdate();
                break;
            case METHOD_ORDER_STATUS_UPDATE:
                $this->doOrderUpdateStatus();
                break;
            default:
                $this->_log->write_log('method_incorrect', $method);
                $this->returnError(ERR_METHOD_INCORRECT,$this->config->item(ERR_METHOD_INCORRECT, 'error_code'));
                break;
        }
    }

    /**
     * 订单列表接口
     */
    public function doOrderList(){
        header("Access-Control-Allow-Origin: *");
        $inputData = array(
            'uid'      => $this->post('uid')
        );

        // 验证输入参数
//        if (!$this->_checkListInputParam($inputData))
//        {
//            return;
//        }

        // 处理订单列表
        if($data = $this->_processOrderList($inputData['uid']))
        {
            // 返回
            $this->returnSuccess($data);
        }else{
            $this->returnError(ERR_UNEXPECTED,$this->config->item(ERR_UNEXPECTED, 'error_code'));;
        }
    }

    /** 验证入参
     * @param $inputData
     * @return bool
     */
    protected function _checkListInputParam($inputData){
        // 页数参数
        if(!is_numeric($inputData['page']))
        {
            $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT,$this->config->item(ERR_PARAMETER_FORMAT_INCORRECT,'error_code').':page类型');
            return false;
        }
        else if((
                (int)$inputData['page'] != $inputData['page'])
            || ((int)$inputData['page'] <= 0)
        )
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').':page必须为正整数');
            return false;
        }

        // 单页尺寸参数
        if(!is_numeric($inputData['pageSize']))
        {
            $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT,$this->config->item(ERR_PARAMETER_FORMAT_INCORRECT,'error_code').':pageSize类型');
            return false;
        }
        else if((
                (int)$inputData['pageSize'] != $inputData['pageSize'])
            || ((int)$inputData['pageSize'] <= 0)
        )
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').':pageSize必须为正整数');
            return false;
        }

        return true;
    }

    /**
     * 拉取订单列表
     * @param $page
     * @param $limitNum
     * @param $limitStart
     * @param $lastUpdated
     * @return array|bool
     */
    protected function _processOrderList($customerId){
        try
        {
            // 用户基本信息 + 订单信息
            $result = $this->ntalker->getCustomerInfo($customerId);

            // 取满足条件的订单总数
            $resultCount = sizeof($result);

            $resultDetail = array();
            $reformatArray = array(
                'customer_name'        => $result['name'],
                'customer_phone'       => $result['phone'],
                'customer_grade'       => $result['chinese_grade'],
                'customer_order_count' => $resultCount,
            );


            $pageInfo = array(
                'current_page' => $page,
                'pages'        => ceil($resultCount[0]['total']/$limitNum),
                'page_size'    => $limitNum ? $limitNum : DEFAULT_PAGE_SIZE,
                'total'        => $resultCount[0]['total'],
            );
            $data = array(
                'page_items'  => $resultDetail,
                'page_info'   => $pageInfo,
            );
            return $data;
        }
        catch(Exception $e)
        {
            $this->returnError(EXIT_DATABASE,$this->config->item(EXIT_DATABASE,'error_code').'订单列表查询失败'.$e->getMessage());
            return false;
        }
    }
}