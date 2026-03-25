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
 * 直播间列表
 * Class Room_EweiShopV2Page
 */
class Room_EweiShopV2Page extends PluginWebPage
{

    /**
     * @author likexin
     */
    function main()
    {
        global $_GPC, $_W;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $condition = ' uniacid = :uniacid';
        $params = array(':uniacid' => $_W['uniacid']);

        // 处理查询条件
        $keywords = trim($_GPC['keywords']);
        if ($keywords !== '') {
            $condition .= ' AND (`name` LIKE :keyword OR `anchor_name` LIKE :keyword)';
            $params[':keyword'] = "%{$keywords}%";
        }

        $isRecommend = trim($_GPC['is_recommend']);
        if ($isRecommend !== '') {
            $condition .= ' AND is_recommend = '. intval($isRecommend);
        }

        $status = trim($_GPC['status']);
        if ($status == 1) {
            $condition .= ' AND local_live_status = 0';
        }else if ($status == 2){
            $condition .= ' AND local_live_status = -1';
        }else if($status == 3){
            $condition .= ' AND local_live_status = 1';
        }
        $isTop = trim($_GPC['is_top']);
        if ($isTop !== '') {
            $condition .= ' AND is_top = '. intval($isTop);
        }

        // 处理排序
        $condition .= " ORDER BY `room_id` DESC";

        // 查询列表
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_wxlive') . " WHERE 1 and {$condition}"  . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);

        if (!empty($list)) {
            // 处理直播状态
            $list = $this->model->handleStatus($list);
        }

        // 查询总数
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_wxlive') . " WHERE 1 and {$condition}", $params);

        $pager = pagination2($total, $pindex, $psize);

        include $this->template();
    }

    /**
     * 同步直播间
     * @author likexin
     */
    function sync()
    {
        global $_W;

        $ret = $this->model->syncRoomList($_W['uniacid']);
        if (is_error($ret)) {
            show_json(0, $ret['message']);
        }
        show_json(1);
    }

    /**
     * 快速切换状态
     * @author likexin
     */
    function property()
    {
        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        $type = trim($_GPC['type']);
        $value = intval($_GPC['value']);

        if (!in_array($type, array('status', 'is_top', 'is_recommend'))) {
            show_json(0, '错误的请求');
        }

        $statusstr = "";
        if ($type == 'status') {
            $typestr = "状态";
            $statusstr = $value == 1 ? '显示' : '关闭';
        } else if ($type == 'displayorder') {
            $typestr = "排序";
            $statusstr = "序号 {$value}";
        } else if ($type == 'hot') {
            $typestr = "是否热门";
            $statusstr = $value == 1 ? '是' : '否';
        } else if ($type == 'recommend') {
            $typestr = "是否推荐";
            $statusstr = $value == 1 ? '是' : '否';
        }

        if (empty($id) && is_array($_GPC['ids'])) {
            foreach ($_GPC['ids'] as $item) {
                pdo_update("ewei_shop_wxlive", array($type => $value), array("id" => $item, "uniacid" => $_W['uniacid']));
                plog('wxlive.room.edit', "修改直播{$typestr}状态   ID: {$item} {$statusstr} ");
            }
        } else {
            pdo_update("ewei_shop_wxlive", array($type => $value), array("id" => $id, "uniacid" => $_W['uniacid']));
            plog('wxlive.room.edit', "修改直播{$typestr}状态   ID: {$id} {$statusstr} ");
        }

        show_json(1);
    }

}
