<?php

class Ordercheck_EweiShopV2Page extends WebPage
{
    public $payType = [
        '21'=>'weixin',
        '22'=>'alipay'
    ];
    public $orderId = '';
    public function main()
    {
        include $this->template();
    }

    public function verify()
    {
        global $_W,$_GPC;
        $set = m('common')->getSysset(array('shop', 'pay'));
        $paytypeCode = $_GPC['paytype'];
        $paytype = $this->payType[$paytypeCode];
        if (empty($paytype))
        {
            show_json(0,'支付方式必选');
        }
        $this->orderId = $_GPC['mch_id'];
        $pay = $set['pay'];
        if (!isset($pay[$paytype]) || $pay[$paytype . '_id'] == 0)
        {
            show_json(0,'请配置您的支付');
        }
        $params = $this->getParams($_W['uniacid'],$pay[$paytype . '_id']);
        if ($params === false)
        {
            show_json(0,'支付参数配置有误或者无可用支付');
        }
        show_json(0,'操作成功');
    }

    public function getParams($uniacid,$paymemberId)
    {
        $payment = pdo_get('ewei_shop_payment',array('id'=>$paymemberId,'uniacid'=>$uniacid));

        if ($payment['paytype'] == 0)
        {
            $ret = $this->buildWechat($payment);
        }else if ($payment['paytype'] == 1)
        {
            $ret = $this->buildAlipay($payment);
            echo json_encode(ihttp_request($ret));
            exit();
        }

        if (empty($ret))
        {
            return false;
        }
    }
    public function buildWechat($payment)
    {
    }
    public function buildAlipay($payment)
    {
        $public = array(
            'app_id' => $payment['apikey'],
            'method' => $payment['method'],
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => $payment['sign_type'] == 1 ? 'RSA2' : 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content'=>json_encode(array('out_trade_no'=>$this->orderId)),
        );

        $this->makeSign($public);
        return $public;
    }

    public function makeSign(&$params)
    {
        $prepares = array();
        foreach ($params as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type') {
                $prepares[] = "{$key}={$value}";
            }
        }
        sort($prepares);
        $string = implode($prepares, '&');
        $string .= $params['secret'];
        $params['sign'] = md5($string);
    }
}