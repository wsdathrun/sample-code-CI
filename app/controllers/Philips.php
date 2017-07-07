<?php
/**
 * Created by PhpStorm.
 * User: philips
 * Date: 17/3/22
 * Time: 下午4:45
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Philips extends CI_Controller {

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
//        $str = array(
//            "test"  =>  0,
//        );
//        var_dump ('0' == '')  ;
        $a=array(6, 42, 11, 7, 1, 42);

        var_dump($this->solution(5, 6, $a));
    }

//    protected function solution($A,$B) {
//            // write your code in PHP7.0
//            $dec = $A * $B;
//            $res = 0;
//            if($dec == 0)
//            {
//                return $res;
//            }
//            $i = 1;
//            while(pow(2,$i) <= $dec)
//            {
//                $left = $dec % pow(2,$i);
//                if ($left >= pow(2,$i - 1))
//                {
//                    $res++;
//                }
//                $i ++;
//            }
//            $res++;
//            return $res;
//    }
    protected function solution($X, $Y, &$A) {
        $N = sizeof($A);
        $result = -1;
        $nX = 0;
        $nY = 0;
        for ($i = 0; $i < $N; $i++) {
            if ($A[$i] == $X)
                $nX += 1;
            else if ($A[$i] == $Y)
                $nY += 1;
            if ($nX == $nY)
                $result = $i;
        }
        return $result;
    }
}