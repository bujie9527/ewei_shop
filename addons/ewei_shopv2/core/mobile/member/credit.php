<?php
//fy  20201125
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Credit_EweiShopV2Page extends MobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$_GPC['type'] = intval($_GPC['type']);
		$member = m('member')->getMember($_W['openid'], true);
		$showTransfer = false;

		if (p('transfer_account')) {
			$set = p('transfer_account')->getSet();

			if ($set['credit'] == 1) {
				$showTransfer = true;
			}
		}

		$credit2 = m('member')->getCredit($_W['openid'], 'credit2');
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$type = intval($_GPC['type']);
		$listType = $_GPC['list_type'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$uidinfo = M('member')->getInfo($_W['openid']);
		$uid = $uidinfo['uid'];
		$credit_condition = ' and r.uniacid=' . $_W['uniacid'] . (' and r.credittype=\'credit1\' and r.openid = \'' . $_W['openid'] . '\'  ');

		if ($listType == 1) {
			$credit_condition .= ' and (r.num < 0) ';
		}
		else {
			$credit_condition .= ' and (r.num > 0) ';
		}

		$list = pdo_fetchall('select m.uid,m.mobile,m.nickname,r.remark title,r.num money,r.createtime from ' . tablename('ewei_shop_member_credit_record') . 'r left join ' . tablename('ewei_shop_member') . ' m on m.openid = r.openid where 1 ' . $credit_condition . ' order by r.createtime desc LIMIT ' . ($pindex - 1) * $psize . ',' . $psize);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_credit_record') . 'r left join ' . tablename('ewei_shop_member') . ' m on m.openid = r.openid where 1 ' . $credit_condition);

		foreach ($list as &$item) {
			$item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
			$item['rechargetype'] = 'credit';
		}

		unset($item);
		show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize));
	}
}

?>
