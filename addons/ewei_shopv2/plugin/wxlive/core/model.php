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

/**
 * 微信小程序直播模型
 * Class WxliveModel
 */
class WxliveModel extends PluginModel
{

    /**
     * @var array 状态映射
     */
    public $statusMap = array(
        0 => '直播未开始',
        1 => '正在直播',
        -1 => '直播已结束',
    );

    /**
     * 处理直播状态
     * @param $list
     * @return mixed
     * @author likexin
     */
    function handleStatus($list)
    {
        foreach ($list as &$row) {
            $row['live_status_text'] = isset($this->statusMap[$row['local_live_status']]) ? $this->statusMap[$row['local_live_status']] : '-';
        }
        return $list;
    }

    /**
     * 同步直播间列表
     * @author likexin
     */
    function syncRoomList($uniacid)
    {
        $plugin = p('app');
        if (!$plugin) {
            return error(-1, '系统未安装小程序');
        }

        // 获取小程序access_token
        $accessToken = $plugin->getAccessToken();
        if (is_error($accessToken)) {
            return $accessToken;
        }

        $pageSize = 100;
        $start = 0;
        // 处理完成的id
        $roomIds = [];

        // 请求接口地址
        $url = 'https://api.weixin.qq.com/wxa/business/getliveinfo?access_token=' . $accessToken;

        while (true) {
            $response = ihttp_post($url, json_encode([
                'start' => $start,
                'limit' => $pageSize,
            ]));
            $result = json_decode($response['content'], true);

//            $result = json_decode('{"errcode":0,"errmsg":"ok","room_info":[{"name":"测试直播","cover_img":"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/7xibEKVHnApB8D3MmD1ia2J4tmtTWRp4L3X2JXlU30E0lBRLjibcMnZibzsq1CEEUyD7xUuF9VZcU91AdnibXZOibdLw\/0?wx_fmt=png","start_time":1583157600,"end_time":1583164800,"anchor_name":"李大妮","anchor_img":"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/7xibEKVHnApB8D3MmD1ia2J4tmtTWRp4L3X2JXlU30E0lBRLjibcMnZibzsq1CEEUyD7xUuF9VZcU91AdnibXZOibdLw\/0?wx_fmt=png","roomid":2,"goods":[],"live_status":102}],"total":1,"live_replay":[]}', true);

            if ($result['errcode'] != 0) {
                if ($result['errcode'] == 1) {
                    return error($result['errcode'], '直播间列表为空');
                } else if ($result['errcode'] == 48001) {
                    return error($result['errcode'], '小程序没有直播权限');
                }
                return error($result['errcode'], $result['errmsg']);
            }

            foreach ($result['room_info'] as $room) {
                $roomId = (int)$room['roomid'];

                $roomIds[] = $roomId;

                // 查询当前直播间是否已经存在
                $wxlive = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_wxlive') . " WHERE `room_id`=:room_id AND `uniacid`=:uniacid", array(':uniacid' => $uniacid, ':room_id' => $roomId));

                $updateData = array(
                    'name' => (string)$room['name'],
                    'cover_img' => (string)$room['cover_img'],
                    'live_status' => (int)$room['live_status'],
                    'start_time' => (int)$room['start_time'],
                    'end_time' => (int)$room['end_time'],
                    'anchor_name' => (string)$room['anchor_name'],
                    'anchor_img' => (string)$room['anchor_img'],
                    'goods_json' => json_encode($room['goods']),
                );

                // 如果数据库中没有将走批量插入
                if (empty($wxlive)) {
                    $insertData = array_merge($updateData, [
                        'uniacid' => $uniacid,
                        'room_id' => $roomId,
                        'status' => 1,
                    ]);

                    // 插入数据
                    pdo_insert('ewei_shop_wxlive', $insertData);
                    continue;
                }

                // 如果数据库中已经存在走更新
                pdo_update('ewei_shop_wxlive', $updateData, array(
                    'room_id' => $room['roomid'],
                    'uniacid' => $uniacid,
                ));
            }


            if ($result['total'] < 100) {
                break;
            }else{
                $start = $pageSize;
                $pageSize += $pageSize;
            }

            unset($room);
        }
        unset($result);

        // 批量删除没操作的直播间
        pdo_query("DELETE FROM " . tablename('ewei_shop_wxlive') . " where room_id not in ( " . implode(',', $roomIds) . ") AND uniacid=:uniacid", array(':uniacid' => $uniacid));

        // 刷新直播状态
        $this->flushLiveStatus($uniacid);
    }

    /**
     * 刷新直播状态
     * @param $uniacid
     * @author likexin
     */
    function flushLiveStatus($uniacid)
    {
        $time = time();
        $cacheKey = 'wxlive_flush_live_status_' . $uniacid;

        $cache = m('cache')->get($cacheKey);
        if (!empty($cache) && ($cache + 30 > $time)) {
            return;
        }

        // 未开播更新为开播
        pdo_query('UPDATE ' . tablename('ewei_shop_wxlive') . " SET `local_live_status`=1 WHERE `local_live_status` = 0 AND `uniacid` = :uniacid AND `start_time` < :time AND `end_time` > :time", array(':uniacid' => $uniacid, ':time' => $time));

        // 开播中更新为下播
        pdo_query('UPDATE ' . tablename('ewei_shop_wxlive') . " SET `local_live_status`=-1 WHERE `uniacid` = :uniacid AND `end_time` < :time", array(':uniacid' => $uniacid, ':time' => $time));

        // 设置缓存
        m('cache')->set($cacheKey, $time);
    }

    public function getRoomId($id, $room_id)
    {
        $ret = pdo_get('ewei_shop_wxlive', compact('id', 'room_id'));
        return $ret['name'];
    }

    public function makeAccessToken()
    {
        global $_W;
        $plugin = p('app');
        if (!$plugin) {
            return error(-1, '系统未安装小程序');
        }

        // 获取小程序access_token
        $accessToken = $plugin->getAccessToken();
        return $accessToken;
    }

    //徐子轩 2020年3月24日20:26:18 小程序直播只获取一段
    public function getBackData($id, $begin = 0, $end = 1)
    {
        $access_toekn = $this->makeAccessToken();
        $data = [
            "action" => "get_replay", // 获取回放
            "room_id" => $id, // 直播间   id
            "start" => $begin, // 起始拉取视频，start =   0   表示从第    1   个视频片段开始拉取
            "limit" => $end // 每次拉取的个数上限，不要设置过大，建议  100 以内
        ];
        $data = json_encode($data);
        $result = ihttp_request('https://api.weixin.qq.com/wxa/business/getliveinfo?access_token=' . $access_toekn, $data);

        return json_decode($result['content'],true);
    }

    public function saveBack()
    {

    }
}
