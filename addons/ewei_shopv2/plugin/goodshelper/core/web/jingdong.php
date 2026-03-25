<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Jingdong_EweiShopV2Page extends PluginWebPage
{


    public function main()
    {
        global $_W;

        $type = 'jd';
        $sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE `uniacid` = :uniacid ORDER BY `parentid`, `displayorder` DESC';

        $category = m('shop')->getFullCategory(true, true);

        include $this->template('goodshelper/index');

    }

}
