<?php
/**
 * Created by PhpStorm.
 * User: Eathan
 * Date: 17/1/16
 * Time: 下午6:38
 */

class Ntalker_model extends CI_Model
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
    public function getCustomerInfo($customerId){
        $this->db->select('e.entity_id, concat(lastname,firstname) as name, date_add(s.created_at, interval 8 hour) as order_created_time,
             p.phone, s.increment_id, mc.chinese_grade, s.status');
        $this->db->join('customer_phone_auth p', 'p.customer_id = e.entity_id', 'left');
        $this->db->join('sales_flat_order s', 's.customer_id = e.entity_id', 'left');
        $this->db->join('sales_flat_order_address a', 'a.parent_id = s.entity_id', 'left');
        $this->db->join('memeclub_grade_tracking mt', 'mt.customer_id = e.entity_id', 'left');
        $this->db->join('memeclub_grade_config mc', 'mc.id = mt.grade_id', 'left');
        $this->db->where('a.address_type = "billing"');
        $this->db->where('s.status not in ("canceled","closed")');
        $this->db->where('s.increment_id not in (select order_id from activity_groupon_order as g where g.status = "failed" or g.status = "processing")');
        $this->db->where('e.entity_id ', intval($customerId));
        $this->db->order_by('increment_id desc');
        $result= $this->db->get('customer_entity e');
        return $result->result_array();
    }


    /**
     * 获取订单详情
     * @param $orderNum
     * @return mixed
     *
     */
    public function getOrderInfo($customerId){
        $this->db->select('s.increment_id, date_add(s.created_at, interval 8 hour), s.status, i.name, st.track_number');
        $this->db->join('sales_flat_order_item i', 'i.order_id = s.entity_id', 'left');
        $this->db->join('sales_flat_shipment ss', 'ss.order_id = s.entity_id', 'left');
        $this->db->join('sales_flat_shipment_track st', 'st.parent_id = ss.entity_id', 'left');
        $this->db->where('s.status not in ("canceled","closed") ');
        $this->db->where('e.entity_id ', intval($customerId));
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

    public function getOptionProdLv1Price($sku){
        $this->db->select('d.value as price');
        $this->db->join('catalog_product_entity e', 'd.entity_id = e.entity_id', 'left');
        $this->db->where('d.attribute_id = 162');
        $this->db->where('e.sku = "'.$sku.'" ' );
        $result= $this->db->get('catalog_product_entity_decimal d');
        return $result->result_array();
    }


    public function getGiftProdItems($returnOrderId){
        $this->db->select('g.sku');
        $this->db->join('rma r', 'r.id = g.rma_id', 'left');
        $this->db->where('r.increment_id="'.$returnOrderId.'" ');
        $result= $this->db->get('rma_gift g');
        return $result->result_array();
    }

}