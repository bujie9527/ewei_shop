<?php


if (!defined('IN_IA')) {
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'merch/core/inc/page_merch.php';

class Index_EweiShopV2Page extends MerchWebPage
{
    /**
     * @var $pluginModel GoodshelperModel
     */
    public $pluginModel = false;


    /**
     * @author 徐子轩
     */
    public function main()
    {
        global $_W;

        $type = 'taobao';

        $sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE `uniacid` = :uniacid ORDER BY `parentid`, `displayorder` DESC';

        $category = m('shop')->getFullCategory(true, true);

        include $this->template();
    }

    public function getPluginModel()
    {
        global $_W;
        if ($this->pluginModel) {
            return $this->pluginModel;
        }

        $this->pluginModel = p('goodshelper');
        $this->pluginModel->merchid = $_W['merch_user']['id'];
        if (empty($_W['merch_user']['goodschecked'])) {
            //需要审核,只要修改过数据，就重新审核
            $checked = 1;
        } else {
            $checked = 0;
        }
        $this->pluginModel->checked = $checked;
        return $this->pluginModel;
    }

    function fetch()
    {
        global $_GPC, $_W;
        $this->getPluginModel();
        $url = $_GPC['url'];
        $type = $_GPC['type'];
        $cates = $_GPC['cate'];
        $goodsTotal = pdo_fetchcolumn('select count(id) from ' . tablename('ewei_shop_goods') . ' where uniacid = :uniacid and merchid = :merchid',array(':uniacid'=>$_W['uniacid'],'merchid'=>$_W['merch_user']['id']));

        //判断是否直接设置的商品ID，如果是的话，直接应用
        if (is_numeric($url)) {
            $goods_id = $url;
        } else {
            //如果不是，正则匹配提取商品ID
            $goods_id = $this->pluginModel->matchid($url, $type);
        }

        if ($goodsTotal +1 > $_W['merch_user']['maxgoods'] &&  $_W['merch_user']['maxgoods'] != 0)
        {
            return show_json(0,'商品超过最大数量限制');
        }
        if (empty($goods_id)) {
            show_json(0, '未获取到商品ID');
        }

        // 检测是否设置apikey
//        $set = m('common')->getSysset('goodshelper');
        $set['apikey'] = $_W['merch_user']['apikey'];
        if (empty($set['apikey'])) {
            show_json(0, '请先设置apikey');
        }

        //处理请求接口需要的数据
        $auth = get_auth();
        $_W['account'] = account_fetch($_W['uniacid']);
        if ($type == 'suning') {
            $params = [
                'type' => $type,
                'goods_id' => $goods_id['goods_id'],
                'shop_id' => $goods_id['shop_id'],
                'api_key' => $set['apikey'],
                'wechat_name' => $_W['account']['name'],
                'timestamp' => time(),
                'site_id' => (int)$auth['id'],
            ];
        } else {
            $params = [
                'type' => $type,
                'goods_id' => $goods_id,
                'api_key' => $set['apikey'],
                'wechat_name' => $_W['account']['name'],
                'timestamp' => time(),
            ];
        }

        $params['site_id'] = (int)$auth['id'];
        $params['uniacid'] = (int)$_W['uniacid'];
        $params['request_domain'] = $_SERVER['HTTP_HOST'];

        $data = $this->pluginModel->getcontent($params);

        if ($data['error'] == '-1') {
            show_json(0, $data['message']);
        }

        if ($type == 'taobao' || $type == 'tmall') {
            return $this->pluginModel->get_item_taobao($data['goods'], $cates);
        } elseif ($type == 'jd') {
            return $this->pluginModel->get_item_jd($data['goods'], $cates);
        } elseif ($type == 'alibaba') {
            return $this->pluginModel->get_item_on688($data['goods'], $cates);
        } elseif ($type == 'suning') {
            return $this->pluginModel->get_item_suning($data['goods'], $cates);
        } elseif ($type == 'pdd') {
            return $this->pluginModel->get_item_pdd($data['goods'], $cates);
        } elseif ($type == 'redbook') {
            return $this->pluginModel->get_item_redbook($data['goods'], $cates);
        }


    }

    /*
     *  设置商品助手apikey
     *  @author xzx
     */
    function set()
    {
        global $_W, $_GPC;
        $setApiKey['apikey'] = $_W['merch_user']['apikey'];

        if ($_W['ispost']) {
            $apikey = $_GPC['apikey'];
            $_W['merch_user']['apikey'] = $apikey;
            pdo_update('ewei_shop_merch_user', compact('apikey'), array('id' => $_W['merch_user']['id'], 'uniacid' => $_W['uniacid']));
            show_json(1);
        }


        include $this->template();
    }

}
