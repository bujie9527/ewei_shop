<?php
//20200615
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Esheet_EweiShopV2Page extends PluginWebPage {

    function main(){
        global $_W, $_GPC;
        // 定义 支付方式
        $paytype = array(
            '0' => array('css' => 'default', 'name' => '未支付'),
            'credit' => array('css' => 'danger', 'name' => '余额支付'),
            'wechat' => array('css' => 'success', 'name' => '微信支付'),
            'alipay' => array('css' => 'warning', 'name' => '支付宝支付')
        );
        // 定义 订单状态
        $orderstatus = array(
            '0' => array('css' => 'danger', 'name' => '待付款'),
            '1' => array('css' => 'info', 'name' => '已付款'),
            '2' => array('css' => 'warning', 'name' => '已发货'),
            '3' => array('css' => 'success', 'name' => '已完成')
        );

        if (empty($starttime) && empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $printset = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_exhelper_sys') . " WHERE uniacid=:uniacid and merchid=0 limit 1", array(':uniacid' => $_W['uniacid']));

        $lodopUrl_ip = 'localhost';
        $lodopUrl_port = empty($printset['port'])?8000:$printset['port'];
        $https = $_W['ishttps']?"https://":"http://";
        $lodopUrl = $https.$lodopUrl_ip.":".$lodopUrl_port."/CLodopfuncs.js";

        load()->func('tpl');
        include $this->template();

    }

    function getdata(){
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];


        if($_W['ispost']){

            // 定义 基础 查询条件
            $condition = " o.uniacid = :uniacid and m.uniacid = :uniacid and o.deleted=0 and o.addressid<>0 ";
            $paras = array(':uniacid' => $_W['uniacid']);

            // 获取 支付方式
            if ($_GPC['paytype'] != '') {
                    $condition .= " AND o.paytype =" . intval($_GPC['paytype']);
            }
            // 获取 订单状态
            $status = intval($_GPC['status']);
            $statuscondition = '';
            if ($status != '') {
                if ($status == '4') {
                    $statuscondition = " AND o.refundstate>0 and o.refundid<>0";
                } else if ($status == '5') {
                    $statuscondition = " AND o.refundtime<>0";
                } else if ($status=='1'){
                    $statuscondition = " AND  o.status = 1 ";
                } else if($status=='0'){
                    $statuscondition = " AND o.status = 0 and o.paytype<>3";
                } else {
                    $statuscondition = " AND o.status = ".intval($status);
                }
            }else{
                $statuscondition = " and o.status>-1 ";
            }
            // 获取 搜索时间
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }
            $searchtime = trim($_GPC['searchtime']);
            if (!empty($searchtime) && !empty($_GPC['starttime']) && !empty($_GPC['endtime']) && in_array($searchtime, array('create', 'pay', 'send', 'finish'))) {
                $starttime = strtotime($_GPC['starttime']);
                $endtime = strtotime($_GPC['endtime']);
                $condition .= " AND o.{$searchtime}time >= :starttime AND o.{$searchtime}time <= :endtime ";
                $paras[':starttime'] = $starttime;
                $paras[':endtime'] = $endtime;
            }
            // 获取 快递单打印状态
            $printstate = intval($_GPC['printstate']);
            if ($printstate!='') {
                $condition .= " AND o.printstate=".$printstate." ";
            }
            // 获取 发货单打印状态
            $printstate2 = $_GPC['printstate2'];
            if ($printstate2!='') {
                $condition .= " AND o.printstate2=".$printstate2." ";
            }
            $sqlcondition = '';
            // 获取关键字 与 查询类型
            if (!empty($_GPC['searchfield']) && !empty($_GPC['keyword'])) {
                $searchfield = trim(strtolower($_GPC['searchfield']));
                $keyword = trim($_GPC['keyword']);
                if ($searchfield == 'ordersn') {
                    $condition .= " AND o.ordersn LIKE '%{$keyword}%'";
                } else if ($searchfield == 'member') {
                    $condition .= " AND (m.realname LIKE '%{$keyword}%' or m.mobile LIKE '%{$keyword}%' or m.nickname LIKE '%{$keyword}%')";
                } else if ($searchfield == 'address') {
                    $condition .= " AND ( a.realname LIKE '%{$keyword}%' or a.mobile LIKE '%{$keyword}%' or o.carrier LIKE '%{$keyword}%' )";
                } else if ($searchfield == 'expresssn') {
                    $condition .= " AND o.expresssn LIKE '%{$keyword}%'";
                }
            }

            $sql = "select o.* ,a.realname ,m.nickname, d.dispatchname,m.nickname,r.refundstatus as refundstatus from " . tablename('ewei_shop_groups_order') . " o"
                . " left join " . tablename('ewei_shop_groups_order_refund') . " r on r.orderid=o.id and ifnull(r.refundstatus,-1)<>-1 and ifnull(r.refundstatus,-1)<>-2"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " left join " . tablename('ewei_shop_dispatch') . " d on d.id = o.dispatchid "
                .$sqlcondition. " where $condition $statuscondition ORDER BY o.createtime DESC,o.status DESC  ";
            $orders = pdo_fetchall($sql, $paras);



            //print_r($orders);
            $list = array();
            foreach ($orders as $order) {
                if(!empty($order['address_send'])){
                    $order_address = iunserializer($order['address_send']);
                }else{
                    $order_address = iunserializer($order['address']);
                }


                if(!is_array($order_address)){
                    $member_address = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id=:id and uniacid=:uniacid limit 1", array(':id'=>$order['addressid'],':uniacid'=>$_W['uniacid']));
                    $addresskey = $member_address['realname'] . $member_address['mobile'] . $member_address['province'] . $member_address['city'] . $member_address['area'] . $member_address['address'];
                }else{
                    $addresskey = $order_address['realname'] . $order_address['mobile'] . $order_address['province'] . $order_address['city'] . $order_address['area'] . $order_address['address'];
                }

                if (!isset($list[$addresskey])) {
                    $list[$addresskey] = array('realname' => $order_address['realname'], 'orderids' => array());
                }
                $list[$addresskey]['orderids'][] = $order['id'];
            }

            include $this->template('exhelper/esheetprint/single/print_tpl');
        }
    }



    function getorder(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $orderids = trim($_GPC['orderids']);



            if (empty($orderids)) {
                die('无任何订单，无法查看');
            }
            $arr = explode(',', $orderids);
            if (empty($arr)) {
                die('无任何订单，无法查看');
            }
            $paytype = array('0' => array('css' => 'default', 'name' => '未支付'),'credit' => array('css' => 'danger', 'name' => '余额支付'),'11' => array('css' => 'default', 'name' => '后台付款'),'2' => array('css' => 'danger', 'name' => '在线支付'),
                'wechat' => array('css' => 'success', 'name' => '微信支付'),'alipay' => array('css' => 'warning', 'name' => '支付宝支付'),'23' => array('css' => 'warning', 'name' => '银联支付'),'3' => array('css' => 'primary', 'name' => '货到付款'),
            );
            $orderstatus = array(
                '-1' => array('css' => 'default', 'name' => '取消'),'0' => array('css' => 'danger', 'name' => '待付款'),'1' => array('css' => 'info', 'name' => '已付款'),
                '2' => array('css' => 'warning', 'name' => '已发货'),'3' => array('css' => 'success', 'name' => '已完成')
            );

            $sql = "select o.* , a.realname,a.mobile,a.province,a.city,a.area,a.street, d.dispatchname,m.nickname,r.refundstatus as refundstatus from " . tablename('ewei_shop_groups_order') . " o"
                . " left join " . tablename('ewei_shop_groups_order_refund') . " r on r.orderid=o.id and ifnull(r.refundstatus,-1)<>-1"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " left join " . tablename('ewei_shop_dispatch') . " d on d.id = o.dispatchid "
                . " where o.id in ( " . implode(',', $arr) . ") and o.uniacid={$_W['uniacid']} and m.uniacid={$_W['uniacid']} ORDER BY o.createtime DESC,o.status DESC  ";



            $list = pdo_fetchall($sql, $paras);


            foreach ($list as &$value) {
                $s = $value['status'];
                $value['statusvalue'] = $s;
                $value['statuscss'] = $orderstatus[$s]['css'];
                $value['statusname'] = $orderstatus[$s]['name'];

                if ($s == -1) {
                    if ($value['refundstatus'] == 1) {
                        $value['status'] = '已退款';
                    }
                }

                $p = $value['pay_type'];
                $value['css'] = $paytype[$p]['css'];
                $value['paytypename'] = $paytype[$p]['name'];
                $value['dispatchname'] = empty($value['addressid']) ? '自提' : $value['dispatchname'];
                if (empty($value['dispatchname'])) {
                    $value['dispatchname'] = '快递';
                }

                if ($value['isverify'] == 1) {
                    $value['dispatchname'] = "线下核销";
                }


                $addressa = iunserializer($value['address']);

                if (is_array($addressa)) {
                    $value['realname'] = $addressa['realname'];
                    $value['mobile'] = $addressa['mobile'];
                    $value['province'] = $addressa['province'];
                    $value['city'] = $addressa['city'];
                    $value['area'] = $addressa['area'];
                    $value['address'] = $addressa['address'];
                    $value['street'] = $addressa['street'];
                }
                $value['address'] = array(
                    'realname' => $value['realname'],
                    'nickname' => $value['nickname'],
                    'mobile' => $value['mobile'],
                    'province' => $value['province'],
                    'city' => $value['city'],
                    'area' => $value['area'],
                    'address' => $value['street'].$value['address'],
                );

                if($value['status']==1){
                    $value['send_status'] = 1;
                }else{
                    $value['send_status']  = 0;
                }



                //订单商品
                if (empty($value['more_spec'])){
                    $order_goods = pdo_fetchall('select * from ' . tablename('ewei_shop_groups_goods') . ' where uniacid=:uniacid and id=:goodid ', array(':uniacid' => $_W['uniacid'], ':goodid' => $value['goodid']));



                }else{

                    $order_goods = pdo_fetchall('select og.*,g.* from ' . tablename('ewei_shop_groups_order_goods') . ' og LEFT JOIN ' . tablename('ewei_shop_groups_goods') . ' g on g.id = og.groups_goods_id '.' where og.uniacid=:uniacid and og.groups_order_id=:orderid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $value['id']));

                }





                $goods = '';

                foreach ($order_goods as &$og) {
                    $goods.="" . $og['title'] . "\r\n";
                    if (!empty($og['option_name'])) {
                        $goods.=" 规格: " . $og['option_name'];
                    }
                    if (!empty($og['goodssn'])) {
                        $goods.=' 商品编号: ' . $og['goodssn'];
                    }
                    if (!empty($og['productsn'])) {
                        $goods.=' 商品条码: ' . $og['productsn'];
                    }
                    $goods.=' 单价: ' . ($og['price']) . ' 拼团后: ' . ($og['groupsprice']) . "\r\n ";

                }
                unset($og);

                $value['goods'] = set_medias($order_goods, 'thumb');
                $value['goods_str'] = $goods;
            }
            unset($value);



            $address = false;
            if (!empty($list)) {
                $address = $list[0]['address'];
            }

            // 处理 发货信息
            $address['sendinfo'] = '';
            $sendinfo = array();


            foreach($list as $item){
                foreach($item['goods'] as $k=>$g){
                    if( isset($sendinfo[$g['id']])) {
                        $sendinfo[$g['id']]['num'] = 1;
                    }else{
                        $sendinfo[$g['id']] = array('title'=>empty($g['shorttitle'])?$g['title']:$g['shorttitle'],'num'=>1,'optiontitle'=>!empty($g['option_name'])?'('.$g['option_name'].')':'');
                    }
                }
            }

            $sendinfos = array();
            foreach($sendinfo as $gid => $info){
                $info['gid'] = $gid;
                $sendinfos[] = $info;
                $address['sendinfo'].=$info['title'].$info['optiontitle'].' x '.$info['num'].'; ';
            }




            $temps = $this->model->getTemp();

            extract($temps);




            include $this->template();
        }
    }







}