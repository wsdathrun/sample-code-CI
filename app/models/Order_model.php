<?php
/**
 * Created by PhpStorm.
 * User: Eathan
 * Date: 17/1/16
 * Time: 下午6:38
 */

class Order_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Utility_model','utility');

    }

    /**
     * 获取订单列表
     * @param $lastUpdated
     * @param null $limitNum
     * @param null $limitStart
     * @return mixed
     */
    public function getOrderList($lastUpdated,$limitNum,$limitStart){
        $this->db->select('s.increment_id');
        $this->db->where('s.status = "processing"');
        $this->db->where_not_in('s.warehouse', array(1,3,4,8));
//        $this->db->where_not_in('s.increment_id', '(select order_id from activity_groupon_order as g where g.status = "failed" or g.status = "processing")');
        $this->db->where('s.increment_id not in (select order_id from activity_groupon_order as g where g.status = "failed" or g.status = "processing")');
        if ($lastUpdated) {
            $this->db->where('date_add(s.created_at, interval 8 hour) >= "'.$lastUpdated.'"');
        }
        if(($limitNum > 0) || ($limitStart > 0))
        {
            $this->db->limit($limitNum,$limitStart);
        }
        $this->db->order_by('s.increment_id desc');
        $result= $this->db->get('sales_flat_order s');
        return $result->result_array();
    }

    /**
     * 查询符合条件的总个数
     * @param $lastUpdated
     * @return mixed
     */
    public function getOrderListCount($lastUpdated){
        $this->db->select('count(1) as total');
        $this->db->where('s.status = "processing"');
        //$this->db->where('s.warehouse', 2);
        $this->db->where_not_in('s.warehouse', array(1,3,4,8));
        $this->db->where_not_in('s.increment_id', '(select order_id from activity_groupon_order as g where g.status = "failed" or g.status = "processing")');
        if ($lastUpdated) {
            $this->db->where('date_add(s.created_at, interval 8 hour) >= "'.$lastUpdated.'"');
        }
        $result= $this->db->get('sales_flat_order s');
        return $result->result_array();
    }

    /**
     * 查询拼团订单
     * @param $orderId
     * @return mixed
     */
    public function verifyGiftOrder($orderId){
        $this->db->select('s.increment_id');
        $this->db->where_not_in('s.warehouse', array(1,3,4,8));
        $this->db->where('s.increment_id not in (select order_id from activity_groupon_order as g where g.status = "failed" or g.status = "processing")');
        $this->db->where('s.entity_id', $orderId);
        $result= $this->db->get('sales_flat_order s');
        $result= $result->result_array();

        if (!isset($result[0]['increment_id']) && empty($result)){
            return false;
        }else{
            return true;
        }

    }

    /**
     * 获取订单详情
     * @param $orderNum
     * @return mixed
     * select s.increment_id, p.phone, s.status, date_add(s.created_at, interval 8 hour) as add_time,
    date_add(si.created_at, interval 8 hour) as pay_time, sp.method, sa.lastname, sa.firstname, sa.region, sa.city,
    sa.street, sa.postcode, sa.telephone, sa.email, s.shipping_amount, (s.subtotal+s.shipping_amount) as total_fee,
    s.grand_total, sum(i.qty_ordered) as qty_ordered, sum(d.value) as goods_amount, s.is_gwp from
    sales_flat_order s left join sales_flat_order_item i on i.order_id = s.entity_id
    left join catalog_product_entity e on e.entity_id = i.product_id
    left join catalog_product_entity_int ci on e.entity_id = ci.entity_id
    left join catalog_product_entity_decimal d on d.entity_id = e.entity_id
    left join customer_phone_auth p on p.customer_id = s.customer_id
    left join sales_flat_order_payment sp on sp.parent_id = s.entity_id
    left join sales_flat_invoice si on si.order_id = s.entity_id
    left join sales_flat_order_address sa on sa.parent_id = s.entity_id
    where d.attribute_id = 162 and ci.attribute_id = 159 and ci.value <> 34 and sa.address_type = "shipping" and s.increment_id = '101125384'
    group by s.increment_id;
     */
    public function getOrderInfo($orderNum){
        $this->db->select('s.increment_id, i.sku, e.sku as sku_b, i.sku as option_sku, i.product_type, 
        s.status, date_add(s.created_at, interval 8 hour) as add_time, date_add(si.created_at, interval 8 hour) as pay_time, 
        sp.method, sa.lastname, sa.firstname, sa.region, sa.city, i.parent_item_id, sa.street, sa.postcode, sa.telephone, 
        sa.email, s.shipping_amount, (s.subtotal+s.shipping_amount) as total_fee, s.subtotal, s.grand_total, i.qty_ordered, 
        i.price as goods_amount, s.is_gwp, s.is_groupon, s.presale_flag, s.rewardpoints_discount, s.total_due, s.total_qty_ordered, s.rewardpoints_spent');
        $this->db->join('sales_flat_order_item i', 'i.order_id = s.entity_id', 'left');
        $this->db->join('catalog_product_entity e', 'e.entity_id = i.product_id', 'left');
        $this->db->join('catalog_product_entity_int ci', 'e.entity_id = ci.entity_id', 'left');
        $this->db->join('sales_flat_order_payment sp', 'sp.parent_id = s.entity_id', 'left');
        $this->db->join('sales_flat_invoice si', 'si.order_id = s.entity_id', 'left');
        $this->db->join('sales_flat_order_address sa', 'sa.parent_id = s.entity_id', 'left');
        $this->db->where('ci.attribute_id = 159');
//        $this->db->where('ci.value = 36');
        $this->db->where('sa.address_type = "shipping"');
        $this->db->where('s.increment_id = "'.$orderNum.'"');
        $result= $this->db->get('sales_flat_order s');
        return $result->result_array();
    }

    function log($filename,$loginfo){
        //打开文件
        $fd = fopen($filename,"a");
        //增加文件
        $str = "[".date("Y/m/d H:i:s",time())."]".$loginfo;
        //写入字符串
        fwrite($fd, $str."\n");
        //关闭文件
        fclose($fd);
    }

    /**
     * 获取订单商品详情
     * @param $orderId
     * @return mixed
     */
    /*
     *  select i.order_id, i.sku, i.price, i.qty_ordered, i.row_total,
        i.discount_amount, i.tax_amount, i.product_options, i.sku as option_sku, e.sku as sku_b,
        i.name, i.product_id, i.product_type, i.parent_item_id, ci.value as warehouse_code, d.value as lv2price
        from sales_flat_order_item i
        left join catalog_product_entity e on i.product_id = e.entity_id
        left join catalog_product_entity_int ci on e.entity_id = ci.entity_id
        left join catalog_product_entity_decimal d on d.entity_id = e.entity_id
        where d.attribute_id = 162 and ci.attribute_id = 159 and ci.value <> 34 and i.order_id = $orderId
     */
    public function getOrderProdInfo($orderId){
        $this->db->select('i.order_id, i.sku, i.price, i.qty_ordered, i.row_total, i.item_id, i.rewardpoints_discount,
        i.discount_amount, i.tax_amount, i.product_options, i.sku as option_sku, e.sku as sku_b, 
        i.name, i.product_id, i.product_type, i.parent_item_id, ci.value as warehouse_code, i.original_price');
        $this->db->join('catalog_product_entity e', 'i.product_id = e.entity_id', 'left');
        $this->db->join('catalog_product_entity_int ci', 'e.entity_id = ci.entity_id', 'left');
        $this->db->where('ci.attribute_id = 159');
//        $this->db->where('ci.value = 36');
        $this->db->where('i.order_id = '.$orderId);
        $result= $this->db->get('sales_flat_order_item i');
        return $result->result_array();
    }

    public function getOptionProdLv1Price($sku){
        $this->db->select('d.value as price');
        $this->db->join('catalog_product_entity e', 'd.entity_id = e.entity_id', 'left');
        $this->db->where('d.attribute_id = 162');
        $this->db->where('e.sku = "'.$sku.'" ' );
        $result= $this->db->get('catalog_product_entity_decimal d');
        return $result->result_array();
    }

    /**
     * 获取OPTION商品的LV2价格
     * @param $sku
     * @return mixed
     */
    public function getOptionProdInfo($sku){
        $this->db->select('d.value as price');
        $this->db->join('catalog_product_entity e', 'd.entity_id = e.entity_id', 'left');
        $this->db->where('d.attribute_id = 75');
        $this->db->where('e.sku = "'.$sku.'" ' );
        $result= $this->db->get('catalog_product_entity_decimal d');
        return $result->result_array();
    }

    /**
     * 获取BUNDLE商品价格
     * @param $v
     * @param $localBundleProductId
     * @param $orderId
     * @return mixed
     */
    public function getBundlePrice($v,$localBundleProductId,$orderId){
        $this->db->select('selection_share_price_type, selection_share_price_value, cpi.sku as sku');
        $this->db->from('catalog_product_bundle_selection bs');
        $this->db->join('sales_flat_order_item cpi', 'cpi.product_id = bs.parent_product_id','left');
        $this->db->where('bs.product_id = '.$v['product_id']);
        $this->db->where('bs.parent_product_id = '.$localBundleProductId);
        $this->db->where('cpi.order_id = '.$orderId);
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    public function updateEavEntityStore(){
        $this->db->update('eav_entity_store s');
        $this->db->join('eav_entity_type t', 's.entity_type_id = t.entity_type_id','inner');
        $this->db->set('increment_last_id = increment_last_id + 1');
        $this->db->where('t.entity_type_code = "shipment" ');
        $this->db->where('s.store_id = 1 ');
    }

    /*  select r.order_increment_id, r.customer_id, r.shipping_name, a.region, a.city, a.street, a.postcode, a.telephone,
        a.email, sp.method, t.title, r.express_name, r.express_number, r.created_at, r.return_description, r.order_item_id
        r.qty_requested, c.sku from rma r
        left join sales_flat_order s on s.entity_id = r.order_id
		left join sales_flat_order_address a on a.parent_id = s.entity_id
		left join sales_flat_order_payment sp on sp.parent_id = s.entity_id
		left join sales_flat_shipment_track t on t.order_id = s.entity_id
        left join meme_refund_order m on r.entity_id = m.aftersale_id and m.order_increment_id = r.order_increment_id
        left join sales_flat_order_item i on i.item_id = r.order_item_id
    	left join catalog_product_entity c on c.entity_id = i.product_id
		where a.address_type = 'billing' and r.status = 'complete' group by m.aftersale_id;
    */
    public function getReturnOrderList($lastUpdated=null,$limitNum=null,$limitStart=null,$returnStatus,$orderNum){
        $this->db->select('r.order_increment_id, r.customer_id, r.shipping_name, a.region, a.city, a.street, a.postcode, a.telephone, 
        a.email, sp.method, t.title, r.express_name, r.express_number, date_add(r.created_at, interval 8 hour) as created_at, 
        r.return_description, r.order_item_id, r.qty_requested, c.sku as sku_b, i.sku, i.price as amount, r.increment_id,
        i.rewardpoints_discount, i.discount_amount, s.subtotal, s.grand_total, s.rewardpoints_spent, s.total_qty_ordered, s.shipping_amount,
        i.row_total, i.qty_ordered, i.order_id, i.product_type, i.product_id, i.tax_amount');
        $this->db->join('sales_flat_order s', 's.entity_id = r.order_id', 'left');
        $this->db->join('sales_flat_order_address a', 'a.parent_id = s.entity_id', 'left');
        $this->db->join('sales_flat_order_payment sp', 'sp.parent_id = s.entity_id', 'left');
        $this->db->join('sales_flat_shipment_track t', 't.order_id = s.entity_id', 'left');
        $this->db->join('sales_flat_order_item i', 'i.item_id = r.order_item_id', 'left');
        $this->db->join('catalog_product_entity c', 'c.entity_id = i.product_id', 'left');
        $this->db->where('a.address_type = "billing" ');
        if ($orderNum){
            $this->db->where('r.increment_id = "'.$orderNum.'" ');
        }
        $this->db->where('r.status = "'.$returnStatus.'" ');
        if ($lastUpdated) {
            $this->db->where('date_add(r.created_at, interval 8 hour) >= "'.$lastUpdated.'"');
        }
        if(($limitNum > 0) || ($limitStart > 0))
        {
            $this->db->limit($limitNum,$limitStart);
        }
        $this->db->order_by('r.increment_id desc');
        $result= $this->db->get('rma r');
        return $result->result_array();
    }

    public function getReturnOrderListCount($lastUpdated,$returnStatus,$orderNum){
        $this->db->select('r.order_increment_id, r.customer_id, r.shipping_name, a.region, a.city, a.street, a.postcode, a.telephone, 
        a.email, sp.method, t.title, r.express_name, r.express_number, date_add(r.created_at, interval 8 hour) as created_at, 
        r.return_reason, r.return_description, r.order_item_id, r.qty_requested, c.sku, i.price as amount, r.increment_id');
        $this->db->join('sales_flat_order s', 's.entity_id = r.order_id', 'left');
        $this->db->join('sales_flat_order_address a', 'a.parent_id = s.entity_id', 'left');
        $this->db->join('sales_flat_order_payment sp', 'sp.parent_id = s.entity_id', 'left');
        $this->db->join('sales_flat_shipment_track t', 't.order_id = s.entity_id', 'left');
        $this->db->join('sales_flat_order_item i', 'i.item_id = r.order_item_id', 'left');
        $this->db->join('catalog_product_entity c', 'c.entity_id = i.product_id', 'left');
        $this->db->where('a.address_type = "billing" ');
        if ($orderNum){
            $this->db->where('r.increment_id = "'.$orderNum.'" ');
        }
        $this->db->where('r.status = "'.$returnStatus.'" ');
        if ($lastUpdated) {
            $this->db->where('date_add(r.created_at, interval 8 hour) >= "'.$lastUpdated.'"');
        }
        $this->db->order_by('r.increment_id desc');
        $result= $this->db->get('rma r');
        return $result->num_rows();
    }

    public function getSendOutOrder(){
        $this->db->select('o.entity_id, o.increment_id, telephone, region, city, o.is_groupon');
        $this->db->join('sales_flat_order_address a', 'a.entity_id = o.shipping_address_id', 'left');
        $this->db->join('sales_flat_order_status_history h', 'o.entity_id = h.parent_id', 'left');
        $this->db->where('h.status="complete" ');
        $this->db->where('h.is_customer_notified = "0" ');
        $this->db->where_not_in('o.warehouse', array(1,3,4,8));
        $this->db->where('h.created_at >= date_add(now(), interval -3 day)');
        $this->db->where('h.created_at < date_add(now(), interval -6 hour)');
        $result= $this->db->get('sales_flat_order o');
        return $result->result_array();
    }


    /**获取bundle子产品
     * @param $bundleSku
     * @return mixed
     */
    public function getSubProdItems($bundleSku,$refundId){
        $this->db->select('e1.sku, e1.entity_id as product_id');
        $this->db->join('catalog_product_entity e', 's.parent_product_id = e.entity_id', 'left');
        $this->db->join('catalog_product_entity e1', 's.product_id = e1.entity_id', 'left');
        $this->db->join('sales_flat_order_item i', 'i.sku = e1.sku', 'left');
        $this->db->join('sales_flat_order o', 'o.entity_id = i.order_id', 'left');
        $this->db->join('rma r', 'r.order_id = o.entity_id', 'left');
        $this->db->where('e.sku="'.$bundleSku.'" ');
        $this->db->where('r.increment_id="'.$refundId.'" ');
        $result= $this->db->get('catalog_product_bundle_selection s');
        return $result->result_array();
    }

    public function getGiftProdItems($returnOrderId){
        $this->db->select('g.sku');
        $this->db->join('rma r', 'r.entity_id = g.rma_id', 'left');
        $this->db->where('r.increment_id="'.$returnOrderId.'" ');
        $result= $this->db->get('rma_gift g');
        return $result->result_array();
    }

}