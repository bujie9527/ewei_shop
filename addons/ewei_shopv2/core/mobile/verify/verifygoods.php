<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}
if (!pdo_tableexists('ewei_shop_saler_verify_log'))
{
    pdo_query("CREATE TABLE " . tablename('ewei_shop_saler_verify_log') ." (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storeid` int(11) DEFAULT '0' COMMENT '店铺id',
  `uniacid` int(11) DEFAULT '0' COMMENT '公众号id',
  `openid` varchar(255) DEFAULT '' COMMENT '核销员openid',
  `saler_id` int(11) NOT NULL DEFAULT '0' COMMENT '核销员id',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单id',
  `verify_time` int(11) NOT NULL DEFAULT '0' COMMENT '核销时间',
  `type` tinyint(2) DEFAULT '0' COMMENT '0是常规1是计时计次',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_uniacid` (`uniacid`) USING BTREE,
  KEY `index_openid` (`openid`) USING HASH,
  KEY `index_type` (`type`) USING HASH
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
class Verifygoods_EweiShopV2Page extends MobilePage {

    /**
     * 会员核销卡核销页面
     */
	function  detail() {

		global $_W, $_GPC;
		$openid = $_W['openid'];

		$verifycode = trim($_GPC['verifycode']);
		$id = trim($_GPC['id']);


		if (empty($verifycode)) {
			$this->message('未查询到记次时商品或核销码已失效,请核对核销码!', '', 'error');
		}

		$item = pdo_fetch('select vg.*,g.id as goodsid ,g.title,g.subtitle,g.thumb,vg.storeid from ' . tablename('ewei_shop_verifygoods') . '   vg
		 inner join ' . tablename('ewei_shop_order_goods') . ' og on vg.ordergoodsid = og.id
		 inner join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id
		 where  vg.id =:id and  vg.verifycode=:verifycode and vg.uniacid=:uniacid and vg.invalid =0 limit 1', array(':id' => $id,':uniacid' => $_W['uniacid'],':verifycode' => $verifycode));



		if (empty($item)) {
			$this->message('未查询到记次时商品或核销码已失效,请核对核销码!', '', 'error');
		}

		if(intval($item['codeinvalidtime'])<time())
		{
			$this->message('核销码已失效，请联系用户刷新页面获取最新核销码!', '', 'error');
		}

		$saler = pdo_fetch('select * from ' . tablename('ewei_shop_saler') . ' where  status=1  and openid=:openid and uniacid=:uniacid limit 1', array(
			':uniacid' => $_W['uniacid'], ':openid' => $openid
		));
		if (empty($saler)) {
			$this->message('您不是核销员,无权核销', '', 'error');
		}

		$store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $saler['storeid'], ':uniacid' => $_W['uniacid']));

		/*if (empty($saler['storeid'])) {
			$this->message('您无核销权限! 错误码:02', '', 'error');
		}*/

		if(!empty($item['storeid'])&&!empty($store)&&$item['storeid']!=$store['id'])
		{
			$this->message('该商品无法在您所属门店核销!请重新确认!', '', 'error');
		}

		if(!empty($item['limitnum']))
		{
			$verifygoodlogs = pdo_fetchall('select *  from ' . tablename('ewei_shop_verifygoods_log') . '    where verifygoodsid =:id  ', array(':id' => $item['id']));
			$verifynum = 0;
			foreach($verifygoodlogs as $verifygoodlog)
			{
				$verifynum +=intval($verifygoodlog['verifynum']);
			}

			$lastverifys = intval($item['limitnum']) - $verifynum;
		}


		//判断时间是否过期
		if(empty($item['limittype']))
		{
			$limitdate = intval($item['starttime']) +  intval($item['limitdays'])*86400;

		}else
		{
			$limitdate = intval($item['limitdate']);
		}

		if($limitdate<time())
		{
			$this->message('该商品已过期!', '', 'error');
		}

		$termofvalidity = date('Y-m-d H:i',  $limitdate);

        include $this->template();
	}

    /**
     * 记计核销台
     */
	function main() {
		global $_W, $_GPC;
		$openid = $_W['openid'];

		$saler = pdo_fetch('select * from ' . tablename('ewei_shop_saler') . ' where status=1  and openid=:openid and uniacid=:uniacid limit 1', array(
			':uniacid' => $_W['uniacid'], ':openid' => $openid
		));

		if (empty($saler)) {
			$this->message('您无核销权限!',"close");
		}
		$member = m('member')->getMember($saler['openid']);
		$store = false;
		if (!empty($saler['storeid'])) {

			$store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $saler['storeid'], ':uniacid' => $_W['uniacid']));

		}

		include $this->template();
	}

    /**
     * 查看核销
     */
	function search() {
		global $_W, $_GPC;
		$openid = $_W['openid'];

        $verifycode = trim($_GPC['verifycode']);
        if (empty($verifycode)) {
            show_json(0, '请填写核销码');
        }
		$verifygood = m('verifygoods')->search($verifycode);
        if(is_error($verifygood)){
              show_json(0, $verifygood['message']);
        }
		show_json(1, array('verifygoodid' =>$verifygood['id']));
	}

    /**
     * 完成核销
     */
	function complete() {
		global $_W, $_GPC;
		$times = intval($_GPC['times']);
		$verifycode = trim($_GPC['verifycode']);
		$remarks = trim($_GPC['remarks']);
		$result = m('verifygoods')->complete($verifycode,$times,$remarks);
		if(is_error($result)){
		    show_json(0,$result['message']);
        }
		show_json(1, array('verifygoodid' => $result['verifygoodid'],'orderid'=>$result['orderid']));
	}

    /**
     * 核销成功页面
     */
	function success(){
		global $_W,$_GPC;
		$this->message(array('title'=>'操作完成','message'=>'您可以退出浏览器了'),"javascript:WeixinJSBridge.call(\"closeWindow\");",'success');
	}

}
