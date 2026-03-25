<?php
/**
 * Created by Yang.
 * User: pc
 * Date: 2016/3/21
 * Time: 20:07
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'merch/core/inc/page_merch.php';
class Index_EweiShopV2Page extends MerchWebPage
{

    function main()
    {
        global $_W;

        include $this->template();
    }

    public function ajaxgettotalprice()
    {

        global $_W;
        $merchid = $_W['merchid'];

        $totals = $this->model->getMerchOrderTotalPrice($merchid);

        if ($totals['status0'] < 0){
            $totals['status0'] = 0;
        }
        show_json(1,$totals);

    }

    public function ajaxgettotalcredit(){
        global $_W;
        $merchid = $_W['merchid'];
        $totals = $this->model->getMerchCreditTotalPrice($merchid);
//
        show_json(1,$totals);
    }

}