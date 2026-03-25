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

class build_EweiShopV2Page extends PluginPfMobilePage {

    /**
     * 接任务
     */
    public function picktask(){
        global $_GPC,$_W;
        if ($_W['ispost']){
            $openid = $_W['openid'] = $_GPC['openid'];
            $taskid = intval($_GPC['id']);
            empty($taskid) && show_json(0,'任务不存在');
            $ret = $this->model->pickTask($taskid,$openid);
            if (is_error($ret)) show_json(0,$ret['message']);
            show_json(1,$ret) ;
        }
    }

	function main() {
	    //判断参与人是否已经参加了其他任务【注意这里是其他任务不包括本任务】，如果有则不能生成海报
		global $_W, $_GPC;
		$goods = array();
		$openid = trim($_GPC['openid']);
		$content = trim(urldecode($_GPC['content']));
        //用户
		if(empty($openid)){
			return;
		}
		$member = m('member')->getMember($openid);
		if(empty($member)){
			return;
		}

		//查找二维码
		$poster = pdo_fetch('select * from ' . tablename('ewei_shop_task_poster') . ' where keyword=:keyword and uniacid=:uniacid and `status`=1 and `is_delete`=0 limit 1', array(':keyword' => $content, ':uniacid' => $_W['uniacid']));
        if (empty($poster)) {
			m('message')->sendCustomNotice($openid, '未找到海报!');
			return;
		}
        $time = time();
		if($poster['timestart']>$time){
			$starttext =empty($poster['starttext'])?"活动于 [任务开始时间] 开始，请耐心等待...":$poster['starttext'];
			$starttext =str_replace("[任务开始时间]",date('Y年m月d日 H:i',$poster['timestart']),$starttext);
			$starttext =str_replace("[任务结束时间]",date('Y年m月d日 H:i',$poster['timeend']),$starttext);
			m('message')->sendCustomNotice($openid,$starttext);
			return;
		}

		if( $poster['timeend']<time()){
			$endtext = empty($poster['endtext'])?"活动已结束，谢谢您的关注！":$poster['endtext'];
			$endtext =str_replace("[任务开始时间]",date('Y-m-d H:i',$poster['timestart']),$endtext);
			$endtext =str_replace("[任务结束时间]",date('Y-m-d- H:i',$poster['timeend']),$endtext);

			m('message')->sendCustomNotice($openid,$endtext);
			return;
		}

        $img = '';
        $is_waiting = false;
        //查找此类型任务是否正在参加
        $task_count = pdo_fetchcolumn('select COUNT(*) from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_type='.$poster['poster_type'].' and is_reward=0 and failtime>'.time(), array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid']));
        if($task_count){
            //有
            $task_info = pdo_fetch('select `needcount`,`completecount`,`is_reward`,`failtime` from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_id=:task_id and task_type=:task_type and  failtime>'.time().' order by `addtime` DESC limit 1', array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid'],':task_id'=>$poster['id'],':task_type'=>$poster['poster_type']));
            //判断是否是当前任务
            if($task_info){
                $poster['completecount'] = $task_info['completecount'];
                //是
                $is_waiting = true;
                if($task_info['is_reward']==0){
                    $img = $this->create_poster($poster, $member);
                }elseif($task_info['is_reward']==1){
                    //加入任务
                    if($poster['is_repeat']) {
                        $img = $this->join_task($member, $poster);
                    }else{
                        $img = $this->create_poster($poster, $member);
                    }
                }
            }else{
                //否
                m('message')->sendCustomNotice($openid, '您已经有同类型的任务正在进行，不能同时参加');
                return ;
            }
        }else{
            //无
            if($poster['poster_type']==1){
                $poster_type = 2;
            }elseif($poster['poster_type']==2){
                $poster_type = 1;
            }
            $other_task_count = pdo_fetchcolumn('select COUNT(*) from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_type='.$poster_type.' and failtime>'.time(), array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid']));
            if($other_task_count){
                $default = pdo_fetchcolumn('select `data` from ' . tablename('ewei_shop_task_default') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
                if($default){
                    $default = unserialize($default);
                    if($default['is_posterall']==1){
                        //判断是否有已结束的此类型任务
                        $end_task_count = pdo_fetchcolumn('select COUNT(*) from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_type='.$poster['poster_type'].' and failtime<'.time(), array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid']));
                        if($end_task_count){
                            //是
                            $end_task_info = pdo_fetch('select `needcount`,`completecount`,`failtime` from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_id=:task_id and task_type=:task_type and failtime<'.time().' order by `addtime` DESC limit 1', array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid'],':task_id'=>$poster['id'],':task_type'=>$poster['poster_type']));
                            //判断是否是当前任务
                            if($end_task_info){
                                //是
                                if($poster['is_repeat']){
                                    //是
                                    $is_waiting = true;
                                    //加入任务
                                    $img = $this->join_task($member,$poster);
                                }else{
                                    //提示此任务不能重复领取
                                    m('message')->sendCustomNotice($openid, '您已经参加过此任务，不能重复参加');
                                    return;
                                }
                            }else{
                                //否
                                $is_waiting = true;
                                //加入任务
                                $img = $this->join_task($member,$poster);
                            }
                        }else{
                            //否
                            $is_waiting = true;
                            //加入任务
                            $img = $this->join_task($member,$poster);
                        }
                    }elseif($default['is_posterall']==0){
                        m('message')->sendCustomNotice($openid, '您已经有另外类型的海报任务正在进行，不能同时参加');
                        return ;
                    }
                }else{
                    m('message')->sendCustomNotice($openid, '您已经有另外类型的海报任务正在进行，不能同时参加');
                    return ;
                }
            }else{
                //判断是否有已结束的此类型任务
                $end_task_count = pdo_fetchcolumn('select COUNT(*) from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_type='.$poster['poster_type'].' and (is_reward=1 or failtime<'.time().')', array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid']));
                if($end_task_count){
                    //是
                    $end_task_info = pdo_fetch('select `needcount`,`completecount`,`failtime` from ' . tablename('ewei_shop_task_join') . ' where uniacid=:uniacid and join_user=:join_user and task_id=:task_id and task_type=:task_type and (is_reward=1 or failtime<'.time().') order by `addtime` DESC limit 1', array(':uniacid' => $_W['uniacid'],':join_user'=>$member['openid'],':task_id'=>$poster['id'],':task_type'=>$poster['poster_type']));
                    //判断是否是当前任务
                    if($end_task_info){
                        //是
                        if($poster['is_repeat']){
                            //是
                            $is_waiting = true;
                            //加入任务
                            $img = $this->join_task($member,$poster);
                        }else{
                            //提示此任务不能重复领取
                            m('message')->sendCustomNotice($openid, '您已经参加过此任务，不能重复参加');
                            return;
                        }
                    }else{
                        //否
                        $is_waiting = true;
                        //加入任务
                        $img = $this->join_task($member,$poster);
                    }
                }else{
                    //否
                    $is_waiting = true;
                    //加入任务
                    $img = $this->join_task($member,$poster);
                }
            }

        }
        //是否需要发送正在生成提示
        if($is_waiting){
            $waittext = !empty($poster['waittext'])?htmlspecialchars_decode($poster['waittext'],ENT_QUOTES):'您的专属海报正在拼命生成中，请等待片刻...';
            $waittext =str_replace("[任务开始时间]",date('Y年m月d日 H:i',$poster['timestart']),$waittext);
            $waittext =str_replace("[任务结束时间]",date('Y年m月d日 H:i',$poster['timeend']),$waittext);

            m('message')->sendCustomNotice($openid, $waittext);
        }
		$mediaid = $img['mediaid'];
		if(!empty($mediaid)){
			//发送任务说明
            $task_complain = "亲爱的[任务执行者昵称]，恭喜您成功领取[任务名称]!\r\n下面是您的专属任务海报,好友扫描海报后即可提升您的人气值。\r\n人气值达到[任务目标]即可解锁任务奖励。\r\n[关注奖励列表]\r\n当前海报有效期至：[海报有效期]";
            $default = pdo_fetchcolumn('select `data` from ' . tablename('ewei_shop_task_default') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
            if($default){
                $default = unserialize($default);
                $task_complain = $default['getposter']['value'];
            }
            if($poster['getposter']){
                $task_complain = $poster['getposter'];
            }
            $poster['okdays']=time()+$poster['days'];
            $poster['completecount']=empty($poster['completecount']) ? 0 : $poster['completecount'];
            $task_complain = $this->model->notice_complain($task_complain,$member,$poster,'',2);
            $task_complain = htmlspecialchars_decode($task_complain,ENT_QUOTES);
            m('message')->sendCustomNotice($openid, $task_complain);
		    //发送海报
			m('message')->sendImage($openid,$mediaid);
		}else{
            //发送任务说明
            $task_complain = "亲爱的[任务执行者昵称]，恭喜您成功领取[任务名称]!\r\n下面是您的专属任务海报,好友扫描海报后即可提升您的人气值。\r\n人气值达到[任务目标]即可解锁任务奖励。\r\n[关注奖励列表]\r\n当前海报有效期至：[海报有效期]";
            $default = pdo_fetchcolumn('select `data` from ' . tablename('ewei_shop_task_default') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
            if($default){
                $default = unserialize($default);
                $task_complain = $default['getposter']['value'];
            }
            $poster['okdays']=time()+$poster['days'];
            $poster['completecount']=empty($poster['completecount']) ? 0 : $poster['completecount'];
            $task_complain = $this->model->notice_complain($task_complain,$member,$poster,'',2);
            $task_complain = htmlspecialchars_decode($task_complain,ENT_QUOTES);
            m('message')->sendCustomNotice($openid, $task_complain);
            //发送海报
			$oktext= "<a href=\"".$img['img']."\">点击查看您的专属海报</a>";
			m('message')->sendCustomNotice($openid, $oktext);
		}
		return;
	}

	//参加任务并生成海报
	private function join_task($member,$poster){
        global $_W;
        //加入任务
        $time = time();
        $rec_reward = unserialize($poster['reward_data']);
        $rec_reward = $rec_reward['rec'];
        $rec_reward = serialize($rec_reward);
        $task_join = array(
            'uniacid'=>$_W['uniacid'],
            'join_user'=>$member['openid'],
            'task_id'=>$poster['id'],
            'task_type'=>1,
            'needcount'=>$poster['needcount'],
            'failtime'=>$time+$poster['days'],
            'addtime'=>$time
        );
        if($poster['poster_type']==2){
            $task_join['task_type'] = 2;
            $task_join['reward_data'] = $rec_reward;
        }
        pdo_insert('ewei_shop_task_join', $task_join);
        $id = pdo_insertid();
        $img = '';
        if($id){
            //获取二维码图片
            $qr = $this->model->getQR($poster, $member);
            if (is_error($qr)) {
                m('message')->sendCustomNotice($member['openid'], '生成二维码出错: ' . $qr['message']);
                exit;
            }
            //生成海报
            $img = $this->model->createPoster($poster, $member, $qr);
        }
        if($img){
            return $img;
        }else{
            return false;
        }
    }
    //只生成海报
    private function create_poster($poster, $member){
        //获取二维码图片
        $qr = $this->model->getQR($poster, $member);

        if (is_error($qr)) {
            m('message')->sendCustomNotice($member['openid'], '生成二维码出错: ' . $qr['message']);
            exit;
        }
        //生成海报
        $img = $this->model->createPoster($poster, $member, $qr);
        if($img){
            return $img;
        }else{
            return false;
        }
    }


    //奖励
    public function reward(){
        global $_W, $_GPC;
        $member_info = $_GPC['member_info'];
        $poster = $_GPC['poster'];
        $join_info = $_GPC['join_info'];
        $qr = $_GPC['qr'];
        $openid = $_GPC['openid'];
        $qrmember = $_GPC['qrmember'];
        $poster['reward_data'] = htmlspecialchars_decode($poster['reward_data']);
        if($join_info['task_type']==1){
            $this->model->reward($member_info,$poster,$join_info,$qr,$openid,$qrmember);
        }elseif($join_info['task_type']==2){
            $this->model->rankreward($member_info,$poster,$join_info,$qr,$openid,$qrmember);
        }
        return true;
    }
}
