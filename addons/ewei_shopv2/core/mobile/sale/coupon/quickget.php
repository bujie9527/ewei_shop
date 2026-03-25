<?php

//20200615
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Quickget_EweiShopV2Page extends MobileLoginPage {

    public function main(){
        global $_W, $_GPC;


        $id = intval($_GPC['id']);

        $openid= $_W['openid'];
        $member = m('member')->getMember($openid);
        if(empty($member))
        {
            header('location: ' . mobileUrl());die;
        }
        $time = time();

        $coupon = pdo_fetch('select * from ' . tablename('ewei_shop_coupon') . ' where  1 and uniacid=:uniacid  and id=:id', array(':uniacid' => $_W['uniacid'],':id' => $id));

        $gettotal = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_data') . ' where couponid=:couponid and uniacid=:uniacid limit 1', array(':couponid' => $id, ':uniacid' => $_W['uniacid']));
        $utotal = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_data') . ' where couponid=:couponid and uniacid=:uniacid and openid = :openid limit 1', array(':couponid' => $id, ':uniacid' => $_W['uniacid'],':openid'=>$_W['openid']));

        if ($utotal >= $coupon['url_limit'] && $coupon['url_limit'] != 0 )
        {
            $this->message('您已经超过最大领取限制!',mobileUrl(),'error');
            return ;
        }
        $left_count = $coupon['total'] -  $gettotal;
        $left_count = intval($left_count);

            if (empty($coupon))
            {
                $msg = '优惠券不存在！';
            }
            if (empty($coupon['quickget']))
            {
                $msg = '此优惠券不可快速领取！';
            }
            if ($left_count<=0 && $coupon['total'] != -1)
            {
                $msg = '优惠券余量不足！';
            }
            if ($msg)
            {
                $this->message($msg ? $msg: '优惠券不存在或已经售罄',mobileUrl(),'error');
                return;
            }

//            header('location: ' . mobileUrl());die;




        //增加优惠券日志
        $couponlog = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $member['openid'],
            'logno' => m('common')->createNO('coupon_log', 'logno', 'CC'),
            'couponid' => $id,
            'status' => 1,
            'paystatus' => -1,
            'creditstatus' => -1,
            'createtime' => time(),
            'getfrom' => 8
        );
        pdo_insert('ewei_shop_coupon_log', $couponlog);

        //增加用户优惠券
        $data = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $member['openid'],
            'couponid' => $id,
            'gettype' => 8,
            'gettime' => time()
        );
        pdo_insert('ewei_shop_coupon_data', $data);
        $id = pdo_insertid();

        header('location: ' . mobileUrl('sale/coupon/my/showcoupons2',array("id"=>$id)));

    }
}
