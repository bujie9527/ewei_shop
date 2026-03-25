<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Transfer_AccountModel extends PluginModel
{


    public $_w = [];

    public $_gpc = [];

    public $member;

    public $toUser;

    public $set;
    public $shopSet;

    public function __construct($name = 'transfer_account')
    {
        global $_W, $_GPC;
        $this->setGpc($_GPC);
        $this->setW($_W);
        $this->set = $this->getSet()['transfer_account'];
        $this->shopSet = m('common')->getSysset(array('trade', 'shop'));

        parent::__construct($name);
    }

    /**
     * 获取gpc参数
     * @param string $key
     * @param string $default
     * @return array
     */
    public function getGpc($key = '', $default = '')
    {
        if ($key) {
            return isset($this->_gpc[$key]) ? $this->_gpc[$key] : $default;
        }
        return $this->_gpc;
    }

    /**
     * 设置gpc参数
     * @param $gpc
     */
    public function setGpc($gpc)
    {
        $this->_gpc = $gpc;
    }

    /**
     * 设置全局w参数
     * @param $w
     */
    public function setW($w)
    {
        $this->_w = $w;
    }

    /**
     * 获取全局w参数
     * @param string $key
     * @param string $default
     * @return array
     */
    public function getW($key = '', $default = '')
    {
        if ($key) {
            return isset($this->_w[$key]) ? $this->_w[$key] : $default;
        }
        return $this->_w;
    }

    /**
     * 获取用户信息
     * @param $uid int 用户id
     * @return mixed
     */
    public function getMemberByUid($uid)
    {
        $member = m('member')->getInfo($uid);
        return $member;
    }

    /**
     * 获取用户的积分/余额
     * @param $uid int 用户id
     * @param string $type string 类型
     * @return mixed
     */
    public function getCredit($uid, $type = 'credit1')
    {
        $member = m('member')->getInfo($uid);
        return $member[$type];
    }

    /**
     * 设置入账用户
     * @param $user array 用户数组
     */
    public function setToUser($user)
    {
        $this->toUser = $user;
    }

    /**
     * 获取转账用户 这里封装方便后期更改
     * @return mixed
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * 设置转出方属性
     * @param $user array 出账方用户
     */
    public function setFromUser($user)
    {
        $this->member = $user;
    }

    /**
     * 获取出账用户 封装起来,后面如果有部分金额冻结在这个函数操作
     * @return mixed
     */
    public function getFromUser()
    {
        return $this->member;
    }

    public function transfer($creditType, $total, $fromUserId, $toUserId)
    {
        $types = [
            'credit2' => 'balance',
            'credit1' => 'credit',
        ];
        $text = [
            'credit1' => $this->shopSet['trade']['credittext'] ? $this->shopSet['trade']['credittext'] : '积分',
            'credit2' => $this->shopSet['trade']['moneytext'] ? $this->shopSet['trade']['moneytext'] : '余额',
        ];
//        if ($this->set['data'][$types[$creditType]] < 1) {
//            return app_error(0, $text[$creditType] . '余量不足');
//        }
        /**
         * 当前用户
         */
        $this->setFromUser($this->getMemberByUid($fromUserId));
        /**
         * 接收转账的用户
         */
        $this->setToUser($this->getMemberByUid($toUserId));
        /**
         * 判断余额
         */
        
        if ($this->member[$creditType] < $total) {
            return $this->buildReturnData(-1, '余额不足');
        }
        /**
         * 判断对方用户是否存在
         */
        if (empty($this->toUser)) {
            return $this->buildReturnData(-1, '收款方不存在');
        }
        /**
         * @var $tmpTotal float 记录总金额
         */
        // 提交
        $tmpTotal = $total;
        /**
         * 积分不需要手续费,只有余额转赠才需要手续费
         */
        //
        if ($creditType == 'credit2') {
            // 实际到账
            $total = $this->getTotal($total);
        }
        //开始转账
        // 转出
        $this->setCredit($tmpTotal, $this->getFromUser()['openid'], 0, $creditType);
        // 转入
        $this->setCredit($total, $this->getToUser()['openid'], 1, $creditType);
        $this->setFromUser($this->getMemberByUid($this->getFromUser()['openid']));
        $this->setToUser($this->getMemberByUid($this->getToUser()['openid']));
        $this->getRechargeMsg($tmpTotal, $creditType);
        pdo_insert('ewei_shop_transfer_log',
            [
                'uniacid' => $this->getW('uniacid'),
                'from_user' => $this->getFromUser()['openid'],
                'to_user' => $this->getToUser()['openid'],
                'type' => 0,
                'credit_type' => $creditType,
                'service_recharge' => price_format($total - $tmpTotal),
                'credit' => $tmpTotal,
                'final_price' => $total,
                'create_time' => time(),
                'from_surplus' => $this->getFromUser()[$creditType],
                'to_surplus' => $this->getToUser()[$creditType],
                'service_recharge_scale' => $this->set['service_recharge_price'] . ($this->set['service_recharge_type'] == 1 ? '%' : ''),
            ]
        );
        
        
        return true;
    }


    /**
     * 操作用户余额
     * @param $total float 总数
     * @param $openid  string 会员openid
     * @param $type int 类型 0 为转出 1 为转入
     * @param $creditType string 0为积分 1 为余额
     */
    public function setCredit($total, $openid, $type, $creditType)
    {
        /**
         * @var $text array 定义设置的文字展示
         */

        $msg = $type == 0 ? '转出' : '转入';
        $msg = $this->getTransMsg($type, $creditType, $total);
        $total = $type == 0 ? -$total : $total;
        m('member')->setCredit(
            $openid,
            $creditType,
            $total,
            array(
                0,
                $msg
            ));
//        会员获得转账余额：100余额 转出UID：1234567891234567  剩余200

    }


    public function getRechargeMsg($total, $creditType)
    {
        //记录转出人的充值信息
        if ($creditType == 'credit1') {
            return false;
        }
        $set = $this->set;
        $logno = m('common')->createNO('member_log', 'logno', 'RC');
        $data = array(
            'openid' => $this->getFromUser()['openid'],
            'logno' => $logno,
            'uniacid' => $this->getW('uniacid'),
            'type' => '0',
            'createtime' => TIMESTAMP,
            'status' => '1',
            'title' => '转出扣除余额',
            'rechargetype' => 'user',
            'money' => -price_format($total),
            'remark' => '转出扣除余额' . ($total) . ' 转入uid为: ' . $this->getToUser()['id'] . ' 剩余余额为: ' . $this->getFromUser()[$creditType],
        );
        pdo_insert('ewei_shop_member_log', $data);
        $logid = pdo_insertid();
        m('notice')->sendMemberLogMessage($logid, 0, true);


        $logno = m('common')->createNO('member_log', 'logno', 'RC');
        $data = array(
            'openid' => $this->getToUser()['openid'],
            'logno' => $logno,
            'uniacid' => $this->getW('uniacid'),
            'type' => '0',
            'createtime' => TIMESTAMP,
            'rechargetype' => 'user',
            'status' => '1',
            'title' => '获得转账余额',
            'money' => price_format($total - $this->getServiceRecharge($total)),
            'remark' => '获得转账余额 ' . ($total - $this->getServiceRecharge($total)). ' 转出uid为: ' . $this->getFromUser()['id'] . ' 剩余余额为: ' . $this->getToUser()[$creditType],
        );
        pdo_insert('ewei_shop_member_log', $data);
        $logid = pdo_insertid();
        m('notice')->sendMemberLogMessage($logid, 0, true);

    }

    /**
     * 获取转账扣除余额的信息
     * @param int $type
     * @param $creditType
     * @param $money
     * @return string
     */
    public function getTransMsg($type = 0, $creditType, $money)
    {
        $text = [
            'credit1' => $this->shopSet['trade']['credittext'],
            'credit2' => $this->shopSet['trade']['moneytext'],
        ];
        $msg = $this->shopSet['shop']['name'] . "会员" . ($type == 1 ? '获得转账' : '转账扣除') . $text[$creditType] . $money . "  ";
        if ($type == 0) {
            $tmpMsg = "转入uid为:" . $this->getToUser()['id'] . "  ";
        } else {
            $tmpMsg = "转出uid为:" . $this->getFromUser()['id'] . "  ";
        }
        return $msg . $tmpMsg;
    }

    /**
     * 获取总金额
     * @param $total
     * @return mixed|string
     */
    public function getTotal($total)
    {
        return $total - $this->getServiceRecharge($total);
    }


    /**
     * 计算手续费
     * @param $total float
     */
    public function getServiceRecharge($total)
    {
        if ($this->set['service_recharge_type'] == 1) {
            if ($this->set['service_recharge_price'] <= 0) {
                return 0;
            }
            return ($this->set['service_recharge_price'] / 100) * $total;

        }
        return $this->set['service_recharge_price'] > 0 ? $this->set['service_recharge_price'] : 0.00;
    }

    public function buildReturnData($code, $msg)
    {
        return compact('code', 'msg');
    }

    /**
     * 获取设置
     * @param string $key
     * @return false|mixed
     */
    public function getSetting($key = '', $defult = '')
    {
        return isset($key) && mb_strlen($key) > 0 ? (isset($this->set[$key]) ? $this->set[$key] : ($defult ? $defult : false)) : ($defult ? $defult : false);
    }

    /**
     * 转给我的
     * @return false|float|int|mixed|Services_JSON_Error|string
     */
    public function getToMe()
    {
        $uid = $this->getGpc('uid');
        if (empty($uid)) {
            return app_error(0, '请输入对方用户名');
        }
        $total = 0;
        $page = max(1, $this->getGpc('page'));
        $pagesize = $this->getGpc('page_size') ? $this->getGpc('page_size') : 20;
        $where = array('to' => $this->_w['openid']);
        if ($uid) {
            $member = m('member')->getMember($uid);
            if (empty($member)) {
                return app_error(0, '会员不存在');
            }
            $where['from'] = $member['openid'];
        }
        $list = pdo_getslice('ewei_shop_transfer_log',
            $where,
            array(($page - 1) * $pagesize, $pagesize),
            $total,
            '*',
            'id',
            'create_time desc'
        );
        foreach ($list as &$item) {
            $item['member'] = m('member')->getMember($item['from']);
            $item['create_time'] = date('Y-m-d H:i:d', $item['create_time']);
        }
        return app_json(1, compact('total', 'list'));
    }

    /**
     * 我转出的
     * @return false|float|int|mixed|Services_JSON_Error|string
     */
    public function getFromMe()
    {
        $uid = $this->getGpc('uid');
        if (empty($uid)) {
            return app_error(0, '请输入对方用户名');
        }
        $total = 0;
        $page = max(1, $this->getGpc('page'));
        $pagesize = $this->getGpc('page_size') ? $this->getGpc('page_size') : 20;
        $where = array('from' => $this->_w['openid']);
        if ($uid) {
            $member = m('member')->getMember($uid);
            if (empty($member)) {
                return app_error(0, '会员不存在');
            }
            $where['from'] = $member['openid'];
        }
        $list = pdo_getslice('ewei_shop_transfer_log',
            $where,
            array(($page - 1) * $pagesize, $pagesize),
            $total,
            '*',
            'id',
            'create_time desc'
        );
        foreach ($list as &$item) {
            $item['member'] = m('member')->getMember($item['to']);
            $item['create_time'] = date('Y-m-d H:i:d', $item['create_time']);
        }
        return app_json(1, compact('total', 'list'));
    }
}