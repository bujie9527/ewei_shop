<?php


if (!defined('IN_IA')) {
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'merch/core/inc/page_merch.php';

class One688_EweiShopV2Page extends MerchWebPage
{

    /**
     * @author 徐子轩
     */
    public function main()
    {
        global $_W;

        $type = 'alibaba';
        $sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE `uniacid` = :uniacid ORDER BY `parentid`, `displayorder` DESC';

        $category = m('shop')->getFullCategory(true, true);

        include $this->template('goodshelper/index');

    }

}
