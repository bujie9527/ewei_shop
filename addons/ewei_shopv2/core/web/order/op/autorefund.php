<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Autorefund_EweiShopV2Page extends WebPage
{
    protected function globalData()
    {
        global $_W, $_GPC;
        $uniacid = $_W['uniacid'];
        $orderid = (int)$_GPC['id'];

        $order = pdo_fetch("select id,status,price,refundid,goodsprice,dispatchprice,deductprice,deductcredit2,finishtime,isverify,`virtual`,refundstate,merchid,random_code,iscycelbuy,paytype from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid  limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid,));
        $orderprice = $order['price'];

        if ($order['iscycelbuy'] == 1) {
            //查询分期订单下面是否存在有开始的周期商品
            $order_goods = pdo_fetch("select * from " . tablename('ewei_shop_cycelbuy_periods') . "where orderid = {$order['id']} and status != 0");
            if (!empty($order_goods)) {
                show_json(0, '订单已经开始，无法进行退款');
            }
        }
        $refund = pdo_fetch("select id,orderid from " . tablename('ewei_shop_order_refund') . " where uniacid = :uniacid and orderid = :orderid and status > 0 limit 1 ", array(':uniacid' => $uniacid, ':orderid' => $orderid));
        if (!empty($refund)) {
            show_json(0, '此订单已完成维权，不能申请退款');
        }

        if (empty($order)) {
            if (!$_W['isajax']) {
                header('location: ' . mobileUrl('order'));
                exit;
            } else {
                show_json(0, '订单未找到');
            }
        }

        $_err = '';
        if ($order['status'] <= 0) {
            $_err = '订单未付款或已关闭，不能申请退款!';
        } else {
            if ($order['status'] == 3) {
                if (!empty($order['virtual']) || $order['isverify'] == 1) {
                    $_err = '此订单不允许退款!';
                } else {
                    if ($order['refundstate'] == 0) {
                        //申请退款
                        $tradeset = m('common')->getSysset('trade');
                        $refunddays = intval($tradeset['refunddays']);
                        if ($refunddays > 0) {
                            $days = intval((time() - $order['finishtime']) / 3600 / 24);
                            if ($days > $refunddays) {
                                $_err = '订单完成已超过 ' . $refunddays . ' 天, 无法发起退款申请!';
                            }
                        } else {
                            $_err = '订单完成, 无法申请退款!';
                        }
                    }
                }
            }
        }

        if (!empty($_err)) {
            if ($_W['isajax']) {
                show_json(0, $_err);
            } else {
                $this->message($_err, '', 'error');
            }
        }


        //订单不能退货商品

        /*********************************************************************/
        $order['cannotrefund'] = true;
        $refundgoods = array(
            'refund' => true,
            'returngoods' => true,
            'exchange' => true,
        );

        if ($order['status'] >= 1) {
            $goods = pdo_fetchall("select og.goodsid, og.price, og.total, og.optionname, g.cannotrefund,g.refund,g.returngoods,g.exchange,g.type, g.thumb, g.title,g.isfullback from" . tablename("ewei_shop_order_goods") . " og left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid where og.orderid=" . $order['id']);
            if (!empty($goods)) {
                foreach ($goods as $g) {
                    /*
                     * 退款优化V1.10
                     * 张洪利2019-09-16
                     * */
                    if (empty($g['cannotrefund'])) {
                        $g['refund'] = true;
                        $g['returngoods'] = true;
                        $g['exchange'] = true;
                    }
                    if ($order['status'] >= 2) {
                        /*
                         * 退款优化V1.10
                         * 张洪利2019-09-16
                         * */
                        if (!empty($g['cannotrefund']) && empty($g['refund']) && empty($g['returngoods']) && empty($g['exchange'])) {
                            $order['cannotrefund'] = false;
                        }
                    }
                    if ($order['status'] == 1) {
                        /*
                         * 退款优化V1.10
                         * 张洪利2019-09-16
                         * */
                        if (!empty($g['cannotrefund']) && empty($g['refund'])) {
                            $order['cannotrefund'] = false;
                        }
                    }
                    //虚拟商品完成订单
                    if ($order['status'] >= 3 && $g['type'] == 2) {
                        $g['returngoods'] = false;
                        $g['exchange'] = false;
                    }
                    $refundgoods['refund'] = empty($refundgoods['refund']) ? false : $g['refund'];
                    $refundgoods['returngoods'] = empty($refundgoods['returngoods']) ? false : $g['returngoods'];
                    $refundgoods['exchange'] = empty($refundgoods['exchange']) ? false : $g['exchange'];
                }
            }
        }
        if ($order['cannotrefund'] && empty($refundgoods['refund']) && empty($refundgoods['returngoods']) && empty($refundgoods['exchange'])) {
            $this->message("此订单不可退换货");
        }

        //是否全返商品，并检测是否允许退款
        $fullback_log = pdo_fetchall("select * from " . tablename('ewei_shop_fullback_log') . " where orderid = " . $orderid . " and uniacid = " . $uniacid . " ");
        $fullbackkprice = 0;
        if ($fullback_log && is_array($fullback_log)) {
            foreach ($fullback_log as $key => $value) {
                $fullbackgoods = pdo_fetch("select refund from " . tablename('ewei_shop_fullback_goods') . " where goodsid = " . $value['goodsid'] . " and uniacid = " . $uniacid . " ");
                if ($fullbackgoods['refund'] == 0) {
                    $this->message("此订单包含全返产品不允许退款");
                }
            }
            foreach ($fullback_log as $k => $val) {
                if ($val['fullbackday'] > 0) {
                    if ($val['fullbackday'] < $val['day']) {
                        $fullbackkprice += $val['priceevery'] * $val['fullbackday'];
                    } else {
                        $fullbackkprice += $val['price'];
                    }
                }
            }
        }
        $order['price'] = $order['price'] - $fullbackkprice;

        //应该退的钱 在线支付的+积分抵扣的+余额抵扣的(运费包含在在线支付或余额里）
        $order['refundprice'] = $order['price'] + $order['deductcredit2'];
        if ($order['status'] >= 2) {
            //如果发货，扣除运费
            $order['refundprice'] -= $order['dispatchprice'];
        }
        $order['refundprice'] = round($order['refundprice'], 2);

        return array(
            'uniacid' => $uniacid,
            'refundgoods' => $refundgoods,
            'openid' => $_W['openid'],
            'orderid' => $orderid,
            'order' => $order,
            'refundid' => $order['refundid'],
            'fullback_log' => $fullback_log,
            'fullbackgoods' => $fullbackgoods,
            'orderprice' => $orderprice
        );
    }

    public function main()
    {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $reasonArr = array('不想要了', '卖家缺货', '拍错了/订单信息错误', '其它');
        include $this->template();
    }

    public function submit()
    {
        global $_GPC, $_W;
        $ret = $this->globalData();
        extract($ret);
        if ($order['status'] == '-1')
            show_json(0, '订单已经处理完毕!');
        if ($order['paytype'] == 11) {
            show_json(0, '后台付款订单不允许售后');
        }
        $price = trim($order['price']);
        $rtype = 0;
        //全返退款，退款退货
        if (($rtype == 0 || $rtype == 1) && $order['status'] >= 3) {

            // if(($fullback_log['price']>=$orderprice || $fullbackgoods['refund'] == 0) && $fullback_log){
            //     show_json(0, "此订单不可退款");
            // }
            //全返管理停止
            if ($fullback_log) {
                m('order')->fullbackstop($orderid);
            }
        }
        $refund = array(
            'uniacid' => $uniacid,
            'merchid' => $order['merchid'],
            'applyprice' => $price,
            'rtype' => $rtype,
            'reason' => '商家缺货',
            'content' => trim($_GPC['content']),
            'imgs' => iserializer($_GPC['images']),
            'price' => $price,
        );
        $refundstate = 1;
        if ($order['refundstate'] == 0) {
            //新建一条退款申请
            $refund['createtime'] = time();
            $refund['orderid'] = $orderid;
            $refund['orderprice'] = $order['refundprice'];
            $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
            pdo_insert('ewei_shop_order_refund', $refund);
            $refundid = pdo_insertid();
            pdo_update('ewei_shop_order', array('refundid' => $refundid, 'refundstate' => $refundstate), array('id' => $orderid, 'uniacid' => $uniacid));
        } else {
            //修改退款申请
            pdo_update('ewei_shop_order', array('refundstate' => $refundstate), array('id' => $orderid, 'uniacid' => $uniacid));
            pdo_update('ewei_shop_order_refund', $refund, array('id' => $refundid, 'uniacid' => $uniacid));
        }
        //模板消息
        m('notice')->sendOrderMessage($orderid, true);
        $this->setRefund($orderid,$refundid);
        show_json(1);
    }

    private function setRefund($orderid,$refundid)
    {
        global $_W, $_GPC,$_S;

        $id = $orderid;

        $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id = :id and uniacid=:uniacid Limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
//        dump($item);
        if (empty($item)) {
            if ($_W['isajax']) {
                show_json(0, "未找到订单!");
            }
            $this->message('未找到订单!', '', 'error');
        }

        if (empty($refundid)) {
            $refundid = $item['refundid'];
        }

        if (!empty($refundid)) {
            $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $refundid));

            $refund['imgs'] = iunserializer($refund['imgs']);
        }
        $r_type = array( '0' => '退款', '1' => '退货退款', '2' => '换货');








            $shopset = $_S['shop'];

            if (empty($item['refundstate'])) {
                show_json(0,'订单未申请维权，不需处理！');
            }

            if ($refund['status'] < 0 || $refund['status'] == 1) {
                pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
                show_json(0,'未找需要处理的维权申请，不需处理！');
            }

            if (empty($refund['refundno'])) { //退款单号
                $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
                pdo_update('ewei_shop_order_refund', array('refundno' => $refund['refundno']), array('id' => $refund['id']));
            }

            //处理退款
            $refundstatus = 1;
            $refundcontent = '商家缺货';

            //0暂不处理 1通过申请 2手动退款 3完成 -1拒绝申请

            $time = time();
            $change_refund = array();
            $uniacid = $_W['uniacid'];

            if ($refundstatus == 0) {
                show_json(1);

            } else if ($refundstatus == 3) {
                //商家通过申请，等待客户发货

                $raid = $_GPC['raid'];
                $message = trim($_GPC['message']);

                if ($raid == 0) {
                    $raddress = pdo_fetch('select * from '.tablename('ewei_shop_refund_address').' where isdefault=1 and uniacid=:uniacid and merchid=0 limit 1',array(':uniacid'=>$uniacid));
                } else {
                    $raddress = pdo_fetch('select * from '.tablename('ewei_shop_refund_address').' where id=:id and uniacid=:uniacid and merchid=0 limit 1',array(':id'=>$raid,':uniacid'=>$uniacid));
                }

                if (empty($raddress)) {
                    $raddress = pdo_fetch('select * from '.tablename('ewei_shop_refund_address').' where uniacid=:uniacid and merchid=0 order by id desc limit 1',array(':uniacid'=>$uniacid));
                }

                unset($raddress['uniacid']);
                unset($raddress['openid']);
                unset($raddress['isdefault']);
                unset($raddress['deleted']);

                $raddress = iserializer($raddress);

                $change_refund['reply'] = '';
                $change_refund['refundaddress'] = $raddress;
                $change_refund['refundaddressid'] = $raid;
                $change_refund['message'] = $message;

                if (empty($refund['operatetime'])) {
                    $change_refund['operatetime'] = $time;
                }

                if ($refund['status'] != 4) {
                    $change_refund['status'] = 3;
                }

                pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $item['refundid']));

                //模板消息
                m('notice')->sendOrderMessage($item['id'], true,$raid);

            } else if ($refundstatus == 5) {
                //商家确认发货

                $change_refund['rexpress'] = $_GPC['rexpress'];
                $change_refund['rexpresscom'] = $_GPC['rexpresscom'];
                $change_refund['rexpresssn'] = trim($_GPC['rexpresssn']);
                $change_refund['status'] = 5;

                if ($refund['status'] != 5 && empty($refund['returntime'])) {
                    $change_refund['returntime'] = $time;

                    if (empty($refund['operatetime'])) {
                        $change_refund['operatetime'] = $time;
                    }
                }


                pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $item['refundid']));

                //模板消息
                m('notice')->sendOrderMessage($item['id'], true);

            } else if ($refundstatus == 10) {
                //确认换货成功，关闭申请

                $refund_data['status'] = 1;
                $refund_data['refundtime'] = $time;
                pdo_update('ewei_shop_order_refund', $refund_data, array('id'=>$item['refundid'], 'uniacid' => $uniacid));

                $order_data = array();
                $order_data['refundstate'] = 0;
                $order_data['status'] = 3;
                $order_data['refundtime'] = $time;
                pdo_update('ewei_shop_order', $order_data, array('id'=>$item['id'], 'uniacid' => $uniacid));

                //模板消息
                m('notice')->sendOrderMessage($item['id'], true);
            } else if ($refundstatus == 1) {
                //同意退款

                //订单号
                if ($item['parentid'] > 0) {
                    $parent_item = pdo_fetch("SELECT id,ordersn,ordersn2,price,transid,paytype,apppay FROM " . tablename('ewei_shop_order') . " WHERE id = :id and uniacid=:uniacid Limit 1", array(':id' => $item['parentid'], ':uniacid' => $_W['uniacid']));
                    if (empty($parent_item)) {
                        show_json(0, "未找到退款订单!");
                    }
                    $order_price = $parent_item['price'];
                    $ordersn = $parent_item['ordersn'];
                    $item['transid'] = $parent_item['transid'];
                    $item['paytype'] = $parent_item['paytype'];
                    $item['apppay'] = $parent_item['apppay'];
                    if(!empty($parent_item['ordersn2'])){
                        $var = sprintf("%02d", $parent_item['ordersn2']);
                        $ordersn.="GJ".$var;
                    }
                } else {
                    $borrowopenid = $item['borrowopenid'];
                    $ordersn = $item['ordersn'];
                    $order_price = $item['price'];
                    if (!strexists($borrowopenid,'2088') && !is_numeric($borrowopenid)){
                        if(!empty($item['ordersn2'])){
                            $var = sprintf("%02d", $item['ordersn2']);
                            $ordersn.="GJ".$var;
                        }
                    }
                }
                if ($item['is_wxapp'] == 1)
                {
                    $item['ordersn'] .= '_wxapp';
                    $ordersn .= '_wxapp';
                    $member = m('member')->getMember($item['openid']);
//                    $item['openid'] = $member['openid_wa'];
                    $item['apppay'] = 2;
                }
                //退款金额
                $applyprice = $refund['applyprice'];

                $pay_refund_price = 0; //在线支付的退款金额
                $dededuct__refund_price = 0; //余额抵扣退款的金额

                if($applyprice<=$item['price']){
                    //申请金额小于实际支付金额
                    $pay_refund_price =$applyprice; //在线支付的退款金额
                    $dededuct__refund_price = 0; //余额抵扣退款的金额
                }elseif( $applyprice> $item['price'] && $applyprice<= $item['price'] +  $item['deductcredit2']){
                    $pay_refund_price = $item['price']; //在线支付的退款金额
                    $dededuct__refund_price =  $applyprice -$pay_refund_price ; //余额抵扣退款的金额
                }else {
                    //错误
                    show_json(0,'退款申请的金额错误.请联系买家重新申请!');
                }

                //购物积分
                $goods = pdo_fetchall("SELECT g.id,g.credit, o.total,o.realprice,g.isfullback FROM " . tablename('ewei_shop_order_goods') .
                    " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid and o.uniacid=:uniacid", array(':orderid' => $item['id'], ':uniacid' => $uniacid));

                $refundtype = 0;

                if (empty($item['transid']) && $item['paytype']==22 && empty($item['apppay'])){
                    $item['paytype'] = 23;
                }
                if (!empty($item['transid']) && $item['paytype']==22 && empty($item['apppay']) && strexists($item['borrowopenid'],'2088')){
                    $item['paytype'] = 23;
                }
                //检查是都是代付订单
                $ispeerpay = m('order')->checkpeerpay($item['id']);
                //如果是代付订单,微信退款
                if (!empty($ispeerpay)){
                    $item['paytype'] = 21;
                }
                if ($item['paytype'] == 11) {
                    show_json(0,'后台付款无法申请退款,请手动处理');
                }
                if ($item['paytype'] == 1) {
                    //余额支付，直接返回余额
                    m('member')->setCredit($item['openid'], 'credit2', $pay_refund_price, array(0, $shopset['name'] . "退款: {$pay_refund_price}元 订单号: " . $item['ordersn']));
                    $result = true;

                    $refundtype = 0;
                } else if ($item['paytype'] == 21) {

                    if ($item['apppay']==2){
                        //如果是小程序的则走小程序配置
                        $result = m('finance')->wxapp_refund($item['openid'], $ordersn, $refund['refundno'], $order_price * 100, $pay_refund_price * 100, !empty($item['apppay']) ? true : false);
                    }else{
                        //微信支付，走退款 接口

                        //如果是代付订单
                        if (!empty($ispeerpay)){
                            //代付订单id
                            $pid = $ispeerpay['id'];

                            $peerpaysql = "SELECT * FROM " . tablename('ewei_shop_order_peerpay_payinfo') . " WHERE pid = :pid";
                            $peerpaylist = pdo_fetchall($peerpaysql, array(':pid'=>$pid));
                            if (empty($peerpaylist)){
                                show_json(0,'没有人帮他代付过,无需退款');
                            }
                            if(count($peerpaylist) >1){
                                $peerpay = array_column($peerpaylist['price']);
                                if($pay_refund_price != array_sum($peerpay)){
                                    show_json(0,'代付不允许单品维权');
                                }
                            }
                            //开始退款
                            foreach ($peerpaylist as $k =>$v) {
                                //余额退款
                                if (empty($v['tid'])){
                                    m('member')->setCredit($v['openid'], 'credit2', $v['price'], array(0, $shopset['name'] . "退款: ". $v['price']."元 代付订单号: " . $item['ordersn']));
                                    $result = true;
                                    continue;
                                }else{//微信退款

                                    $result = m('finance')->refund($v['openid'], $v['tid'], $refund['refundno'].$v['id'], $v['price'] * 100, $v['price'] * 100, !empty($item['apppay']) ? true : false);
                                }
                            }
                        }else {
                            if ($pay_refund_price > 0) {
                                // $res = pdo_get('ewei_shop_order',array('ordersn'=>$ordersn,'uniacid'=>$_W['uniacid']),array('random_code'));
                                // $random_code = '';
                                // if(!empty($res['random_code'])){
                                //     $random_code = trim($res['random_code']);
                                // }
                                // $ordersn = trim($ordersn.$random_code);
                                if (empty($item['isborrow'])) {
                                    $result = m('finance')->refund($item['openid'], $ordersn, $refund['refundno'], $order_price * 100, $pay_refund_price * 100, !empty($item['apppay']) ? true : false);
                                } else {
                                    $result = m('finance')->refundBorrow($item['borrowopenid'], $ordersn, $refund['refundno'], $order_price * 100, $pay_refund_price * 100, !empty($item['ordersn2']) ? 1 : 0);
                                }
                            }
                        }
                    }
                    $refundtype = 2;
                }elseif($item['paytype']==22){
                    $sec = m('common')->getSec();
                    $sec =iunserializer($sec['sec']);
                    if(!empty($item['apppay'])){
                        if(!empty($sec['app_alipay']['private_key_rsa2'])){
                            $sign_type = 'RSA2';
                            $privatekey=$sec['app_alipay']['private_key_rsa2'];
                        }else{
                            $sign_type = 'RSA';
                            $privatekey=$sec['app_alipay']['private_key'];
                        }
                        // new & app
                        if(empty($privatekey) || empty($sec['app_alipay']['appid'])){
                            show_json(0,'支付参数错误，私钥为空或者APPID为空!');
                        }
                        $params = array('out_request_no'=>time(),'out_trade_no' => $ordersn,'refund_amount'=>$pay_refund_price,'refund_reason' => $shopset['name'] . "退款: {$pay_refund_price}元 订单号: " . $item['ordersn']);
                        $config = array('app_id' => $sec['app_alipay']['appid'], 'privatekey' =>$privatekey, 'publickey' => "", 'alipublickey' => "",'sign_type'=>$sign_type);
                        $result = m('finance')->newAlipayRefund($params, $config);
                    }else if(!empty($sec['alipay_pay'])){
                        //新版支付宝退款
                        if(empty($sec['alipay_pay']['private_key']) || empty($sec['alipay_pay']['appid'])){
                            show_json(0,'支付参数错误，私钥为空或者APPID为空!');
                        }
                        if($sec['alipay_pay']['alipay_sign_type'] == 1){
                            $sign_type = 'RSA2';
                        }else{
                            $sign_type = 'RSA';
                        }
                        $params = array('out_request_no'=>time(),'out_trade_no' => $item['ordersn'],'refund_amount'=>$pay_refund_price,'refund_reason' => $shopset['name'] . "退款: {$pay_refund_price}元 订单号: " . $item['ordersn']);
                        $config = array('app_id' => $sec['alipay_pay']['appid'], 'privatekey' => $sec['alipay_pay']['private_key'], 'publickey' => "", 'alipublickey' => "",'sign_type'=>$sign_type);
                        $result = m('finance')->newAlipayRefund($params, $config);
                    }else{
                        // old
                        if (empty($item['transid'])){
                            show_json(0,'仅支持 升级后此功能后退款的订单!');
                        }
                        $setting = uni_setting($_W['uniacid'], array('payment'));
                        if (!is_array($setting['payment'])) {
                            return error(1, '没有设定支付参数');
                        }
                        $alipay_config = $setting['payment']['alipay'];
                        $batch_no_money = $pay_refund_price*100;
                        $batch_no = date('Ymd').'RF'.$item['id'].'MONEY'.$batch_no_money;
                        $res = m('finance')->AlipayRefund(array(
                            'trade_no'=> $item['transid'],
                            'refund_price'=> $pay_refund_price,
                            'refund_reason'=> $shopset['name'] . "退款: {$pay_refund_price}元 订单号: " . $item['ordersn'],
                        ),$batch_no,$alipay_config);

                        if (is_error($res)) show_json(0,$res['message']);
                        show_json(1,array('url'=>$res));
                    }
                    $refundtype=3;
                }elseif($item['paytype']==23 && !empty($item['isborrow'])){
                    $result = m('finance')->refundBorrow($item['borrowopenid'], $ordersn, $refund['refundno'], $order_price * 100, $pay_refund_price * 100, !empty($item['ordersn2']) ? 1 : 0);
                    $refundtype=4;
                } else {
                    //其他支付方式，走微信企业付款
                    if ($pay_refund_price < 0.3) {
                        show_json(0,'退款金额必须大于0.3元，才能使用微信企业付款退款!');
                    }

                    if ($pay_refund_price > 0) {
                        $result = m('finance')->pay($item['openid'], 1, $pay_refund_price * 100, $refund['refundno'], $shopset['name'] . "退款: {$pay_refund_price}元 订单号: " . $item['ordersn']);
                    }
                    $refundtype = 1;
                }

                if (is_error($result)) {
                    show_json(0,$result['message']);
                }
                //全返管理停止
                if($goods['isfullback']>0){
                    m('order')->fullbackstop($item['id']);
                }

//
//                //计算订单中商品累计赠送的积分   扣除积分已经在 m('order')->setStocksAndCredits($item['id'], 2); 方法中实现，此处不需要
//                $credits = m('order')->getGoodsCredit($goods);
//                //订单完成减少积分
//                if($item['status'] == 3){
//                    //扣除会员购物赠送积分
//                    if($credits>0){
//                        m('member')->setCredit($item['openid'], 'credit1', -$credits, array(0, $shopset['name'] . "退款扣除购物赠送积分: {$credits} 订单号: " . $item['ordersn']));
//                    }
//                }


                //返还抵扣积分
                if ($item['deductcredit'] > 0) {
                    m('member')->setCredit($item['openid'], 'credit1', $item['deductcredit'], array('0', $shopset['name'] . "购物返还抵扣积分 积分: {$item['deductcredit']} 抵扣金额: {$item['deductprice']} 订单号: {$item['ordersn']}"));
                }
                if ($item['deductcredit2'] > 0)
                {
                    m('order')->setDeductCredit2($item);
                }
//                if ($dededuct__refund_price > 0) {
//                    $item['deductcredit2'] = $dededuct__refund_price;
//                    m('order')->setDeductCredit2($item);
//                    if ($item['deductprice'] > 0) {
//                        m('member')->setCredit($item['openid'], 'credit1', $item['deductcredit'], array('0', $_W['shopset']['shop']['name'] . "自动关闭订单返还抵扣积分 积分: {$item['deductcredit']} 抵扣金额: {$item['deductprice']} 订单号: {$item['ordersn']}"));
//                    }
//                }

                $change_refund['reply'] = '';
                $change_refund['status'] = 1;
                $change_refund['refundtype'] = $refundtype;
                $change_refund['price'] = $applyprice;
                $change_refund['refundtime'] = $time;

                if (empty($refund['operatetime'])) {
                    $change_refund['operatetime'] = $time;
                }

                //同意
                pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $item['refundid']));

                //处理赠送余额情况
                // 2019-08-27 zhurunfeng 修改  添加参数，订单状态为已完成的扣除返还金额，否则不扣
//                m('order')->setDeductCredit2($item);

//                            返还抵扣积分



                if ($refund['orderprice'] == $refund['applyprice']) {
                    //退还优惠券
                    if (com('coupon') && !empty($item['couponid'])) {
                        com('coupon')->returnConsumeCoupon($item['id']); //申请退款成功
                    }
                }

                //更新订单退款状态
                pdo_update('ewei_shop_order', array('refundstate' => 0, 'status' => -1, 'refundtime' => $time), array('id' => $item['id'], 'uniacid' => $uniacid));

                //更新实际销量
                foreach ($goods as $g) {
                    //实际销量
                    $salesreal = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid '
                        . ' where og.goodsid=:goodsid and o.status>=1 and o.uniacid=:uniacid limit 1', array(':goodsid' => $g['id'], ':uniacid' => $uniacid));
                    pdo_update('ewei_shop_goods', array('salesreal' => $salesreal), array('id' => $g['id']));
                }


                $log = "订单退款 ID: {$item['id']} 订单号: {$item['ordersn']}";

                if ($item['parentid'] > 0) {
                    $log .= " 父订单号:{$ordersn}";
                }

//                plog('order.op.refund.submit', $log);

                //模板消息
                m('notice')->sendOrderMessage($item['id'], true);
            }




            else if ($refundstatus == -1) {
                //驳回申请

                pdo_update('ewei_shop_order_refund', array('reply' => $refundcontent, 'status' => -1, 'endtime' => $time), array('id' => $item['refundid']));

//                plog('order.op.refund.submit', "订单退款拒绝 ID: {$item['id']} 订单号: {$item['ordersn']} 原因: {$refundcontent}");

                //更新订单退款状态
                pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id'], 'uniacid' => $uniacid));

                //模板消息
                m('notice')->sendOrderMessage($item['id'], true);
            } else if ($refundstatus == 2) {
                //手动退款

                //同意
                $refundtype = 2;

                $change_refund['reply'] = '';
                $change_refund['status'] = 1;
                $change_refund['refundtype'] = $refundtype;
                $change_refund['price'] = $refund['applyprice'];
                $change_refund['refundtime'] = $time;

                if (empty($refund['operatetime'])) {
                    $change_refund['operatetime'] = $time;
                }

                pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $item['refundid']));

                //处理赠送余额情况

                if ($refund['orderprice'] == $refund['applyprice']) {
                    //退还优惠券
                    if (com('coupon') && !empty($item['couponid'])) {
                        com('coupon')->returnConsumeCoupon($item['id']); //申请退款成功
                    }
                }

                //更新订单退款状态
                pdo_update('ewei_shop_order', array('refundstate' => 0, 'status' => -1, 'refundtime' => $time), array('id' => $item['id'], 'uniacid' => $uniacid));

                $goods = pdo_fetchall("SELECT g.id,g.credit, o.total,o.realprice FROM " . tablename('ewei_shop_order_goods') .
                    " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid and o.uniacid=:uniacid", array(':orderid' => $item['id'], ':uniacid' => $uniacid));

                //计算订单中商品累计赠送的积分
                $credits = m('order')->getGoodsCredit($goods);

//                plog('order.op.refund.submit', "订单退款 ID: {$item['id']} 订单号: {$item['ordersn']} 手动退款!");
                //订单完成减少积分
                if ($item['status'] == 3) {
                    //扣除会员购物赠送积分
                    if($credits>0){
                        m('member')->setCredit($item['openid'], 'credit1', -$credits, array(0, $shopset['name'] . "退款扣除购物赠送积分: {$credits} 订单号: " . $item['ordersn']));
                    }
                }


                //更新实际销量
                foreach ($goods as $g) {
                    //实际销量
                    $salesreal = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid '
                        . ' where og.goodsid=:goodsid and o.status>=1 and o.uniacid=:uniacid limit 1', array(':goodsid' => $g['id'], ':uniacid' => $uniacid));
                    pdo_update('ewei_shop_goods', array('salesreal' => $salesreal), array('id' => $g['id']));
                }

                //模板消息
                m('notice')->sendOrderMessage($item['id'], true);
            }

            //加入好物圈收藏
            $goodscircle = p('goodscircle');
            if($goodscircle){
                $goodscircle->updateOrder($item['openid'],$item['id']);
            }
        plog('order.op.autorefund', "订单主动退款 ID: {$item['id']} 订单号: {$item['ordersn']} 原因: {$refundcontent}");

            show_json(1);
        }




}
