<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage
{


    public function main()
    {
        global $_W;

        $type = 'taobao';

        $sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE `uniacid` = :uniacid ORDER BY `parentid`, `displayorder` DESC';

        $category = m('shop')->getFullCategory(true, true);

        include $this->template();
    }


    function fetch()
    {
        global $_GPC, $_W;
        $url = $_GPC['url'];
        $type = $_GPC['type'];
        $cates = $_GPC['cate'];


        if (is_numeric($url)) {
            $goods_id = $url;
        } else {
        
            $goods_id = $this->model->matchid($url, $type);
        }


        if (empty($goods_id)) {
           show_json(0, '未获取到商品ID');
        }

     
        $set = m('common')->getSysset('goodshelper');
        if (empty($set['apikey'])) {
            show_json(0, '请先设置apikey');
        }

    
        $auth = get_auth();

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

        $data = $this->model->getcontent($params);


        if ($data['error'] == '-1') {
            show_json(0, $data['message']);
        }

        if ($type == 'taobao' || $type == 'tmall') {
            return $this->model->get_item_taobao($data['goods'], $cates);
        } elseif ($type == 'jd') {
            return $this->model->get_item_jd($data['goods'], $cates);
        } elseif ($type == 'alibaba') {
            return $this->model->get_item_on688($data['goods'], $cates);
        } elseif ($type == 'suning') {
            return $this->model->get_item_suning($data['goods'], $cates);
        } elseif ($type == 'pdd') {
            return $this->model->get_item_pdd($data['goods'], $cates);
        } elseif ($type == 'redbook') {
            return $this->model->get_item_redbook($data['goods'], $cates);
        }


    }


    function set()
    {
        global $_W, $_GPC;

        $setApiKey = m('common')->getSysset('goodshelper');


        if ($_W['ispost']) {
            $status = $_GPC['apikey'];

            $data = array();
            $data['apikey'] = $status;
            m('common')->updateSysset(array('goodshelper' => $data));
            show_json(1);
        }


        include $this->template();
    }

}
