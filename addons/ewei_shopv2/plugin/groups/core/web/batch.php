<?php



//20200615
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Batch_EweiShopV2Page extends PluginWebPage {

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



    // 获取订单基本信息
    function getdata(){
        global $_W, $_GPC;


        $uniacid = $_W['uniacid'];

        if($_W['ispost']){

            // 定义 基础 查询条件
            $condition = " o.uniacid = :uniacid and m.uniacid = :uniacid and o.deleted=0 and o.addressid <>0";

            $paras = array(':uniacid' => $_W['uniacid']);

            // 获取 支付方式
            if ($_GPC['paytype'] != '') {
                    $condition .= " AND o.pay_type =" . $_GPC['paytype'];
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
                    $statuscondition = " AND o.status = 1 ";
                } else if($status=='0'){
                    $statuscondition = " AND o.status = 0 and o.pay_type<>3";
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
            if($_GPC['printstate']!='') {
                $printstate = intval($_GPC['printstate']);
                $condition .= " AND o.printstate={$printstate} ";
            }
            // 获取 发货单打印状态
            if($_GPC['printstate2']!='') {
                $printstate2 = intval($_GPC['printstate2']);
                $condition .= " AND o.printstate2={$printstate2} ";
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
                } else if ($searchfield == 'goodstitle') {
                    $sqlcondition =  " inner join ( select distinct og.orderid from " . tablename('ewei_shop_order_goods') . " og left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid where og.uniacid = '$uniacid' and og.merchid=0 and (locate(:keyword,g.title)>0)) gs on gs.orderid=o.id";
                    $paras[':keyword'] = trim($_GPC['keyword']);
                } else if ($searchfield == 'goodssn') {
                    $sqlcondition =  " inner join ( select distinct og.orderid from " . tablename('ewei_shop_order_goods') . " og left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid where og.uniacid = '$uniacid' and og.merchid=0 and (locate(:keyword,g.goodssn)>0)) gs on gs.orderid=o.id";
                    $paras[':keyword'] = trim($_GPC['keyword']);
                }
            }

            $sql = "select o.* ,a.realname ,m.nickname, d.dispatchname,m.nickname,r.refundstatus as refundstatus from " . tablename('ewei_shop_groups_order') . " o"
                . " left join " . tablename('ewei_shop_groups_order_refund') . " r on r.orderid=o.id and ifnull(r.refundstatus,-1)<>-1 and ifnull(r.refundstatus,-1)<> -2"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid = o.uniacid "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " left join " . tablename('ewei_shop_dispatch') . " d on d.id = o.dispatchid "
                . $sqlcondition." where $condition $statuscondition  ORDER BY o.createtime DESC,o.status DESC  ";
            $orders = pdo_fetchall($sql, $paras);
            //print_r($orders);



            $totalmoney = 0;


            foreach($orders as $i=>$order){
                // 计算订单总金额


                /*$totalmoney = $totalmoney+$order['price'];
                $totalmoney = number_format($totalmoney,2);*/




                // 定义支付方式
                $paytype = array('0' => array('css' => 'default', 'name' => '未支付'),'credit' => array('css' => 'danger', 'name' => '余额支付'),'11' => array('css' => 'default', 'name' => '后台付款'),'2' => array('css' => 'danger', 'name' => '在线支付'),
                    'wechat' => array('css' => 'success', 'name' => '微信支付'),'alipay' => array('css' => 'warning', 'name' => '支付宝支付'),'23' => array('css' => 'warning', 'name' => '银联支付'),'3' => array('css' => 'primary', 'name' => '货到付款'),
                );
                $orderstatus = array(
                    '-1' => array('css' => 'default', 'name' => '取消'),'0' => array('css' => 'danger', 'name' => '待付款'),'1' => array('css' => 'info', 'name' => '已付款'),
                    '2' => array('css' => 'warning', 'name' => '已发货'),'3' => array('css' => 'success', 'name' => '已完成')
                );



                if (empty($order['more_spec'])){
                    $order_goods = pdo_fetchall('select * from ' . tablename('ewei_shop_groups_goods') . ' where uniacid=:uniacid and id=:goodid ', array(':uniacid' => $_W['uniacid'], ':goodid' => $order['goodid']));

                }else{

                    $order_goods = pdo_fetchall('select og.*,g.* from ' . tablename('ewei_shop_groups_order_goods') . ' og LEFT JOIN ' . tablename('ewei_shop_groups_goods') . ' g on g.id = og.groups_goods_id '.' where og.uniacid=:uniacid and og.groups_order_id=:orderid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $order['id']));



                }


                $order_goods = set_medias($order_goods, 'thumb');


                $p = $order['pay_type'];

                $orders[$i]['goods'] = $order_goods;
                $orders[$i]['paytype'] = $paytype[$p]['name'];
                $orders[$i]['css'] = $paytype[$p]['css'];
                $orders[$i]['dispatchname'] = empty($order['addressid']) ? '自提' : $order['dispatchname'];
                if (empty($orders[$i]['dispatchname'])) {
                    $orders[$i]['dispatchname'] = '快递';
                }

                $s = $order['status'];

                $orders[$i]['statusvalue'] = $s;
                $orders[$i]['statuscss'] = $orderstatus[$s]['css'];
                $orders[$i]['statusname'] = $orderstatus[$s]['name'];

                $orders[$i]['address'] = iunserializer($order['address']);

                $orders[$i]['address']['address'] = $orders[$i]['address']['street'].$orders[$i]['address']['address'];
                $orders[$i]['address']['nickname'] = $order['nickname'];



                if($order['status']==1){
                    $orders[$i]['send_status'] = 1;
                }else{
                    $orders[$i]['send_status']  = 0;
                }


            }



            $temps = $this->model->getTemp();
            extract($temps);


            include $this->template();
        }
    }




    // 修改订单打印状态
    function changestate(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $arr = $_GPC['arr'];
            $type = intval($_GPC['type']);
            if(empty($arr) || empty($type)){
                die(json_encode(array("result"=>'error','resp'=>'数据错误。EP04')));
            }
            foreach($arr as $i=>$data){
                $orderid = $data['orderid'];
                $ordergoodid = $data['ordergoodid'];
                // 查询出已打印次数
                $ordergood = pdo_fetch("SELECT id,goodsid,printstate,printstate2 FROM " . tablename('ewei_shop_order_goods') . " WHERE goodsid=:goodsid and orderid=:orderid and uniacid=:uniacid and merchid=0 limit 1", array(':orderid'=>$orderid, ':goodsid' => $ordergoodid,':uniacid' => $_W['uniacid']));
                if($type==1){
                    pdo_update('ewei_shop_order_goods', array("printstate"=>$ordergood['printstate']+1), array('id' => $ordergood['id']));
                    $orderprint = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_order_goods') . " WHERE orderid=:orderid and printstate=0 and uniacid= :uniacid and merchid=0", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
                }
                elseif($type==2){
                    pdo_update('ewei_shop_order_goods', array("printstate2"=>$ordergood['printstate2']+1), array('id' => $ordergood['id']));
                    $orderprint = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_order_goods') . " WHERE orderid=:orderid and printstate2=0 and uniacid= :uniacid and merchid=0", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
                }
                // 判断 如果当前订单中的商品全部打印 则标记订单打印状态
                if($orderprint==0){
                    $printstatenum = 2;
                }else{
                    $printstatenum = 1;
                }
                if($type==1){
                    pdo_update('ewei_shop_order', array("printstate"=>$printstatenum), array('id' => $orderid));
                }
                elseif($type==2){
                    pdo_update('ewei_shop_order', array("printstate2"=>$printstatenum), array('id' => $orderid));
                }
            }
            die(json_encode(array("result"=>'success','orderprintstate'=>$printstatenum)));
        }
    }

    function getorderinfo(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $orderids = $_GPC['orderids'];
            $temp_esheetid = intval($_GPC['temp_esheetid']);

            $in = implode(',',$orderids);
            if(empty($in)){
                exit; // orders 为空
            }
            //查询电子面单模版
            $params = array(':uniacid' => $_W['uniacid'],':id'=>$temp_esheetid);
            $sql = 'SELECT temp.id,temp.esheetname,esheet.express,esheet.name as expresscom  FROM ' . tablename('ewei_shop_exhelper_esheet_temp') . ' temp '
                . ' left join ' . tablename('ewei_shop_exhelper_esheet') . ' esheet on temp.esheetid = esheet.id '
                . ' where temp.id=:id and temp.uniacid=:uniacid and temp.merchid=0 limit 1';
            $printTemp = pdo_fetch($sql, $params);

            if(empty($printTemp) || !is_array($printTemp)){
                exit; // 未选择电子面单模版
            }


            $orders = pdo_fetchall("SELECT id,ordersn,address,address_send,status,paytype,expresscom,expresssn,express,dispatchtype FROM " . tablename('ewei_shop_order') . " WHERE id in( $in ) and (status=1 or (paytype=3 and status=0)) and uniacid=:uniacid and merchid=0 and isparent=0 order by ordersn desc ", array(':uniacid' => $_W['uniacid']));

            if(empty($orders)){
                exit;  // 订单信息为空
            }
            $paytype = array('0' => array('css' => 'default', 'name' => '未支付'),'1' => array('css' => 'danger', 'name' => '余额支付'),'11' => array('css' => 'default', 'name' => '后台付款'),'2' => array('css' => 'danger', 'name' => '在线支付'),
                '21' => array('css' => 'success', 'name' => '微信支付'),'22' => array('css' => 'warning', 'name' => '支付宝支付'),'23' => array('css' => 'warning', 'name' => '银联支付'),'3' => array('css' => 'primary', 'name' => '货到付款'),
            );
            $orderstatus = array(
                '-1' => array('css' => 'default', 'name' => '已关闭'),'0' => array('css' => 'danger', 'name' => '待付款'),'1' => array('css' => 'info', 'name' => '待发货'),
                '2' => array('css' => 'warning', 'name' => '待收货'),'3' => array('css' => 'success', 'name' => '已完成')
            );



            foreach($orders as $i=>$order){
                if(!empty($order['address_send'])){
                    $orders[$i]['address_address'] = iunserializer($order['address_send']);
                }else{
                    $orders[$i]['address_address'] = iunserializer($order['address']);
                }

                if($order['status']==1 || ($order['status']==0 && $order['paytype']==3)){
                    $orders[$i]['send_status'] = 1;
                }else{
                    $orders[$i]['send_status']  = 0;
                }

                $p = $order['paytype'];
                $orders[$i]['paycss'] = $paytype[$p]['css'];
                $orders[$i]['paytypename'] = $paytype[$p]['name'];

                $s = $order['status'];
                $orders[$i]['statuscss'] = $orderstatus[$s]['css'];
                $orders[$i]['statusname'] = $orderstatus[$s]['name'];
                if ($s == -1) {
                    if ($order['refundstatus'] == 1) {
                        $orders[$i]['statusname'] = '已退款';
                    }
                }
            }



            include $this->template('exhelper/esheetprint/print_tpl_dosend');

        }
    }

    // 执行发货
    function dosend(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $orderid = intval($_GPC['orderid']);
            $express = trim($_GPC['express']);	// 快递编码
            $expresssn = trim($_GPC['expresssn']);	// 快递号
            $expresscom = trim($_GPC['expresscom']);	// 快递公司

            $orderinfo = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id=:orderid and status>-1 and uniacid=:uniacid and merchid=0 limit 1", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
            if(empty($orderinfo)){
                die(json_encode(array('result'=>'error','resp'=>'订单不存在')));
            }
            if($orderinfo['status']==1 || ($orderinfo['status']==0 && $orderinfo['paytype']==3)){
                // 判断 订单状态未待发货
                pdo_update('ewei_shop_order', array(
                    "express"=>trim($express),
                    'expresssn'=>trim($expresssn),
                    'expresscom'=>trim($expresscom),
                    'sendtime' => time(),
                    'status'=>2,
                    'refundstate'=>0
                ), array('id'=>$orderid));

                //取消退款状态
                if (!empty($orderinfo['refundid'])) {
                    $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $orderinfo['refundid']));
                    if (!empty($refund)) {
                        pdo_update('ewei_shop_order_refund', array('status' => -1, 'endtime'=>time()), array('id' => $orderinfo['refundid']));
                        pdo_update('ewei_shop_order', array('refundid' => 0), array('id' => $orderinfo['id']));
                    }
                }
                //模板消息
                m('notice')->sendOrderMessage($orderinfo['id']);

                plog('exhelper.print.batch.dosend', "一键发货 订单号: {$orderinfo['ordersn']} <br/>快递公司: {$_GPC['expresscom']} 快递单号: {$_GPC['expresssn']}");

                die(json_encode(array('result'=>'success')));
            }
        }
    }

    function getesheetTemp(){
        global $_W, $_GPC;


        if($_W['ispost']){

            $temp_senderid = intval($_GPC['temp_senderid']);
            $temp_esheetid = intval($_GPC['temp_esheetid']);



            if(empty($temp_esheetid)){
                die(json_encode(array("result"=>'error','resp'=>"加载模版错误! 请重新选择打印模板。")));
            }
            if(empty($temp_senderid)){
                die(json_encode(array("result"=>'error','resp'=>"加载模版错误! 请重新选择发件人信息模板。")));
            }
            if(empty($_GPC['printarr']['orderid'])){
                die(json_encode(array("result"=>'error','resp'=>"未找到相关订单")));
            }

            $params = array(':uniacid' => $_W['uniacid'],':id'=>$temp_esheetid);
            $sql = 'SELECT *  FROM ' . tablename('ewei_shop_exhelper_esheet_temp') . ' temp '
                . ' left join ' . tablename('ewei_shop_exhelper_esheet') . ' esheet on temp.esheetid = esheet.id '
                . ' where temp.id=:id and temp.uniacid=:uniacid and temp.merchid=0 limit 1';
            $esheet_item = pdo_fetch($sql, $params);

            $sender_item = pdo_fetch("SELECT *  FROM " . tablename('ewei_shop_exhelper_senduser') . " WHERE id=:id and uniacid=:uniacid and merchid=0 limit 1", array(':id'=>$temp_senderid, ':uniacid' => $_W['uniacid']));

            $orderids=explode( ',', $_GPC['printarr']['orderid']) ;
            $template=pdo_fetch("SELECT id, ordersn, print_template  FROM " . tablename('ewei_shop_order') ." WHERE id=:id limit 1",array('id' =>$orderids[0]));


            $replace_str =  array("'", '"', "#", "&", "+", "<", ">",",");
            $ordercode = $template['ordersn'];

            $eorder =array();
            $eorder["ShipperCode"] = $esheet_item['code'];
            $eorder["OrderCode"] =trim($_GPC['printarr']['ordercode']);
            $eorder["OrderCode"] =$ordercode;
            $eorder["PayType"] = $esheet_item['paytype'];//邮费支付方式:1-现付，2-到付，3-月结，4-第三方支付
            $eorder["ExpType"] = 1;//快递类型：1-标准快件
            $eorder["CustomerName"] =  $esheet_item['customername'];
            $eorder["CustomerPwd"] = $esheet_item['customerpwd'];
            $eorder["MonthCode"] = $esheet_item['monthcode'];
            $eorder["SendSite"] = $esheet_item['sendsite'];
            $eorder["IsNotice"] = $esheet_item['isnotice'];

            $sender = array();
            $sender["Name"] = $sender_item['sendername'];
            $sender["Mobile"] = $sender_item['sendertel'];
            $sender["ProvinceName"] = $sender_item['province'];
            $sender["CityName"] = $sender_item['city'];
            $sender["ExpAreaName"] = $sender_item['area'];
            $sender["Address"] = $sender_item['senderaddress'];
            $sender["PostCode"] = $sender_item['sendercode'];
            $sender["ExpAreaName"] = str_replace($replace_str, "",trim($sender["ExpAreaName"]));
            $sender["Address"] = str_replace($replace_str, "",trim($sender["Address"]));
            //收件人
            $receiver = array();
            $receiver["Name"] = trim($_GPC['printarr']['Name']);
            $receiver["Mobile"] = trim($_GPC['printarr']['Mobile']);
            $receiver["ProvinceName"] =trim($_GPC['printarr']['ProvinceName']);
            $receiver["CityName"] = trim($_GPC['printarr']['CityName']);
            $receiver["ExpAreaName"] =trim($_GPC['printarr']['ExpAreaName']);
            $receiver["ExpAreaName"] = str_replace($replace_str, "",trim($receiver["ExpAreaName"]));
            $receiver["Address"] = trim($_GPC['printarr']['Address']);
            $receiver["Address"] = str_replace($replace_str, "",trim($receiver["Address"]));
            if($esheet_item['code']=='EMS' || $esheet_item['code']=='YZPY' || $esheet_item['code']=='YZBK'){
                $receiver["PostCode"]='000000';
            }
            $commodityOne = array();
            $goodsname = str_replace($replace_str, "",trim($_GPC['printarr']['sendinfo']));
            $goods_name = "商品：".$goodsname;
            //$commodityOne["GoodsName"] = mb_substr($goodsname,0,15, 'utf-8');//商品名称
            //$commodityOne["GoodsName"] .='.....更多请查看发货清单';
            $commodityOne["GoodsName"] = $goods_name;
            $commodityOne["Goodsquantity"] = intval($_GPC['printarr']['all_total']);//商品数量
            $commodity = array();
            $commodity[] = $commodityOne;
            $eorder["Sender"] = $sender;
            $eorder["Receiver"] = $receiver;
            $eorder["Commodity"] = $commodity;
            $eorder["IsReturnPrintTemplate"] = '1';//返回电子面单模板：0-不需要；1-需要
            //圆通电子面单模板需要用新模板180mm模板
            if($esheet_item['code'] == 'YTO'){
                $esheet_item['templatesize'] = 180;
            }
            //中通电子面单模板需要用新模板130mm模板
            if($esheet_item['code'] == 'ZTO'){
                $esheet_item['templatesize'] = 76;
            }
            if(!empty($esheet_item['templatesize'])){
                $eorder["TemplateSize"] = $esheet_item['templatesize'];
            }
            //        $eorder["Quantity"] = 1; //件数/包裹数


            $jsonResult =  $this->model->submitEOrder($eorder);
            $result = json_decode($jsonResult['content'], true);
            if($result["ResultCode"] == "100" || $result['Success'] === true) {
                $printTemplate = str_replace('[二维码]','VIP客户',$result["PrintTemplate"]);
                $printTemplate = preg_replace_callback("/商品：(.*)<\/div>/" ,function($match) use ($goodsname){
                    return $goodsname;
                }, $printTemplate);
                //修改打印次数和code
                pdo_query("UPDATE ".tablename('ewei_shop_order_goods')." SET `esheetprintnum` = `esheetprintnum` + 1 WHERE id in (".$_GPC['printarr']['ordergoodid'].")");
                pdo_update('ewei_shop_order_goods', array("ordercode"=>$eorder["OrderCode"]), array('id' =>explode( ',', $_GPC['printarr']['ordergoodid'] ) ));

                foreach ($orderids as $orderid){
                    $orderinfo = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id=:orderid and status>-1 and uniacid=:uniacid and merchid=0 limit 1", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
                    if(empty($orderinfo)){
                        continue;
                    }
                    if($orderinfo['status']==1 || ($orderinfo['status']==0 && $orderinfo['paytype']==3)){
                        // 判断 订单状态未待发货
                        $data=array(
                            "express"=>trim($esheet_item['express']),
                            'expresssn'=>trim($result['Order']['LogisticCode']),
                            'expresscom'=>trim($esheet_item['name']),
                            'sendtime' => time(),
                            'refundstate'=>0,
                            'print_template'=>$printTemplate
                        );
                        if($esheet_item['issend']==1){
                            $data[ 'status']=2;

                            //取消退款状态
                            if (!empty($orderinfo['refundid'])) {
                                $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $orderinfo['refundid']));
                                if (!empty($refund)) {
                                    pdo_update('ewei_shop_order_refund', array('status' => -1, 'endtime'=>time()), array('id' => $orderinfo['refundid']));
                                    pdo_update('ewei_shop_order', array('refundid' => 0), array('id' => $orderinfo['id']));
                                }
                            }
                        }
                        $update_res = pdo_update('ewei_shop_order', $data, array('id' =>$orderinfo['id']));
                        if($update_res && $data[ 'status']==2){
                            //模板消息
                            m('notice')->sendOrderMessage($orderinfo['id']);
                            plog('exhelper.print.batch.dosend', "自动发货 订单号: {$orderinfo['ordersn']} <br/>快递公司: {$_GPC['expresscom']} 快递单号: {$_GPC['expresssn']}");
                        }
                    }
                }
                die(json_encode(array("result"=>'success','PrintTemplate'=>$printTemplate,'ordercode'=>$eorder["OrderCode"],'issend'=>$esheet_item['issend'])));
            }
            else {
                die(json_encode(array("result"=>'error','resp'=>$result['Reason'])));
            }

        }
    }

}
