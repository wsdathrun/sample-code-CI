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
class Openapi extends REST_Controller
{

    // 定义订单状态常量
    const ORDER_STATUS_PROCESSING      = 1;
    const ORDER_STATUS_READY_SHIPMENT  = 1;
    const ORDER_STATUS_COMPLETE        = 1;

    // 定义错误返回码常量
    const ORDER_NOT_FOUND                  = 512;
    const ORDER_RESULT_IS_EMPTY            = 513;
    const ILLEGAL_SHIPPING_ORDER           = 701;
    const INCORRECT_WAREHOUSE_COMPANY_CODE = 702;
    const ORDER_ALREADY_SHIPPED            = 703;
    const SHIPPING_INFO_UPDATE_FAILED      = 704;
    const ORDER_STATUS_INCORRECT           = 705;

    // 定义支付编码常量
    const PAY_CODE_ALIPAY_PAYMENT       = 'alipay';
    const PAY_CODE_GLOBALALIPAY_PAYMENT = 'alipay_global';
    const PAY_CODE_WECHAT_PAYMENT       = 'weixin';
    const PAY_CODE_FREE                 = 'xsptzf';

    // 定义退货单状态常量
    const RETURN_ORDER_CLOSED           = 'closed';
    const RETURN_ORDER_PENDING          = 'pending';
    const RETURN_ORDER_PENDING_RECEIVED = 'pending_received';
    const RETURN_ORDER_COMPLETE         = 'complete';
    const RETURN_ORDER_DENIED           = 'denied';

    // 退换货原因
    const RETURN_REASON_ONE   = 1;
    const RETURN_REASON_TWO   = 2;
    const RETURN_REASON_THREE = 3;
    const RETURN_REASON_FOUR  = 4;
    const RETURN_REASON_FIVE  = 5;
    const RETURN_REASON_SIX   = 6;
    const RETURN_REASON_SEVEN = 7;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Utility_model', 'utility');
        $this->load->model('Order_model', 'order');
        $this->load->model('Item_model','item');
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

        $inputData = array(
            'page'      => $this->post('page') ? $this->post('page') : DEFAULT_PAGE_NUM,
            'pageSize'  => $this->post('page_size')? $this->post('page_size') : DEFAULT_PAGE_SIZE,
            'order_status' => 0,
            'pay_status'   => 2,
            'last_updated' => $this->post('last_updated'),
        );

        $lastUpdated = $inputData['last_updated'];
        if (!isset($lastUpdated) || is_null($lastUpdated) || $lastUpdated == ''){
            $lastUpdated = '2016-01-01';
        }
        $limitStart = ($inputData['page'] - 1) * $inputData['pageSize'] ;

        // 验证输入参数
        if (!$this->_checkListInputParam($inputData))
        {
            return;
        }

        // 验证日期参数
        if (!$this->_checkOrderListParam($lastUpdated)) {
            return;
        }

        // 处理订单列表
        if($data = $this->_processOrderList($inputData['page'], $inputData['pageSize'], $limitStart, $lastUpdated))
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
     * 验证最后更新时间参数是否符合时间格式
     * @param $lastUpdated
     * @return bool
     */
    protected function _checkOrderListParam($lastUpdated){
        if ($lastUpdated != '') {
            $isDate = strtotime($lastUpdated) ? strtotime($lastUpdated) : false;
            if ($isDate === false) {
                $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT, $this->config->item(ERR_PARAMETER_FORMAT_INCORRECT, 'error_code') . ':最后更新时间格式错误');
                return false;
            }
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
    protected function _processOrderList($page, $limitNum, $limitStart, $lastUpdated){
        try
        {
            // 取满足条件的订单总数
            $resultCount = $this->order->getOrderListCount($lastUpdated);

            // 非直邮订单 + 成团订单
            $result = $this->order->getOrderList($lastUpdated,$limitNum,$limitStart);

            $resultDetail = array();
            foreach ($result as $k=>$v){
                $orderLevelInfo = $this->_processOrderInfo($v['increment_id']);

                $reformatArray = array(
                    'order_sn'        => $orderLevelInfo[0]['order_sn'],
                    'user_id'         => $orderLevelInfo[0]['user_id'],
                    'order_status'    => $orderLevelInfo[0]['order_status'],
                    'shipping_status' => 0,
                    'pay_status'      => 2,
                    'trans_type'      => 1,
                    'add_time'        => $orderLevelInfo[0]['add_time'],
                    'pay_time'        => $orderLevelInfo[0]['pay_time'],
                    'pay_code'        => $orderLevelInfo[0]['pay_code'],
                    'shipping_code'   => '',
                    'pos_code'        => '',
                    'consignee'       => $orderLevelInfo[0]['consignee'],
                    'country_name'    => 'CN',
                    'province_name'   => $orderLevelInfo[0]['province_name'],
                    'city_name'       => $orderLevelInfo[0]['city_name'],
                    'district_name'   => $orderLevelInfo[0]['district_name'],
                    'address'         => $orderLevelInfo[0]['address'],
                    'zipcode'         => $orderLevelInfo[0]['zipcode'],
                    'tel'             => $orderLevelInfo[0]['tel'],
                    'mobile'          => $orderLevelInfo[0]['mobile'],
                    'email'           => $orderLevelInfo[0]['email'],
                    'best_time'       => '',
                    'postscript'      => '',
                    'to_buyer'        => '',
                    'goods_amount'    => $orderLevelInfo[0]['goods_amount'],
                    'shipping_fee'    => $orderLevelInfo[0]['shipping_fee'],
                    'total_fee'       => $orderLevelInfo[0]['total_fee'],
                    'money_paid'      => $orderLevelInfo[0]['money_paid'],
                    'order_amount'    => $orderLevelInfo[0]['order_amount'],
                    // discount => 蜜豆
                    'discount'        => $orderLevelInfo[0]['discount'],
                    'goods_count'     => $orderLevelInfo[0]['goods_count'],
                    'marks_name'      => '',
                    'inv_payee'       => '',
                    'inv_content'     => '',
                    'source'          => 'OPENSHOP',
                );
                array_push($resultDetail, $reformatArray);
            }

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

    /**
     * 订单详情接口
     */
    public function doOrderDetail()
    {
        $orderInfo = array();
        $orderNum = $this->post('oid');

        // 验证订单号
        if (!$this->_checkDetailInputParam($orderNum)) {
            return;
        }

        $orderId = $this->utility->select_row('sales_flat_order', array('entity_id', 'status'), array('increment_id' => $orderNum))->entity_id;
        // 订单信息
        if($orderInfo = $this->_processOrderInfo($orderNum)){
            $isGift    = $orderInfo[0]['is_gift'];
            $orderDtl  = $this->order->getOrderInfo($orderNum);
            $isGroupon = $orderDtl[0]['is_groupon'];
            $isPresale = $orderDtl[0]['presale_flag'];
            $subtotal  = $orderDtl[0]['subtotal'];
            $totalQty  = $orderDtl[0]['total_qty_ordered'];
            $rewardpointSpent = $orderDtl[0]['rewardpoints_spent'];
            $grandTotal = $orderDtl[0]['grand_total'];
            $shippingAmount = $orderDtl[0]['shipping_amount'];
        }else{
            return;
        }


        // 商品信息
        if ($orderDetail = $this->_processProductInfo($orderId,$orderNum,$isGift,$isGroupon,$isPresale,$subtotal,$rewardpointSpent,$totalQty,$grandTotal,$shippingAmount)) {
            $data = array(
                'basic' => $orderInfo,
                'items' => $orderDetail,
            );

            // 返回
            $this->returnSuccess($data);
        }else{
            return;
        }
    }

    /**
     * 验证订单号是否存在
     * @param $orderNum
     * @return bool
     */
    protected function _checkDetailInputParam($orderNum){
        $returnId = $this->utility->select_row('sales_flat_order','entity_id, status, warehouse',array('increment_id' => $orderNum));
        //
        if(!isset($orderNum) || is_null($orderNum))
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING,'error_code').'未指定订单号');
            return false;
        }
        else if(is_null($returnId))
        {
            $this->returnError(self::ORDER_NOT_FOUND,$this->config->item(self::ORDER_NOT_FOUND,'error_code').'订单不存在!');
            return false;
        }

        if($returnId->status != 'processing' && $returnId->status != 'ready_shipment'){
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').'订单状态错误!');
            return false;
        }

        if($returnId->warehouse == 1){
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').'韩国仓订单!');
            return false;
        }

        if(!$this->order->verifyGiftOrder($returnId->entity_id)){
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').'未成团订单!');
            return false;
        }

        return true;
    }

    /**
     * 获取订单级别详情
     * @param $orderNum
     * @return array|bool
     */
    protected function _processOrderInfo($orderNum){
            $orderInfo = $this->order->getOrderInfo($orderNum);
            $isGift = $orderInfo[0]['is_gwp'];
            $isGroupon = $orderInfo[0]['is_groupon'];
            $isPresale = $orderInfo[0]['presale_flag'];
            $resultDetail = array();
            $qtyOrdered   = 0;
            $goodsAmount  = 0;
            $prodPrice    = 0;
            $bundleGoodAmount = 0;
            foreach ($orderInfo as $k => $v)
            {
                switch ($v['method']) {
                    case 'alipay_payment':
                        $payCode = $this::PAY_CODE_ALIPAY_PAYMENT;
                        break;
                    case 'globalalipay_payment':
                        $payCode = $this::PAY_CODE_GLOBALALIPAY_PAYMENT;
                        break;
                    case 'wechat_payment':
                        $payCode = $this::PAY_CODE_WECHAT_PAYMENT;
                        break;
                    case 'free':
                        $payCode = $this::PAY_CODE_FREE;
                        break;
                }
                switch ($v['status']) {
                    case 'processing':
                        $orderStatus = $this::ORDER_STATUS_PROCESSING;
                        break;
                    case 'ready_shipment':
                        $orderStatus = $this::ORDER_STATUS_READY_SHIPMENT;
                        break;
                    case 'complete':
                        $orderStatus = $this::ORDER_STATUS_COMPLETE;
                        break;
                }
                $prodPrice = $v['goods_amount'];
                    if (!is_null($v['parent_item_id']))
                    {
                        $v['qty_ordered'] = 0;
                        $prodPrice = 0;
                    }

                    $qtyOrdered  += $v['qty_ordered'];
                    $goodsAmount += $prodPrice * $v['qty_ordered'];

            }

            if ($isGift == 1){
                $v['grand_total'] = 0;
            }
            if ($isGroupon == 1 || $isPresale == 1){
                $v['grand_total'] = $v['grand_total'] - $v['total_due'];
            }

            // 截取收获地址的区和详细地址
            $cityarea = explode("\n", $v['street']);
            array_push($cityarea,'abnormal street');

            $reformatArray = array(
                'order_sn'        => $v['increment_id'],
                'user_id'         => $v['telephone'],
                'order_status'    => $orderStatus,
                'shipping_status' => 0,
                'pay_status'      => 2,
                'trans_type'      => 1,
                'add_time'        => $v['add_time'],
                'pay_time'        => $v['pay_time'],
                'pay_code'        => $payCode,
                'shipping_code'   => '',
                'pos_code'        => '',
                'consignee'       => $v['lastname'] . $v['firstname'],
                'country_name'    => 'CN',
                'province_name'   => $v['region'],
                'city_name'       => $v['city'],
                'district_name'   => $cityarea[0],
                'address'         => $cityarea[1],
                'zipcode'         => $v['postcode'],
                'tel'             => $v['telephone'],
                'mobile'          => $v['telephone'],
                'email'           => $v['email'],
                'best_time'       => '',
                'postscript'      => '',
                'to_buyer'        => '',
                'goods_amount'    => $goodsAmount,
                'shipping_fee'    => $v['shipping_amount'],
                'total_fee'       => $v['total_fee'],
                'money_paid'      => $v['grand_total'],
                'order_amount'    => $v['grand_total'],
                'discount'        => $v['rewardpoints_discount'],
                'goods_count'     => $qtyOrdered,
                'marks_name'      => '',
                'inv_payee'       => '',
                'inv_content'     => '',
                'source'          => 'OPENSHOP',
                'is_gift'         => $v['is_gwp'],
            );

            array_push($resultDetail, $reformatArray);
            return $resultDetail;

    }

    /**
     * 获取订单对应的商品详情
     * @param $orderId
     * @param $orderNum
     * @param $isGift
     * @return array
     */
    protected function _processProductInfo($orderId,$orderNum,$isGift,$isGroupon,$isPresale,$subtotal,$rewardpointSpent,$totalQty,$grandTotal,$shippingAmount){
            $orderProdInfo = $this->order->getOrderProdInfo($orderId);
            $resultDetail = array();
            $optionPrice = array();
            $localBundleSize = 0;
            $bundleCount     = 0;
            $sumPoints       = 0;
            $orderItemCount  = 0;
            $accuDisAmount   = 0;
            // 订单中使用优惠券个数
            $discountNum     = 0;
            $numberOfDiscount= 0;

            $wholeDiscountAmount = abs($this->utility->select_row('sales_flat_order', 'discount_amount',
                'entity_id = ' . $orderId)->discount_amount);

            foreach ($orderProdInfo as $k => $v){
                if ($v['discount_amount'] > 0){
                    $discountNum ++;
                }
            }

            foreach ($orderProdInfo as $k => $v) {
                $orderItemCount = $orderItemCount + 1;
                $prodPrice      = ($v['original_price'] == 0) ? $v['price'] : $v['original_price'];
                $transPrice     = $v['row_total'];
                $discountAmount = round($v['discount_amount'], 2);
                $sharePoint     = $v['rewardpoints_discount'];

                if ($discountAmount > 0 && $wholeDiscountAmount > 0){
                    $numberOfDiscount ++;
                    if ($discountNum == $numberOfDiscount){
                        $discountAmount = $wholeDiscountAmount - $accuDisAmount;
                    }
                    if ($discountAmount > 0){
                        $accuDisAmount += $discountAmount;
                    }
                }

                $singleProdPointArr = $this->getSingleProductRealPriceAndRewardPoints($v,$subtotal,$rewardpointSpent,$totalQty,$grandTotal,$shippingAmount);
                $singleProdPoint = $singleProdPointArr['rewardpoints'];
                if (count($orderProdInfo) == $orderItemCount){
                    $singleProdPoint = round($rewardpointSpent - $sumPoints, 2);
                }
                $sumPoints += $singleProdPointArr['rewardpoints'];
                $sharePoint = round($singleProdPoint,2);

                // 均摊价--商品折后金额
                $sharePrice = $v['row_total'] - $discountAmount + $v['tax_amount'];

                if ($v['product_type'] == 'bundle'){
                    $bundleCount = 0;
                    $localBundleProductId = $v['product_id'];
                    //bundle 子品数量
                    $localBundleSize = $this->utility->select_row('catalog_product_bundle_selection', 'count(product_id) as count',
                        'parent_product_id = ' . $localBundleProductId)->count;
                    //bundle 总价格
                    $localBundlePrice    = $v['original_price'] * $v['qty_ordered'];
                    //bundle 折后金额
                    $localBundleAmount   = $localBundlePrice - $discountAmount + $v['tax_amount'];
                    //bundle 总优惠金额
                    $localBundleDiscount = $discountAmount;

                    $bundleParentItemId  = $v['item_id'];
                    $prodPriceBundle     = ($v['original_price'] == 0) ? $v['price'] : $v['original_price'];
                    $transPriceBundle    = $v['row_total'];
                    $sharePointBundle    = $sharePoint;
                    $sumSharePrice       = 0;
                    $sumDiscountAmount   = 0;
                    $sumProdPrice        = 0;
                    $sumTransPrice       = 0;
                    $sumSharePoint       = 0;
                    continue;
                }
                else if ($v['sku_b'] != $v['option_sku'] && !is_null($v['sku']))
                {
                    // option 商品
                    $option_vals = explode('-', $v['option_sku']);
                    if (sizeof($option_vals) == 3) {
                        // option 商品为A-A-B, SKU = A-B(实际发货)
                        $options_sku = array_splice($option_vals, 1);
                        $v['option_sku'] = $options_sku[0];
                        $v['sku'] = $options_sku[0] . '-' . $options_sku[1];
                    } else if (sizeof($option_vals) == 2) {
                        // option 商品为A-B, SKU = B(实际发货)
                        $v['option_sku'] = end($option_vals);
                        $v['sku'] = end($option_vals);
                    }
                    // 拼团预售订单价格为订单实际价格
                    if ($isGroupon == 1 || $isPresale == 1){
                        $prodPrice = $v['original_price'];
                    }
                    // 赠品单价格为0
                    if ($isGift == 1){
                        $sharePrice = 0;
                    }
                }
                else if (!is_null($v['parent_item_id']))
                {
                    // 计算 bundle 子品价格
                    if ($v['parent_item_id'] == $bundleParentItemId){
                        if (is_null($v['sku'])) {
                            // 避免bundle子品SKU为空的情况
                            $v['sku']= $v['sku_b'];
                        } else {
                            $v['sku']= $v['option_sku'];
                        }
                        // bundle子品均摊价
                        $sharePrice = $this->_getLocalBundlePrice($v, $localBundleProductId, $localBundleAmount, $orderId);
                        $bundleDiscount = round(($sharePrice / ($localBundleAmount ? $localBundleAmount : 1)) * $localBundleDiscount, 2);
                        $discountAmount = $bundleDiscount;
                        $prodPrice  = round(($sharePrice /  ($localBundleAmount ? $localBundleAmount : 1)) * $prodPriceBundle, 2);
                        $transPrice = round(($sharePrice /  ($localBundleAmount ? $localBundleAmount : 1)) * $transPriceBundle, 2);
                        $sharePoint = round(($sharePrice /  ($localBundleAmount ? $localBundleAmount : 1)) * $sharePointBundle, 2);

                        if (($localBundleSize - $bundleCount) == 1) {
                            $sharePrice = $localBundleAmount - $sumSharePrice;
                            $discountAmount = $localBundleDiscount - $sumDiscountAmount;
                            $prodPrice = $prodPriceBundle - $sumProdPrice;
                            $transPrice = $transPriceBundle - $sumTransPrice;
                            $sharePoint = round(($sharePointBundle - $sumSharePoint),2);

                        }

                        // 累计bundle子品的share price & discount & share points
                        $sumSharePrice += $sharePrice;
                        $sumDiscountAmount += $discountAmount;
                        $sumProdPrice += $prodPrice;
                        $sumTransPrice += $transPrice;
                        $sumSharePoint += $sharePoint;
                    }
                    $bundleCount ++;
                }else{
                    // simple 商品, 拼团预售订单价格为订单实际价格
                    if ($isGroupon == 1 || $isPresale == 1){
                        $prodPrice = $v['original_price'];
                        $transPrice = $v['row_total'];
                    }
                    // 赠品单价格为0
                    if ($isGift == 1){
                        $sharePrice = 0;
                    }
                }

                $reformatArray = array(
                    'sku_sn' => $v['sku'],
                    'order_sn' => $orderNum,
                    'goods_sn' => $v['sku'],
                    'goods_name' => $v['name'],
                    'goods_attr' => '',
                    'goods_number' => $v['qty_ordered'],
                    'goods_price' => $prodPrice,
                    'transaction_price' => $transPrice,
                    'discount' => $discountAmount,
                    'share_price' => $sharePrice,
                    'share_point' => $sharePoint,
                    'is_gift' => $isGift,
                );

                array_push($resultDetail, $reformatArray);
            }

            return $resultDetail;
    }

    /**
     * 计算bundle商品均摊价
     * @param $v
     * @param $localBundleProductId
     * @param $bundlePriceParam
     * @param $orderId
     * @return bool|float|int
     */
    protected function _getLocalBundlePrice($v,$localBundleProductId,$bundlePriceParam,$orderId)
    {
        try {
            $bundleSelectionInfo = $this->order->getBundlePrice($v,$localBundleProductId,$orderId);
            $option_vals = explode('-', $bundleSelectionInfo[0]["sku"]);
            $whole_bundle_size = count($option_vals) - 1;

            $result = 0;
            if ($bundleSelectionInfo[0]['selection_share_price_type'] != 0) {
                $result = round($bundlePriceParam / (($v['qty_ordered'] * $whole_bundle_size) ? ($v['qty_ordered'] * $whole_bundle_size) : 1), 2);
            } else {
//                $result = round($bundlePriceParam * $bundleSelectionInfo[0]['selection_share_price_value'] / ((100 * $v['qty_ordered']) ? (100 * $v['qty_ordered']) : 1), 2);
                $result = round($bundlePriceParam * $bundleSelectionInfo[0]['selection_share_price_value'] / 100, 2);
            }
            return $result;
        } catch (Exception $e) {
            $this->returnError(EXIT_DATABASE, 'bundle商品查询失败' . $e->getMessage());
            return false;
        }
    }

    // 订单蜜豆分摊计算
    public function getSingleProductRealPriceAndRewardPoints($v,$subtotal,$rewardpointSpent,$totalQty,$grandTotal,$shippingAmount){
        $rowTotal = $v['row_total'];
        $rowQty = $v['qty_ordered'];

        $order = $v['order_id'];
        $orderPriceExcludeShipping = $grandTotal - $shippingAmount;
        if($orderPriceExcludeShipping == 0){
            return array('price'=>0,'rewardpoints'=>($rewardpointSpent / $totalQty));
        }
        //商品实付单价=（整笔订单实付Grandtotal-订单运费）*该商品金额（商品subtotal）/订单商品金额（即订单subtotal）/商品购买数量
        $orderSubTotal = $subtotal;

        //单个商品分摊的比例
        $rate = ($rowTotal / $rowQty) / $orderSubTotal;

        $singlePrice = $orderPriceExcludeShipping * $rate;
        //截取两位
        $singlePrice = number_format($singlePrice-0.005, 2, '.', '');
//        $singlePrice = round($singlePrice,2);

        //单个商品分摊的蜜豆数
        $singleReward = $rewardpointSpent * $rate;
//        $singleReward = round($rewardpointSpent * $rate , 2);

        //单个商品实际付款数
        return array('price'=>$singlePrice,'rewardpoints'=>$singleReward);
    }

    /**
     * 订单发货回写
     */
    public function doOrderDelivery(){
        $data     = array();
        $tokenData = array(
            'order_sn'      => $this->post('oid'),
            'shipping_sn'   => $this->post('shipping_sn'),
            'shipping_code' => $this->post('shipping_code')
        );
        $orderNum     = $tokenData['order_sn'];
        $shippingNum  = $tokenData['shipping_sn'];
        $shippingCode = $tokenData['shipping_code'];

        $orderInfo = $this->utility->select_row('sales_flat_order',array('entity_id','status'), array('increment_id' => $orderNum));

        // 验证参数
        if(!$this->_checkDeliveryParam($orderInfo, $shippingNum, $shippingCode, $orderNum))
        {
            return;
        }

        switch (strtolower($shippingCode)) {
            case 'post':
                $shippingCode = SHIPPING_COMPANY_YZGN;
                break;
            case 'ems':
                $shippingCode = SHIPPING_COMPANY_EMS;
                break;
            case 'sf':
                $shippingCode = SHIPPING_COMPANY_SHUNFENG;
                break;
            case 'sto':
                $shippingCode = SHIPPING_COMPANY_SHENTONG;
                break;
            case 'yto':
                $shippingCode = SHIPPING_COMPANY_YUANTONG;
                break;
            case 'zto':
                $shippingCode = SHIPPING_COMPANY_ZHONGTONG;
                break;
            case 'htky':
                $shippingCode = SHIPPING_COMPANY_HUITONG;
                break;
            case 'yunda':
                $shippingCode = SHIPPING_COMPANY_YUNDA;
                break;
            case 'zjs':
                $shippingCode = SHIPPING_COMPANY_ZHAIJISONG;
                break;
            case 'ttkdex':
                $shippingCode = SHIPPING_COMPANY_TIANTIAN;
                break;
            case 'dbl':
                $shippingCode = SHIPPING_COMPANY_DEBANG;
                break;
            case 'fedex':
                $shippingCode = SHIPPING_COMPANY_LIANBANG;
                break;
            case 'best':
                $shippingCode = SHIPPING_COMPANY_BAISHI;
                break;
            case 'yct':
                $shippingCode = SHIPPING_COMPANY_HEIMAO;
                break;
            default:
                $shippingCode = 'others';
                $this->_log->write_log('shipping_info', $shippingCode);
                break;
        }

        // 处理订单发货状态更新
        if($this->_processOrderStatusUpdate($orderNum, $shippingNum, $shippingCode, $tokenData)){
            // 返回
            $this->returnSuccess($data);
        }

    }

    //验证参数
    protected function _checkDeliveryParam($orderInfo, $shippingNum, $shippingCode, $orderNum)
    {
        // 参数验证
        if(!isset($shippingNum) || is_null($shippingNum) || $shippingNum == '')
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code').'快递单号不能为空');
            return false;
        }
        if(!isset($shippingCode) || is_null($shippingCode) || $shippingCode == '')
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code').'快递公司代码不能为空');
            return false;
        }
        if(!isset($orderNum) || is_null($orderNum) || $orderNum == '')
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code').'订单号不能为空');
            return false;
        }
        if(empty($orderInfo))
        {
            $this->returnError(self::ORDER_NOT_FOUND,$this->config->item(self::ORDER_NOT_FOUND, 'error_code').'订单号不存在');
            return false;
        }

        return true;
    }

    /**
     * 处理订单发货回写
     * @param $orderNum
     * @param $shippingNum
     * @param $shippingCode
     * @return bool
     */
    protected function _processOrderStatusUpdate($orderNum, $shippingNum, $shippingCode, $tokenData){
        $orderInfo = $this->utility->select_row('sales_flat_order',array('entity_id','status'), array('increment_id' => $orderNum));
        $orderId = $orderInfo->entity_id;
        $table= 'sales_flat_shipment_track';
        $shipping_order = $this->utility->select_row($table, array('track_number', 'title'), array('order_id' => $orderId));
        // 检查物流信息是否已经存在,如有则不再更新
        if ($shipping_order = $this->utility->select_row($table, array('track_number', 'title'), array('order_id' => $orderId))){
            $this->returnError(self::ORDER_ALREADY_SHIPPED,'订单已存在配送');
            return false;
        }
        if ($orderInfo->status == 'complete') {
            $this->returnError(self::ORDER_ALREADY_SHIPPED, '订单已存在配送');
            return false;
        }else if ($orderInfo->status != 'ready_shipment'){
            $this->returnError(self::ORDER_STATUS_INCORRECT, '订单状态有误,同步信息失败');
            return false;
        }else{
            // 对待发货状态的订单更新物流信息
            if ($this->_processOrderShipmentUpdate($orderNum,$shippingNum,$shippingCode, $tokenData)) {
                return true;
            }else{
                $this->returnError(self::SHIPPING_INFO_UPDATE_FAILED,'更新物流信息失败');
                return false;
            }
        }
    }

    /**
     * 更新MAGENTO相应表
     * @param $orderNum
     * @param $shippingNum
     * @param $shippingCode
     * @return bool
     */
    protected function _processOrderShipmentUpdate($orderNum,$shippingNum,$shippingCode, $tokenData){
        $url = $this->config->item('magento_server').'/service/erpapi/deliveryFeedback';

        $post_data = array(
            'order_sn'      =>  $orderNum,
            'shipping_sn'   =>  $shippingNum,
            'shipping_code' =>  $shippingCode,
        );

        $result = $this->extend->curl_post($url,$post_data);

        // 请求超时
        if(is_null($result))
        {
            $this->returnError(ERR_MAGENTO_TIMEOUT,$this->config->item(ERR_MAGENTO_TIMEOUT, 'error_code'));
            return false;
        }
        // 请求成功
        else if($result['code'] == 1)
        {
            return true;
        }
        // 请求失败
        else
        {
            $this->returnError(ERR_MAGENTO_REQUEST,$this->config->item(ERR_MAGENTO_REQUEST, 'error_code'));
            return false;
        }

    }

    /**
     * 退货单列表接口
     */
    public function doOrderReturnApply(){

        $inputData = array(
            'page'         => $this->post('page') ? $this->post('page') : DEFAULT_PAGE_NUM,
            'pageSize'     => $this->post('page_size')? $this->post('page_size') : DEFAULT_PAGE_SIZE,
            'order_sn'     => $this->post('order_sn'),
            'apply_time'   => $this->post('apply_time'),
            'returns_status'=> $this->post('returns_status') ? $this->post('returns_status') : 2,
        );

        $limitStart = ($inputData['page'] - 1) * $inputData['pageSize'] ;
        switch ($inputData['returns_status']){
            case 0:
                $returnStatus = $this::RETURN_ORDER_CLOSED;
                break;
            case 1:
                $returnStatus = $this::RETURN_ORDER_PENDING;
                break;
            case 2:
                $returnStatus = $this::RETURN_ORDER_PENDING_RECEIVED;
                break;
            case 3:
                $returnStatus = $this::RETURN_ORDER_COMPLETE;
                break;
            case 4:
                $returnStatus = $this::RETURN_ORDER_DENIED;
                break;
            default:
                $returnStatus = '';
                break;
        }
        // 验证参数
        if(!$this->_checkReturnOrderListParam($inputData,$returnStatus))
        {
            return;
        }

        // 处理退货订单列表
        if($data = $this->_processReturnOrderList($inputData, $limitStart, $returnStatus))
        {
            // 返回
            $this->returnSuccess($data);
        }else{
            $this->returnError(ERR_UNEXPECTED,'退货单拉取失败');
        }

    }

    /**
     * 验证参数
     * @param $inputData
     * @param $returnStatus
     * @return bool
     */
    protected function _checkReturnOrderListParam($inputData,$returnStatus){
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

        if ($inputData['returns_status'] != 2){
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').':returns_status必须为2->受理状态');
            return false;
        }

        // 验证申请时间
        if ($inputData['apply_time'] != '') {
            $isDate = strtotime($inputData['apply_time']) ? strtotime($inputData['apply_time']) : false;
            if ($isDate === false) {
                $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT, $this->config->item(ERR_PARAMETER_FORMAT_INCORRECT, 'error_code') . '申请时间格式错误');
                return false;
            }
        }

        return true;

    }

    /**
     * 处理退货单列表
     * @param $inputData
     * @param $limitStart
     * @param $returnStatus
     * @return array
     */
    protected function _processReturnOrderList($inputData, $limitStart, $returnStatus){
//        try
//        {
            // 取满足条件的订单总数
            $resultCount = $this->order->getReturnOrderListCount($inputData['apply_time'],$returnStatus,$inputData['order_sn']);

            if($resultCount == 0)
            {
                $pageInfo = array(
                    'current_page' => $inputData['page'],
                    'pages'        => 0,
                    'page_size'    => 0,
                    'total'        => 0,
                );
                $data = array(
                    'page_items' => null,
                    'page_info'  => $pageInfo
                );

                return $data;
            }
            // 退货单列表
            $result = $this->order->getReturnOrderList($inputData['apply_time'],$inputData['pageSize'],$limitStart,$returnStatus,$inputData['order_sn']);

            // 退货商品是否为bundle
            $prodType = $result[0]['product_type'];
            $subtotal = $result[0]['subtotal'];
            $rewardpointSpent = $result[0]['rewardpoints_spent'];
            $totalQty = $result[0]['total_qty_ordered'];
            $grandTotal = $result[0]['grand_total'];
            $shippingAmount = $result[0]['shipping_amount'];
            $resultDetail = array();
            foreach ($result as $k=>$v){
                $prodType = $v['product_type'];
                $subtotal = $v['subtotal'];
                $rewardpointSpent = $v['rewardpoints_spent'];
                $totalQty = $v['total_qty_ordered'];
                $grandTotal = $v['grand_total'];
                $shippingAmount = $v['shipping_amount'];
                $singleProdPointArr = $this->getSingleProductRealPriceAndRewardPoints($v,$subtotal,$rewardpointSpent,$totalQty,$grandTotal,$shippingAmount);

                // 截取收获地址的区和详细地址
                $area     = str_replace("\n"," ",$v['street']);
                $cityarea = explode(' ', $area);
                switch ($v['method']){
                    case 'alipay_payment':
                        $payCode = $this::PAY_CODE_ALIPAY_PAYMENT;
                        break;
                    case 'globalalipay_payment':
                        $payCode = $this::PAY_CODE_GLOBALALIPAY_PAYMENT;
                        break;
                    case 'wechat_payment':
                        $payCode = $this::PAY_CODE_WECHAT_PAYMENT;
                        break;
                    case 'free':
                        $payCode = $this::PAY_CODE_FREE;
                        break;
                }
                switch ($v['express_name']){
                    case 'EMS':
                        $returnShippingCode = SHIPPING_COMPANY_EMS;
                        break;
                    case '中通快递':
                        $returnShippingCode = SHIPPING_COMPANY_ZHONGTONG;
                        break;
                    case '圆通快递':
                        $returnShippingCode = SHIPPING_COMPANY_YUANTONG;
                        break;
                    case '天天快递':
                        $returnShippingCode = SHIPPING_COMPANY_TIANTIAN;
                        break;
                    case '申通快递':
                        $returnShippingCode = SHIPPING_COMPANY_SHENTONG;
                        break;
                    case '百世快递':
                        $returnShippingCode = SHIPPING_COMPANY_BAISHI;
                        break;
                    case '韵达快递':
                        $returnShippingCode = SHIPPING_COMPANY_YUNDA;
                        break;
                    case '顺丰快递':
                        $returnShippingCode = SHIPPING_COMPANY_SHUNFENG;
                        break;
                    case '宅急送':
                        $returnShippingCode = SHIPPING_COMPANY_ZHAIJISONG;
                        break;
                    case '京东快递':
                        $returnShippingCode = SHIPPING_COMPANY_JINGDONG;
                        break;
                    case '全峰快递':
                        $returnShippingCode = SHIPPING_COMPANY_QUANFENG;
                        break;
                    default:
                        $returnShippingCode = 'others';
                        $this->_log->write_log('shipping_info', $returnShippingCode);
                        break;
                }

                switch ($v['return_description']){
                    case self::RETURN_REASON_ONE:
                        $reason = '七天无理由退货';
                        break;
                    case self::RETURN_REASON_TWO :
                        $reason = '产品疑似质量问题';
                        break;
			        case self::RETURN_REASON_THREE :
                        $reason = '产品漏发、错发、破损';
                        break;
			        case self::RETURN_REASON_FOUR :
                        $reason = '收到货后产品降价幅度大';
                        break;
                    case self::RETURN_REASON_FIVE :
                        $reason = '快递丢件、快递退件';
                        break;
                    case self::RETURN_REASON_SIX :
                        $reason = '收到商品与描述、图片不符';
                        break;
                    case self::RETURN_REASON_SEVEN :
                        $reason = '其他';
                        break;
                    default :
                        $reason = $v['return_description'];
                        break;

                }

                $refundGood = array();
                if ($prodType == 'bundle')
                {
                    $sumPrice = 0;
                    $subProdCount = 0;
                    $sumPoints = 0;
                    $bundleSku = $v['sku_b'];
                    $localBundleProductId = $v['product_id'];
                    //bundle 子品数量
                    $localBundleSize = $this->utility->select_row('catalog_product_bundle_selection', 'count(product_id) as count',
                        'parent_product_id = ' . $localBundleProductId)->count;
                    $subProd = $this->order->getSubProdItems($bundleSku,$v['increment_id']);


                    $singleProdPoint = $singleProdPointArr['rewardpoints'];

                    $orderId = $v['order_id'];

                    // bundle商品总金额
                    $localBundlePrice    = $v['amount'] * $v['qty_ordered'];

                    //bundle 折后金额
                    $localBundleAmount   = $localBundlePrice - $v['discount_amount'] + $v['tax_amount'];
                    foreach ($subProd as $kb => $vb)
                    {
                        $subProdCount ++;

                        // bundle子品均摊价
                        $sharePrice = $this->_getLocalBundlePrice($vb, $localBundleProductId, $localBundleAmount, $orderId);

                        if (sizeof($subProd) == $subProdCount){
                            $singleProdPoint = round($rewardpointSpent - $sumPoints, 2);
                            $sharePoint = round($singleProdPoint,2);
                            $refundGoods = array(
                                'barcode'     => $vb['sku'],
                                'shop_price'  => $v['row_total'] - $v['discount_amount'] + $v['tax_amount'] - $sumPrice ,
                                'goods_price' => $v['row_total'] - $v['discount_amount'] + $v['tax_amount'] - $sumPrice ,
                                'goods_number'=> $v['qty_requested'],
                                'reason'      => $reason,
                                'share_point' => $sharePoint,
                                'is_gift'     => 'N',
                            );
                        }else {
                            $sharePoint = round($singleProdPoint / sizeof($subProd),2);
                            $sumPoints += $sharePoint;
                            $refundGoods = array(
                                'barcode'     => $vb['sku'],
                                'shop_price'  => round($sharePrice / $v['qty_requested'], 2),
                                'goods_price' => round($sharePrice / $v['qty_requested'], 2),
                                'goods_number'=> $v['qty_requested'],
                                'reason'      => $reason,
                                'share_point' => $sharePoint,
                                'is_gift'     => 'N',
                            );
                            $sumPrice += round($sharePrice / $v['qty_requested'], 2);
                        }
                        array_push($refundGood, $refundGoods);

                        // 退货单中是否包含赠品
                        $giftProd = $this->order->getGiftProdItems($v['increment_id']);

                        if (sizeof($giftProd) > 0){
                            foreach ($giftProd as $giftKey => $giftValue){
                                $refundGiftGoods = array(
                                    'barcode' => $giftValue['sku'],
                                    'shop_price' => $v['amount'],
                                    'goods_price' => $v['amount'],
                                    'goods_number' => $v['qty_requested'],
                                    'reason' => $reason,
                                    'share_point' => 0,
                                    'is_gift'  => 'Y'
                                );
                                array_push($refundGood, $refundGiftGoods);
                            }
                        }

                    }
                }
                else if ($v['sku_b'] != $v['sku']) {
                    $option_vals = explode('-', $v['sku']);
                    if (sizeof($option_vals) == 3) {
                        $options_sku = array_splice($option_vals, 1);
                        $v['sku'] = $options_sku[0] . '-' . $options_sku[1];
                    } else if (sizeof($option_vals) == 2) {
                        $v['option_sku'] = end($option_vals);
                        $v['sku'] = end($option_vals);
                    }

                    $refundGoods = array(
                        'barcode' => $v['sku'],
                        'shop_price' => $v['amount'],
                        'goods_price' => $v['amount'],
                        'goods_number' => $v['qty_requested'],
                        'reason' => $reason,
                        'share_point' => round($rewardpointSpent * ($v['qty_requested'] / $v['qty_ordered']), 2),
                        'is_gift'  => 'N'
                    );

                    array_push($refundGood, $refundGoods);

                    // 退货单中是否包含赠品
                    $giftProd = $this->order->getGiftProdItems($v['increment_id']);

                    if (sizeof($giftProd) > 0){
                        foreach ($giftProd as $giftKey => $giftValue){
                            $refundGiftGoods = array(
                                'barcode' => $giftValue['sku'],
                                'shop_price' => $v['amount'],
                                'goods_price' => $v['amount'],
                                'goods_number' => $v['qty_requested'],
                                'reason' => $reason,
                                'share_point' => round($rewardpointSpent * ($v['qty_requested'] / $v['total_qty_ordered']), 2),
                                'is_gift'  => 'Y'
                            );
                            array_push($refundGood, $refundGiftGoods);
                        }
                    }

                }
                else {
                    $refundGoods = array(
                        'barcode' => $v['sku'],
                        'shop_price' => $v['amount'],
                        'goods_price' => $v['amount'],
                        'goods_number' => $v['qty_requested'],
                        'reason' => $reason,
                        'share_point' => round($rewardpointSpent * ($v['qty_requested'] / $v['total_qty_ordered']), 2),
                        'is_gift'  => 'N'
                    );

                    array_push($refundGood, $refundGoods);

                    // 退货单中是否包含赠品
                    $giftProd = $this->order->getGiftProdItems($v['increment_id']);

                    if (sizeof($giftProd) > 0){
                        foreach ($giftProd as $giftKey => $giftValue){
                            $refundGiftGoods = array(
                                'barcode' => $giftValue['sku'],
                                'shop_price' => $v['amount'],
                                'goods_price' => $v['amount'],
                                'goods_number' => $v['qty_requested'],
                                'reason' => $reason,
                                'share_point' => round($rewardpointSpent * ($v['qty_requested'] / $v['qty_ordered']), 2),
                                'is_gift'  => 'Y'
                            );
                            array_push($refundGood, $refundGiftGoods);
                        }
                    }
                }
                $reformatArray = array(
                    'returns_order_sn' => $v['increment_id'],
                    'returns_status' => $returnStatus,
                    'order_sn' => $v['order_increment_id'],
                    'user_id' => $v['customer_id'],
                    'receiver_name' => $v['shipping_name'],
                    'province' => $v['region'],
                    'city' => $v['city'],
                    'district' => $cityarea[0],
                    'address' => $cityarea[1],
                    'receiver_mobile' => $v['telephone'],
                    'receiver_tel' => $v['telephone'],
                    'receiver_zip' => $v['postcode'],
                    'receiver_email' => $v['email'],
                    'pay_code' => $payCode,
                    'shipping_code' => strtolower($v['title']),
                    'return_shipping_code' => $this->config->item($returnShippingCode, 'shipping_code'),
                    'return_shipping_sn' => $v['express_number'],
                    'return_money' => $singleProdPointArr['price'] * $v['qty_requested'],
                    'shipping_fee' => 0,
                    'add_time' => $v['created_at'],
                    'return_desc' => $v['return_description'],
                    'best_time' => '',
                    'source' => '',
                    'return_type' => 1,
                    'refund_goods' => $refundGood,
                    'return_shipping_fee' => 0,
                    'qty_requested' => $v['qty_requested']
                );

                array_push($resultDetail, $reformatArray);
            }
            $pageInfo = array(
                'current_page' => $inputData['page'],
                'pages'        => ceil($resultCount/$inputData['pageSize']),
                'page_size'    => count($result),
                'total'        => $resultCount,
            );
            $data = array(
                'page_items' => $resultDetail,
                'page_info'  => $pageInfo
            );

            return $data;
//        }
//        catch(Exception $e)
//        {
//            $this->returnError(EXIT_DATABASE,'订单列表查询失败'.$e->getMessage());
//            return false;
//        }
    }

    /**
     * 退货单回写接口
     */
    public function doOrderReturnUpdate(){
        $oid      = $this->post('oid');

        $inputData = array(
            'rmaId'  => $oid,
            'status' => $this->post('status'),
            'count'  => $this->post('count'),
            'comment'=> $this->post('comment'),
            'reason' => $this->post('reason')
        );

        // 验证参数
        if(!$this->_checkReturnOrderUpdateParam($oid, $inputData))
        {
            return;
        }

        $returnId = $this->utility->select_row('rma','entity_id',array('increment_id' => $oid))->entity_id;
        $data = array();

        if ($inputData['status'] == 3){
            $data = $this->_processReturnOrderReceived($inputData, $returnId);
        }elseif ($inputData['status'] == 4){
            $data = $this->_processReturnOrderDeny($inputData, $returnId);
        }

        // 处理退货订单列表
        if($data)
        {
            // 返回
            $this->returnSuccess($data);
        }else{
            $this->returnError(ERR_UNEXPECTED,'退货回写失败');
        }
    }

    protected function _checkReturnOrderUpdateParam($oid,$inputData){
        $returnId = $this->utility->select_row('rma','entity_id, qty_requested',array('increment_id' => $oid));
        //
        if(is_null($oid))
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING,'error_code').'退货申请单号为空');
            return false;
        }
        else if(is_null($returnId))
        {
            $this->returnError(self::ORDER_NOT_FOUND,$this->config->item(self::ORDER_NOT_FOUND,'error_code').'return order not found!');
            return false;
        }
        else if(!isset($inputData['count']) || is_null($inputData['count']) || $inputData['count'] == '' || $inputData['count'] == 0)
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING,'error_code').'退货数量未指定!');
            return false;
        }
        else if($returnId->qty_requested < $inputData['count']){
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT,'error_code').'退货数量超出退货申请最大数量!');
            return false;
        }

        return true;

    }

    /**
     * 处理退货单申请接收
     * @param $inputData
     * @return bool
     */
    public function _processReturnOrderReceived($inputData, $returnId){
        $url = $this->config->item('magento_server').'/service/erpapi/rmaRecevied';
        $post_data = array(
            'id'      =>  $returnId,
            'count'   =>  $inputData['count'],
            'comment' =>  $inputData['comment'],
            'reason'  =>  $inputData['reason'],
        );
        $result = $this->extend->curl_post($url,$post_data);

        // 请求超时
        if(is_null($result))
        {
            $this->returnError(ERR_MAGENTO_TIMEOUT,$this->config->item(ERR_MAGENTO_TIMEOUT, 'error_code'));
            return false;
        }
        // 请求成功
        else if($result['code'] == 1)
        {
            return true;
        }
        // 请求失败
        else
        {
            $this->returnError(ERR_MAGENTO_REQUEST,$this->config->item(ERR_MAGENTO_REQUEST, 'error_code'));
            return false;
        }
    }

    /**
     * 处理退货单申请拒绝
     * @param $inputData
     * @return bool
     */
    public function _processReturnOrderDeny($inputData, $returnId){

        $url = $this->config->item('magento_server').'/service/erpapi/rmaDenyRecevied';
        $sign = $this->extend->createSign($inputData);
        $post_data = array(
            'id'      =>  $returnId,
            'count'   =>  $inputData['count'],
            'comment' =>  $inputData['comment'],
            'reason'  =>  $inputData['reason'],
        );
        $result = $this->extend->curl_post($url,$post_data);

        // 请求超时
        if(is_null($result))
        {
            $this->returnError(ERR_MAGENTO_TIMEOUT,$this->config->item(ERR_MAGENTO_TIMEOUT, 'error_code'));
            return false;
        }
        // 请求成功
        else if($result['code'] == 1)
        {
            return true;
        }
        // 请求失败
        else
        {
            $this->returnError(ERR_MAGENTO_REQUEST,$this->config->item(ERR_MAGENTO_REQUEST, 'error_code'));
            return false;
        }
        return true;
    }

    /**
     * 订单状态回写接口
     */
    public function doOrderUpdateStatus(){
        $inputData = array(
            'order_sn'  => explode(",",$this->post('deal_code')),
        );

        $orderNum = $inputData['order_sn'];

        // 验证参数
        if(!$this->_checkUpdateStatusParam($inputData,$orderNum))
        {
            return;
        }

        // 处理订单列表
        if($data = $this->_processUpdateStatus($orderNum))
        {
            // 返回
            $this->returnSuccess($data);

        }else{
            return;
        }
    }

    // 验证参数
    protected function _checkUpdateStatusParam($inputData,$orderNum){
        if(!isset($inputData['order_sn']) || is_null($inputData['order_sn']))
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code') . ':deal_code');
            return false;
        }
        foreach ($orderNum as $k=>$v){
            $orderId = $this->utility->select_row('sales_flat_order', array('entity_id', 'status'), array('increment_id' => $v));
            if (!isset($orderId->entity_id) || is_null($orderId->entity_id)){
                $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code') . ':订单号: '.$v.' 不存在');
                return false;
            }
        }

        return true;
    }

    /**
     * 处理订单状态回写
     * @param $orderNum
     * @return bool
     */
    public function _processUpdateStatus($orderNum){
        $result = array();
        $abnormalOrder = array();
        $data = array();

        foreach ($orderNum as $k=>$v){
            $status = $this->utility->select_row('sales_flat_order','status',array('increment_id' => $v))->status;
            if($status == 'ready_shipment')
            {
                continue;
            }
            else if($status != 'processing'){
                array_push($abnormalOrder, $v);
            }
            else
            {
                $url = $this->config->item('magento_server').'/service/erpapi/updateOrderStatus';
                $post_data = array(
                    'order_sn'      =>  $v,
                );

                $result = $this->extend->curl_post($url,$post_data);

                // 请求超时
                if(is_null($result))
                {
                    $this->returnError(ERR_MAGENTO_TIMEOUT,$this->config->item(ERR_MAGENTO_TIMEOUT, 'error_code'),['error']);
                    return false;
                }
                // 请求失败
                elseif ($result['code'] != 1)
                {
                    $this->returnError(ERR_MAGENTO_REQUEST,$this->config->item(ERR_MAGENTO_REQUEST, 'error_code'));
                    return false;
                }
            }

        }
        
        if(!empty($abnormalOrder)){
            $this->returnError(ERR_UNEXPECTED, $this->config->item(ERR_UNEXPECTED, 'error_code' . 'abnormal order with unexpected status.'), $abnormalOrder);
            return false;
        }
        return true;

    }


    /**
     * 商品列表接口
     */
    public function doItemList()
    {
        $data = array();
        $inputData = array(
            'page'      =>  $this->post('page') ? $this->post('page') : DEFAULT_PAGE_NUM,
            'pageSize'  =>  $this->post('page_size')? $this->post('page_size') : DEFAULT_PAGE_SIZE,
            'skuSns'    =>  json_decode($this->post('sku_sns'),true),
        );

        // 验证参数
        if(!$this->_checkListParam($inputData))
        {
            return;
        }

        // 处理
        $data = $this->_processList($inputData);

        // 返回
        $this->returnSuccess($data);
    }

    /**
     * 商品列表接口参数验证
     * @param $inputData
     * @return bool
     */
    protected function _checkListParam($inputData)
    {
        // 页数参数
        if(!is_numeric($inputData['page']))
        {
            $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT,$this->config->item(ERR_PARAMETER_FORMAT_INCORRECT, 'error_code') . ':page类型');
            return false;
        }
        else if((
                (int)$inputData['page'] != $inputData['page'])
            || ((int)$inputData['page'] <= 0)
        )
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT, 'error_code') . ':page必须为正整数');
            return false;
        }


        // 单页尺寸参数
        if(!is_numeric($inputData['pageSize']))
        {
            $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT,$this->config->item(ERR_PARAMETER_FORMAT_INCORRECT, 'error_code') . ':pageSize类型');
            return false;
        }
        else if((
                (int)$inputData['pageSize'] != $inputData['pageSize'])
            || ((int)$inputData['pageSize'] <= 0)
        )
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT, 'error_code') . ':pageSize必须为正整数');
            return false;
        }

        return true;
    }

    /**
     * 商品列表获取处理
     * @param $inputData
     * @return mixed
     */
    protected function _processList($inputData)
    {
        $pageInfo = $this->item->getItemPageInfo($inputData);
        $pageItems = $this->item->getItemList($inputData);
        $result = array(
            'page_info'     =>  $pageInfo,
            'page_items'    =>  $pageItems,
        );

        return $result;
    }

    /**
     * 根据商品编码获取商品信息接口
     */
    public function doItemDetail()
    {
        $data = array();
        $inputData = array(
            'sku_sn'    =>  $this->post('sn'),
        );

        // 验证参数
        if(!$this->_checkDetailParam($inputData))
        {
            return;
        }

        // 处理
        $data = $this->_processDetail($inputData);

        // 返回
        $this->returnSuccess($data);
    }

    /**
     * 根据商品编码获取商品信息接口参数验证
     * @param $inputData
     * @return bool
     */
    protected function _checkDetailParam($inputData)
    {
        // sku验证
        if(!isset($inputData['sku_sn']) || is_null($inputData['sku_sn']) || $inputData['sku_sn'] == '')
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code') . ':sn');
            return false;
        }

        $goodsInfo = $this->item->getItemInfo($inputData);
        if(empty($goodsInfo))
        {
            $this->returnError(ERR_RETURN_INFO_EMPTY,$this->config->item(ERR_RETURN_INFO_EMPTY, 'error_code') . ':商品不存在');
            return false;
        }

        if($goodsInfo[0]['value'] == 34 || $goodsInfo[0]['value'] == 0)
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT, 'error_code') . ':韩国仓商品');
            return false;
        }

        if($goodsInfo[0]['value'] == 52)
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT, 'error_code') . ':保税仓商品');
            return false;
        }

        return true;
    }

    /**
     * 根据商品编码获取商品信息获取处理
     * @param $inputData
     * @return mixed
     */
    protected function _processDetail($inputData)
    {
        $result = $this->item->getItemDetail($inputData);
        return $result;
    }

    /**
     * 库存设置接口
     */
    public function doItemSetInventory()
    {
        $inputData = array(
            'sku_sn'    =>  $this->post('sku'),
            'qty'       =>  $this->post('actual_number'),
        );

        // 验证参数
        if(!$this->_checkSetInventoryParam($inputData))
        {
            return;
        }

        // 处理
        if($this->_processSetInventory($inputData))
        {
            $this->returnSuccess(MSG_SUCCESS);
        }
        else
        {
            return;
        }

    }

    /**
     * 库存设置接口参数验证
     * @param $inputData
     * @return bool
     */
    protected function _checkSetInventoryParam($inputData)
    {

        // sku验证
        if(!isset($inputData['sku_sn']) || is_null($inputData['sku_sn']))
        {
            $this->returnError(ERR_PARAMETER_MISSING,$this->config->item(ERR_PARAMETER_MISSING, 'error_code') . ':sku');
            return false;
        }

        // 数量参数
        if(!is_numeric($inputData['qty']))
        {
            $this->returnError(ERR_PARAMETER_FORMAT_INCORRECT,$this->config->item(ERR_PARAMETER_FORMAT_INCORRECT, 'error_code') . ':actual_number');
            return false;
        }
        else if((int)$inputData['qty'] != $inputData['qty'])
        {
            $this->returnError(ERR_PARAMETER_INCORRECT,$this->config->item(ERR_PARAMETER_INCORRECT, 'error_code') . ':actual_number必须为整数');
            return false;
        }

        return true;
    }

    /**
     * 库存设置处理(计算Magento的可用库存)
     * @param $inputData
     * @return bool
     */

    protected function _processSetInventory($inputData)
    {
        // 获取ERP中的可用库存
        $erpStock = $inputData['qty'];

        // 获取在订单中需要锁定的库存
        $blockedStock = $this->item->getBlockedStock($inputData['sku_sn']);

        // 计算实际的库存
        $realStock = $erpStock - $blockedStock;

        // 调用magentoApi更新库存
        $url = $this->config->item('magento_server').'/service/erpapi/syncStock';
        $post_data = array(
            'sku'       =>  (string)$inputData['sku_sn'],
            'qty'       =>  (string)$realStock,
        );

        $result = $this->extend->curl_post($url,$post_data);

        // 请求超时
        if(is_null($result))
        {
            $this->returnError(ERR_MAGENTO_TIMEOUT,$this->config->item(ERR_MAGENTO_TIMEOUT, 'error_code'));
            return false;
        }
        // 请求成功
        else if($result['code'] == 1)
        {
            return true;
        }
        // 请求失败
        else
        {
            $this->returnError(ERR_MAGENTO_REQUEST,$this->config->item(ERR_MAGENTO_REQUEST, 'error_code'),json_encode($result));
            return false;
        }
    }


}