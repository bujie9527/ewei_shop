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

class Index_EweiShopV2Page extends PluginMobilePage
{
    /**
     * @var $model Transfer_AccountModel
     */
    public $model;
    
    /**
     * 首页
     * @author zhurunfeng
     */
    function main()
    {
        $openid = $this->model->getW('openid');
        $type = $this->model->getGpc('type', 'credit1');
        $credit = m('member')->getCredit($openid, $type);
        $set = m('common')->getSysset('trade');

        include $this->template();
    }
    
    
    /**
     * 页面
     * @author zhurunfeng
     */
    public function ts()
    {
        global $_GPC, $_W;
        $search = '';
        $type = $_GPC['type'];
//        if ($_W['ispost']) {
//            $search = $_GPC['search'];
//        }
        $set = p('transfer_account')->getSet();
        include $this->template();
    }
    
    /**
     * 页面
     * @author zhurunfeng
     */
    public function sdl()
    {
        global $_GPC, $_W;
        $type = $_GPC['type'];
        $search = $_GPC['search'];
        if ($search == 0) {
        
        }
        if ($search == $this->model->getW('ewei_shopv2_member')['id']) {
        
        }

        $member = m('member')->getMember($search);
        if (empty($member)) {
        
        }
        if (!empty($member['mobile'])) {
            $member['mobile'] = secret($member['mobile'], false, 3, 4);
        }
        $set = p('transfer_account')->getSet();
        
        include $this->template();
    }
    
    /**
     * 页面
     * @author zhurunfeng
     */
    public function dsaccounts()
    {
        global $_GPC, $_W;
        $search = $_GPC['search'];
        $type = $_GPC['type'];
        $set = p('transfer_account')->getSet();
        
        if ($search == 0) {
        
        }
        if ($search == $this->model->getW('ewei_shopv2_member')['id']) {
        
        }
    
        $member = m('member')->getMember($search);
        if (empty($member)) {
        
        }
        if (!empty($member['mobile'])) {
            $member['mobile'] = secret($member['mobile'], false, 3, 4);
        }
        $currentMember = m('member')->getInfo($this->model->getW('openid'));
        include $this->template();
    }
    
    /**
     * 页面
     * @author zhurunfeng
     */
    public function log()
    {
        global $_W, $_GPC;
        $set = p('transfer_account')->getSet();
        include $this->template();
    }
    
    public function getList()
    {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $listType = $_GPC['listType'];
        $condition = '  and log.uniacid=:uniacid and log.credit_type=:credit_type';
    
        $keyword = $_GPC['keyword'];
    
    
        $params = array(
            ':uniacid' => $_W['uniacid'],
            ':credit_type' => $_GPC['type']
        );
        if ($listType == 'in') {
            $condition .= ' and to_user=:to_user ';
            $params[':to_user'] = $_W['openid'];
            $leftJoin = ' left join '.tablename('ewei_shop_member'). ' m on m.openid=log.from_user';
          
        } else {
            $condition .= ' and from_user=:from_user ';
            $params[':from_user'] = $_W['openid'];
            $leftJoin = ' left join '.tablename('ewei_shop_member'). ' m on m.openid=log.to_user';
        }
    
        if (!empty($keyword)) {
            $condition .= " and (m.nickname like '%{$keyword}%' or m.id like '%{$keyword}%') ";
        }
    
        $list = pdo_fetchall("select log.*,m.nickname, m.id uid from " . tablename('ewei_shop_transfer_log') . " log {$leftJoin}  where 1 {$condition} order by log.id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize , $params);

        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_transfer_log') . "log  $leftJoin where 1 {$condition}", $params);
    
        foreach ($list as &$item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['surplus'] = $listType == 'in' ? $item['to_surplus'] :  $item['from_surplus'];
            if ($listType == 'in') {
                $item['transfer_text'] = '转出方';
                $item['time_text'] = '转入时间';
            } else {
                $item['transfer_text'] = '转入方';
                $item['time_text'] = '转出时间';
            }
            if ($_GPC['type'] == 'credit1') {
                $item['credit_text'] = '积分';
            } else {
                $item['credit_text'] = '余额';
            }
        }
        show_json(0, array('list' => $list, 'total' => $total, 'pagesize'=>$psize));
    }
    
    /**
     * 页面
     * @author zhurunfeng
     */
    public function finish()
    {
        global $_GPC, $_W;
        $set = p('transfer_account')->getSet();
        include $this->template();
    }
    
    /**
     * 获取用户信息
     * @author zhurunfeng
     */
    public function getuser()
    {
        $uid = $this->model->getGpc('uid', 0);
        if ($uid == 0) {
            show_json(-1, '用户不存在');
        }
        if ($uid == $this->model->getW('ewei_shopv2_member')['id']) {
            show_json(-1, '不能转给自己');
        }
        
        $member = m('member')->getMember($uid);
        if (empty($member)) {
            show_json(-1, '用户不存在');
        }
        if (!empty($member['mobile'])) {
            $member['mobile'] = secret($member['mobile'], false, 3, 4);
        }
//        $currentMember = m('member')->getInfo($this->model->getW('openid'));
//        if ($currentMember['id'] === $member['id']) {
//            show_json(0, '不能给当前用户转账');
//        }
        
        show_json(1);
    }

    public function transfer()
    {
        $type = $this->model->getGpc('type', 'credit2');
        $money = $this->model->getGpc('money');
        if (empty($money)) {
            show_json(0, '金额不能为空');
        }
        $toUserId = $this->model->getGpc('to_userid', 0);
        $member = m('member')->getMember($this->model->getW('openid'));
        if ($member[$type] < $money) {
            return show_json(-1, '您的剩余' . ($type == 'credit1' ? '积分' : '余额') . '不足');
        }
        if ($toUserId === 0) {
            return show_json(-1, '请选择转账用户');
        }
        if ($toUserId == $member['id']) {
            return show_json(-1, '不能转给自己');
        }
        $transfer_result = $this->model->transfer($type, $money, $member['id'], $toUserId);
        if (true === is_array($transfer_result) && $transfer_result['code'] <= 0) {
            return show_json($transfer_result['code'], $transfer_result['msg']);
        }
        show_json(1, '操作成功');
    }

    public function getFromMe()
    {
        return $this->model->getFromMe();
    }

    public function getToMe()
    {
        return $this->model->getToMe();
    }

}
