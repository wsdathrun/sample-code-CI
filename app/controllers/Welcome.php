<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
//	    $data['orders'] = array(
//            '100001','100002','100003'
//        );
//        $data['count'] = 34;
//        $data['name'] = "王义砚";
//        $data['phone'] = "15221778995";
//        $data['grade'] = "铂金蜜米";
//		$this->load->view('nengview',$data);

//        $tokenData = array(
//            'order_sn'      => $this->post('oid'),
//            'shipping_sn'   => $this->post('shipping_sn'),
//            'shipping_code' => $this->post('shipping_code')
//        );
//        $orderNum     = $tokenData['order_sn'];
//        $shippingNum  = $tokenData['shipping_sn'];
//        $shippingCode = 'yuantong';

        // 调用magentoApi更新库存
        $url = $this->config->item('magento_server').'/service/erpapi/deliveryFeedback';

        $post_data = array(
            'order_sn'      =>  "101307966",
            'shipping_sn'   =>  "885260050030500303",
            'shipping_code' =>  "yuantong",
        );
       

        $result = $this->extend->curl_post($url,$post_data);
        var_dump($result);
	}
}
