<?php
/**
 * Created by PhpStorm.
 * User: philips
 * Date: 17/1/21
 * Time: 下午10:35
 */

class Item_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Utility_model','utility');

    }

    /**
     * 获取商品列表记录数
     * @param $inputData
     * @return mixed
     */
    /*
     * select count(1) from catalog_product_entity cpe
        left join catalog_product_entity_int wh on wh.entity_id = cpe.entity_id
        and wh.attribute_id = 159
        and wh.value = 36
    */
    public function getItemPageInfo($inputData)
    {
        $this->db->select('cpe.entity_id');
        $this->_whereItem();
        if(!is_null($inputData['skuSns']))
        {
            $this->db->where_in('cpe.sku',$inputData['skuSns']);
        }
        
        $this->db->order_by('cpe.entity_id','ASC');
        $result= $this->db->get('catalog_product_entity cpe');
        $total = $result->num_rows();
        $pageInfo= array(
            'current_page'  =>  $inputData['page'],
            'pages'         =>  ceil($total / $inputData['pageSize']),
            'page_size'     =>  $inputData['pageSize'],
            'total'         =>  $total,
        );
        return $pageInfo;
    }

    /**
     * 获取商品列表数据
     * @param $inputData
     * @return mixed
     */
    public function getItemList($inputData)
    {
        $urlPrefix = $this->utility->getItemUrlPrefix() . ITEM_URL;
//        $this->db->select('concat("'.$urlPrefix.'",cpe.entity_id) as goods_url,cpe.sku as goods_sn,
//(select value from catalog_product_entity_varchar n where n.entity_id = cpe.entity_id and n.attribute_id = 71
//) as goods_name,
//(select value from catalog_product_entity_decimal mp where mp.entity_id = cpe.entity_id and mp.attribute_id = 162
//) as market_price,
//(select value from catalog_product_entity_decimal sp where sp.entity_id = cpe.entity_id and sp.attribute_id = 75
//) as shop_price,
//(select value from catalog_product_entity_int en where en.entity_id = cpe.entity_id and en.attribute_id = 96
//) as is_on_sell,
//(select attr.code from catalog_product_entity_int cat
//left join memebox_catalog_product_base_attribute attr on cat.value = attr.entity_id where cat.entity_id = cpe.entity_id and cat.attribute_id = 385
//) as cat_id,
//(select value from catalog_product_entity_varchar b where b.entity_id = cpe.entity_id and b.attribute_id = 434
//) as barcode');
        $this->db->select('concat("'.$urlPrefix.'",cpe.entity_id) as goods_url,cpe.sku as goods_sn,
(select value from catalog_product_entity_varchar n where n.entity_id = cpe.entity_id and n.attribute_id = 71
) as goods_name,
(select value from catalog_product_entity_decimal mp where mp.entity_id = cpe.entity_id and mp.attribute_id = 162
) as market_price,
(select value from catalog_product_entity_decimal sp where sp.entity_id = cpe.entity_id and sp.attribute_id = 75
) as shop_price,
(select value from catalog_product_entity_int en where en.entity_id = cpe.entity_id and en.attribute_id = 96
) as is_on_sell,
"C001" as cat_id,
(select value from catalog_product_entity_varchar b where b.entity_id = cpe.entity_id and b.attribute_id = 434
) as barcode');
        $this->_whereItem();
        if(!is_null($inputData['skuSns']))
        {
            $this->db->where_in('cpe.sku',$inputData['skuSns']);
        }

        $this->db->limit($inputData['pageSize'],$inputData['pageSize'] * ($inputData['page'] - 1));
        $this->db->order_by('cpe.entity_id','ASC');
        $result= $this->db->get('catalog_product_entity cpe');
        return $result->result_array();
    }

    /**
     * 查找item的sql语句
     */
    private function _whereItem()
    {
        $this->db->join('catalog_product_entity_int wh', 'wh.entity_id = cpe.entity_id', 'left');
        $this->db->where('wh.attribute_id',159);
        $this->db->where('wh.value',36);
        $this->db->where('cpe.type_id','simple');
        $this->db->where('cpe.has_options',0);
    }

    /**
     * 获取单个sku数据
     * @param $inputData
     * @return mixed
     */
    public function getItemDetail($inputData)
    {
        $result = array();
        $urlPrefix = $this->utility->getItemUrlPrefix() . ITEM_URL;
//        $this->db->select('concat("'.$urlPrefix.'",cpe.entity_id) as goods_url,cpe.sku as goods_sn,
//(select value from catalog_product_entity_varchar n where n.entity_id = cpe.entity_id and n.attribute_id = 71
//) as goods_name,
//(select value from catalog_product_entity_decimal mp where mp.entity_id = cpe.entity_id and mp.attribute_id = 162
//) as market_price,
//(select value from catalog_product_entity_decimal sp where sp.entity_id = cpe.entity_id and sp.attribute_id = 75
//) as shop_price,
//(select value from catalog_product_entity_int en where en.entity_id = cpe.entity_id and en.attribute_id = 96
//) as is_on_sell,
//(select attr.code from catalog_product_entity_int cat
//left join memebox_catalog_product_base_attribute attr on cat.value = attr.entity_id where cat.entity_id = cpe.entity_id and cat.attribute_id = 385
//) as cat_id,
//(select value from catalog_product_entity_varchar b where b.entity_id = cpe.entity_id and b.attribute_id = 434
//) as barcode');
        $this->db->select('concat("'.$urlPrefix.'",cpe.entity_id) as goods_url,cpe.sku as goods_sn,
(select value from catalog_product_entity_varchar n where n.entity_id = cpe.entity_id and n.attribute_id = 71
) as goods_name,
(select value from catalog_product_entity_decimal mp where mp.entity_id = cpe.entity_id and mp.attribute_id = 162
) as market_price,
(select value from catalog_product_entity_decimal sp where sp.entity_id = cpe.entity_id and sp.attribute_id = 75
) as shop_price,
(select value from catalog_product_entity_int en where en.entity_id = cpe.entity_id and en.attribute_id = 96
) as is_on_sell,
"C001" as cat_id,
(select value from catalog_product_entity_varchar b where b.entity_id = cpe.entity_id and b.attribute_id = 434
) as barcode');
        $this->_whereItem();
        $this->db->where('cpe.sku',$inputData['sku_sn']);
        $query= $this->db->get('catalog_product_entity cpe');
        $basicInfoArray = $query->result_array();
        $result['basic'] = $basicInfoArray[0];
        $result['sku_item'] = array();
        $itemContent = array(
            'sku_sn'        =>  $basicInfoArray[0]['goods_sn'],
            'barcode_sn'    =>  $basicInfoArray[0]['barcode'],
            'goods_sn'      =>  $basicInfoArray[0]['goods_sn'],
            'ext_attr'      =>  '',
            'ext_desc'      =>  '',
            'actual_number' =>  '',
            'spec_price'    =>  '',
        );
        array_push($result['sku_item'],$itemContent);
        return $result;
    }

    public function getItemInfo($inputData){
        $this->db->select('cpe.sku, wh.value');
        $this->db->join('catalog_product_entity_int wh', 'wh.entity_id = cpe.entity_id', 'left');
        $this->db->where('wh.attribute_id = 159');
        $this->db->where('cpe.type_id','simple');
        $this->db->where('cpe.has_options',0);
        $this->db->where('cpe.sku = "'.$inputData['sku_sn'].'"');
        $result= $this->db->get('catalog_product_entity cpe');
        return $result->result_array();
    }

    /**
     * 获取magento订单中锁定的库存数 (pending，processing)
     * @param $sku
     * @return int
     */
    /*
     * select si.sku,IFNULL(sum(si.qty_ordered),0) as qty_blocked from
     sales_flat_order_item si
    left join sales_flat_order s on s.entity_id = si.order_id and si.product_type <> "bundle"
    where s.status in ("pending","processing")
    and si.sku = "C040095";;
     * */
    public function getBlockedStock($sku)
    {
        // 定义库存锁定的订单状态
        $blockStatus = array(
            'pending',
            'processing',
            'presale_waiting',
        );
        $this->db->select('si.sku,IFNULL(sum(si.qty_ordered),0) as qty_blocked');
        $this->db->join('sales_flat_order s', 's.entity_id = si.order_id', 'left');
        $this->db->where_in('s.status',$blockStatus);
        $this->db->where('si.product_type <>','bundle');
        $this->db->where('si.sku',$sku);
        $query = $this->db->get('sales_flat_order_item si');
        $result = $query->row();
        return $result->qty_blocked;
    }
}