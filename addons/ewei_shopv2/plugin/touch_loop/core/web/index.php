<?php

/*
 * 人人商城
 *
 * 青岛易联互动网络科技有限公司
 * http://www.we7shop.cn
 * TEL: 4000097827/18661772381/15865546761
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
/**
 * Class Index_EweiShopV2Page
 * @property Touch_loopModel $model 一键发圈model
 */
class Index_EweiShopV2Page extends PluginWebPage
{
    /**
     * 获取列表
     */
    public function main()
    {
        global $_GPC, $_W;
        $page = max(1, $_GPC['page']);
        $conditon = '';
        if (!empty($_GPC['status'])) {
            $conditon .= ' and t.status=' . $_GPC['status'];
        }
        if (!empty($_GPC['isrecommand'])) {
            $conditon .= ' and t.isrecommand=' . ($_GPC['isrecommand'] - 1);
        }
        if (!empty($_GPC['keyword'])) {
            $conditon .= ' and g.title like %' . $_GPC['keyword'] . '%';
        }
        $list = $this->model->getList($_W['uniacid'], array('andWhere' => $conditon), $page);
        include $this->template();
    }

    /**
     * 删除
     */
    public function delete()
    {
        global $_GPC, $_W;
        $id = $this->model->getId($_GPC);
        foreach ($id as $item) {
            $list = pdo_delete('ewei_shop_touch_loop_list', array('id' => $item, 'uniacid' => $_W['uniacid']));
        }
        show_json(1, '删除成功');
    }

    /**
     *  修改状态
     */
    public function status()
    {
        global $_W, $_GPC;
        $id = $this->model->getId($_GPC);
        $status = (int)$_GPC['status'];
        foreach ($id as $item) {
            pdo_update('ewei_shop_touch_loop_list', array('status' => $status), array('id' => $item));
        }
        show_json(1, '操作成功');
    }

    /**
     * 是否推荐
     */
    public function isrecommand()
    {
        global $_W, $_GPC;
        $id = $this->model->getId($_GPC);
        $isrecommand = ((int)$_GPC['isrecommand']);
        foreach ($id as $item) {
            pdo_update('ewei_shop_touch_loop_list', array('isrecommand' => $isrecommand), array('id' => $item));
        }
        show_json(1, '操作成功');
    }

    /**
     * 新增
     */
    public function add()
    {
        $this->post();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $this->post();
    }

    /**
     * 新增更改的动作
     */
    public function post()
    {
        global $_W, $_GPC;
        if (!$_W['ispost']) {
            if ($_GPC['id'] > 0) {
                /**
                 * @var $retult array ['item','goods']
                 */
                $retult = $this->model->getInfo($_W['uniacid'], $_GPC['id']);
                list($item, $goods) = $retult;
            }
        }
        if ($_W['ispost']) {
            $data = [];
            $data['uniacid'] = $_W['uniacid'];
            $data['sort'] = $_GPC['sort'];
            $data['goods_id'] = $_GPC['goodsid'];
            $data['thumbs'] = serialize($_GPC['thumbs']);
            $data['content'] = ($_GPC['content']);
            $data['isrecommand'] = (int)($_GPC['isrecomand']);
            $data['status'] = (int)($_GPC['status']);
            if ($_GPC['id'] > 0) {
                $ret = $this->model->updateRecord(array('id' => $_GPC['id']), $data);
            } else {
                $ret = $this->model->create($data);
            }
            if ($ret) {
                show_json(1, '操作成功');
            }
            show_json(0, '未知错误或数据没变化');
        }
        include $this->template();
    }

    /**
     * 查询商品
     */
    public function query()
    {
        global $_W, $_GPC;
        $uniacid = intval($_W['uniacid']);
        $kwd = trim($_GPC['keyword']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 8;
        $params = array();
        $params[':uniacid'] = $uniacid;
        $condition = " and deleted=0 and uniacid=:uniacid and status = 1 and merchid = 0 and type != 30 ";
        if (!empty($kwd)) {
            $condition .= " AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)";
            $params[':keywords'] = "%{$kwd}%";
        }
        $goods = pdo_fetchall('SELECT id,title,thumb,thumb_url,marketprice,total
            FROM ' . tablename('ewei_shop_goods') . "
             WHERE 1 {$condition} and id not in (select goods_id from " . tablename('ewei_shop_touch_loop_list') . " where uniacid = :uniacid) ORDER BY displayorder DESC,id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) 
            FROM ' . tablename('ewei_shop_goods') . "
             WHERE 1 {$condition} and id not in (select goods_id from " . tablename('ewei_shop_touch_loop_list') . " where uniacid = :uniacid) ORDER BY displayorder DESC,id DESC ", $params);
        foreach ($goods as &$item) {
            $item['thumb_url'] = unserialize($item['thumb_url']);
            $m = [];
            foreach ($item['thumb_url'] as $t) {
                $m['media'][] = tomedia($t);
                $m['thumb'][] = ($t);
            }
            $item['thumb_url'] = $m;
            $item['thumb_tmp'] = $item['thumb'];
        }
        unset($item);
        $pager = pagination2($total, $pindex, $psize, '', array('before' => 5, 'after' => 4, 'ajaxcallback' => 'select_page', 'callbackfuncname' => 'select_page'));
        $goods = set_medias($goods, array('thumb'));
        include $this->template();
    }

    /**
     * 修改排序
     */
    public function displayorder()
    {
        global $_GPC, $_W;
        if ($_GPC['id'] < 1) {
            show_json(0, '数据不存在');
        }
        $sort = max(0, $_GPC['value']);
        pdo_update('ewei_shop_touch_loop_list', compact('sort'), array('id' => $_GPC['id'], 'uniacid' => $_W['uniacid']));
        show_json(1, '操作成功');
    }
}
