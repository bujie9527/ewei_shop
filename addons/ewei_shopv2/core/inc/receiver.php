<?php
//20200612
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Receiver extends WeModuleReceiver {
    public function receive() {
        global $_W;
        $type = $this->message['type'];
        $event = $this->message['event'];
        //redis 防止并发
        $open_redis = function_exists('redis') && !is_error(redis());
        $redis_key = "receive_".$this->message['from'];
//        $file_key = __DIR__."/{$this->message['from']}.json";
        if($open_redis){
            $redis = redis();
            if($redis->get($redis_key)){
                return $this->responseEmpty();
            }
            $redis->set($redis_key, 2,2);
        }else{
            $handle = opendir(__DIR__); //当前目录
            while (false !== ($file = readdir($handle))) { //遍历该php文件所在目录
                list($filesname,$kzm)=explode(".",$file);//获取扩展名
                if($kzm=="json") { //文件过滤
                    if (!is_dir('./'.$file)) { //文件夹过滤
                        $info = filectime(__DIR__.'/'.$file);
                        if($info<time()){
                            @unlink(__DIR__.'/'.$file);
                        }
                    }
                }
            }
            if(file_exists($file_key)){
                return $this->responseEmpty();
            }
            fopen($file_key,'a+');
        }
        if($event == 'subscribe' && $type == 'subscribe') {
            $this->saleVirtual();
        }
    }

    public function saleVirtual($obj=null)
    {
        global $_W;
        if (empty($obj)){
            $obj = $this;
        }
        $sale = m('common')->getSysset('sale');
        $data = $sale['virtual'];
        if (empty($data['status'])){
            return false;
        }
        load()->model('account');
        $account = account_fetch($_W['uniacid']);

        //会员统计查询
        $open_redis = function_exists('redis') && !is_error(redis());
        if( $open_redis ) {
            $redis_key1 = "ewei_{$_W['uniacid']}_member_salevirtual_isagent";
            $redis_key2 = "ewei_{$_W['uniacid']}_member_salevirtual";
            $redis = redis();
            if (!is_error($redis)) {
                if ($redis->get($redis_key1) != false) {
                    $totalagent = $redis->get($redis_key1);
                    $totalmember = $redis->get($redis_key2);
                } else {
                    $totalagent = pdo_fetchcolumn("select count(*) from" . tablename('ewei_shop_member') . " where uniacid =" . $_W['uniacid'] . " and isagent =1");
                    $totalmember = pdo_fetchcolumn("select count(*) from" . tablename('ewei_shop_member') . " where uniacid =" . $_W['uniacid']);
                    $redis->set($redis_key1, $totalagent, array('nx', 'ex' => '3600'));  //设置缓存  过期时间为1小时
                    $redis->set($redis_key2, $totalmember, array('nx', 'ex' => '3600'));  //设置缓存  过期时间为1小时
                }
            }
        }else{
            $totalagent = pdo_fetchcolumn("select count(*) from" . tablename('ewei_shop_member') . " where uniacid =" . $_W['uniacid'] . " and isagent =1");
            $totalmember = pdo_fetchcolumn("select count(*) from" . tablename('ewei_shop_member') . " where uniacid =" . $_W['uniacid']);
        }

        $acc = WeAccount::create();
        $member = abs((int)$data['virtual_people']) + (int)$totalmember;
        $commission =abs((int)$data['virtual_commission']) + (int)$totalagent;
        $user = m('member')->checkMemberFromPlatform($obj->message['from'],$acc);
        //超级海报会员关注
        if ($_SESSION['eweishop']['poster_member']){
            $user['isnew'] = true;
            $_SESSION['eweishop']['poster_member'] = null;
        }
        if ($user['isnew'])
        {
            $message = str_replace('[会员数]', $member, $data['virtual_text']);
            $message = str_replace('[排名]', $member+1, $message);
        } else{
            $message = str_replace('[会员数]', $member, $data['virtual_text2']);
        }
        $message = str_replace('[分销商数]', $commission, $message);
        $message = str_replace('[昵称]', $user['nickname'], $message);
        $message = htmlspecialchars_decode($message,ENT_QUOTES);
        $message = str_replace('"','\"',$message);
        return $this->sendText($acc,$obj->message['from'],$message);

    }

    public function sendText($acc,$openid,$content){
        $send['touser'] = trim($openid);
        $send['msgtype'] = 'text';
        $send['text'] = array('content' => urlencode($content));
        $data = $acc->sendCustomNotice($send);
        return $data;
    }

    private function responseEmpty() {
        ob_clean();
        ob_start();
        echo '';
        ob_flush();
        ob_end_flush();
        exit(0);
    }
}