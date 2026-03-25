<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Sn_EweiShopV2Page extends PluginWebPage
{


    public function main()
    {
        global $_W;

        $type = 'suning';
        $sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE `uniacid` = :uniacid ORDER BY `parentid`, `displayorder` DESC';

        $category = m('shop')->getFullCategory(true, true);

        include $this->template('goodshelper/index');

    }

}
