<?php
//禁止非法访问
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Goods_selector_EweiShopV2Page extends WebPage
{
    /**
     * 商品选择器
     */
    public function main($page = 0)
    {
        global $_GPC, $_W;
        //分页
        $page = empty($page) ? max(1, (int)$_GPC['page']) : $page;
        $page_size = 8;
        $page_start = ($page - 1) * $page_size;
        $condition = '';
        if (!empty($_GPC['condition'])) {
            $condition = base64_decode(trim($_GPC['condition']));
        }
        //搜索关键字
        $params = array(':uniacid' => $_W['uniacid']);
        $keywords = trim($_GPC['keywords']);
        if (!empty($keywords)) {
            $params[':title'] = '%' . $keywords . '%';
            $keywords = "and title like :title ";
        }

        //商品分组
        $goodsgroup = intval($_GPC['goodsgroup']);
        $goodsgroup_where = '';
        if (!empty($goodsgroup)) {
            $goodsgroup_where = " and (find_in_set('{$goodsgroup}',ccates) or find_in_set('{$goodsgroup}',pcates) or find_in_set('{$goodsgroup}',tcates)) ";
        }
        //查询

        //小程序过滤批发商品
        if($_GPC['platform'] == 'wxapp'){
            $condition .= ' and type != 4 ';
        }

        //多商户
        if ((int)$_GPC['merchid']) {
            $condition .= ' and merchid = ' . (int)$_GPC['merchid'];
        }
        /*else{
            $condition .= ' and merchid = 0';
        }*/
        $limit = "limit {$page_start},{$page_size}";

        // 积分商城
        if (trim($_GPC['creditshop']) == 1) {
            $query_field = 'id,title,hasoption,price as marketprice,thumb,total, credit';
            $tablename = tablename('ewei_shop_creditshop_goods');
            $condition .= ' AND deleted=0 ';
        }

        // 拼团
        elseif (trim($_GPC['group']) == 1) {
            $query_field = 'id,title,stock as total,groupsprice as marketprice,thumb';
            $tablename = tablename('ewei_shop_groups_goods');
        }

        // 普通商品
        else {
            $query_field = 'merchid,id,title,total,hasoption,marketprice,thumb,minprice,bargain,sales';
            $tablename = tablename('ewei_shop_goods');
            $condition .= ' AND status=1  AND deleted=0 AND bargain=0 AND checked=0';
        }
//        周期购
        if($_GPC['pagetype'] !=2 && trim($_GPC['group']) != 1){
            $condition .=' AND type<>9';
        }
        // 快速购买
        if (intval($_GPC['quick']) == 1) {
            $condition .= ' AND type<>4';
        }

        $query_sql = "select {$query_field} from " . $tablename . " where uniacid = :uniacid {$condition} " . $goodsgroup_where . $keywords;
        $count_field = 'count(*)';
        $count_sql = str_replace($query_field, $count_field, $query_sql);
        $query_sql .= $limit;
        $list = pdo_fetchall($query_sql, $params);
        if (!empty($list))
            foreach ($list as &$li) {
                $li['thumb'] = tomedia($li['thumb']);
                if (empty($li['merchid'])){
                    $li['myshop'] = 1;
                }
            }
        unset($li);
        $count = pdo_fetchcolumn($count_sql, $params);
        //页码处理
        $page_num = ceil($count / $page_size);
        $total = $page_num;
        $i = 1;
        while ($page_num) {
            $page_num_arr[] = $i++;
            $page_num--;
        }
        //页码数组
        $slice = 0;
        if ($page > 6) {
            $slice = $page - 6;
        }
        is_array($page_num_arr) && $page_num_arr = array_slice($page_num_arr, $slice, 10);

        if (empty($list) && $page !== 1) {
            //当前页无数据,跳回第一页
            $this->main(1);
        } else {
            include $this->template('util/goods_selector');
        }
    }

    /**
     * 商品选项编辑器
     */
    public function op()
    {
        global $_GPC, $_W;
        $column = json_decode(htmlspecialchars_decode(urldecode(trim($_GPC['column']))), 1);
//        处理自定义项目
        if (is_array($column))
            foreach ($column as $ck => &$c) {
                if (is_string($c)) {
                    $c = array('name' => $ck, 'title' => $c);
                } elseif (is_array($c) && !empty($c['title'])) {
                    if (empty($c['name'])) {
                        $c['name'] = $ck;
                    }
                    continue;
                } else {
                    show_json(0, 'column参数不合法');
                }
            }
        $id = intval($_GPC['id']);
//        商品
        $sql = "select * from " . tablename('ewei_shop_goods') . " where id = {$id}";
        $goods = pdo_fetch($sql);
        if (empty($goods)) {
            show_json(0, '此商品已经不存在,请移除');
        }

        if (empty($_GPC['nooption'])) {
            //规格
            $sql = "select * from " . tablename('ewei_shop_goods_option') . " where goodsid = {$id}";
            $options = pdo_fetchall($sql);
        }
        include $this->template('util/goods_selector_op');
    }

    public function js()
    {
        global $_W;
        include $this->template('util/goods_selector_js');
    }

    //获取商品分类
    public function getcate()
    {
        global $_W;
        if (empty($_W['import']))
        {
            $category = m('shop')->getAllCategory();
        }
        else
        {
            $category = m('shop')->getFullCategory(true, true);

        }
        /*$category = array_filter($category,function ($v){
            if ($v['parentid'] == 0)return 1;
            return 0;
        });*/
        header('Content-type: application/json');
        exit(json_encode($category));
    }
}