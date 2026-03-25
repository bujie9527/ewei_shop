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
require_once 'seting.php';
require_once 'grade.php';
require_once 'presentation.php';
require_once 'surprised.php';
class Chicken_EweiShopV2Page extends PluginMobilePage {
    /**
     * 当前数据表名称
     * @var string
     */
    private $table = 'ewei_open_farm_chicken';
    /**
     * 当前类的所有字段
     * @var array
     */
    private $field = array(
        'id',
        'uniacid',
        'openid',
        'name',
        'portrait',
        'level',
        'experience',
        'accelerate',
        'egg_stock',
        'feed_stock',
        'bowl_stock',
        'integral',
        'lay_eggs_sum',
        'eat_sum',
        'feeding_sum',
        'feeding_time',
        'create_time',
    );
    /**
     * 默认openid
     * @var string
     */
    private $openid = '';
    /**
     * 初始化接口
     */
    public function __construct() {
        parent::__construct();
        global $_W;
        $_W['openid'] = $_W['openid'];
    }
    /**
     * 获取详细信息
     * @param bool $method
     * @return bool
     */
    public function getInfo($method = false) {
        global $_W;
        $sql = 'SELECT * FROM ' . tablename($this->table)
            . " WHERE `uniacid` = '{$_W['uniacid']}' AND `openid` = '{$_W['openid']}' ";
        $info = pdo_fetch($sql);
        if ($method) {
            return $info;
        } else {
            $this->model->returnJson($info);
        }
    }
    /**
     * 添加用户饲料
     * @param $number
     * @return bool
     */
    public function incFeed($number) {
        global $_W;
        $tableName = tablename($this->table);
        $sql = " UPDATE {$tableName} SET " .
            "`feed_stock` = `feed_stock` + {$number} " .
            " WHERE " .
            " `uniacid` = '{$_W['uniacid']}' AND " .
            " `openid` = '{$_W['openid']}' ";
        $query = pdo_query($sql);
        return $query;
    }
    /**
     * 添加用户饲料
     * @param $number
     * @return bool
     */
    public function redFeed($number) {
        global $_W;
        $tableName = tablename($this->table);
        $sql = " UPDATE {$tableName} SET " .
            "`feed_stock` = `feed_stock` - {$number} " .
            " WHERE " .
            " `uniacid` = '{$_W['uniacid']}' AND " .
            " `openid` = '{$_W['openid']}' ";
        $query = pdo_query($sql);
        return $query;
    }
    /**
     * 添加用户鸡吃过的饲料总数
     * @param $chicken
     * @return array
     */
    public function updateFeed($chicken) {
        global $_W;
        $data = $this->layEggs($chicken);
        $tableName = tablename($this->table);
        $sql = " UPDATE {$tableName} SET " .
            "`eat_sum` = `eat_sum` + {$chicken['feeding_sum']} ," .
            "`lay_eggs_sum` = {$data['number']} " .
            " WHERE " .
            " `uniacid` = '{$_W['uniacid']}' AND " .
            " `openid` = '{$_W['openid']}' ; ";
        pdo_query($sql);
        return $data;
    }
    /**
     * 添加鸡蛋库存
     * @param $number
     * @return bool
     */
    public function incEggs($number) {
        global $_W;
        $tableName = tablename($this->table);
        $sql = " UPDATE {$tableName} SET " .
            "`egg_stock` = `egg_stock` + {$number} " .
            " WHERE " .
            " `uniacid` = '{$_W['uniacid']}' AND " .
            " `openid` = '{$_W['openid']}' ; ";
        $query = pdo_query($sql);
        return $query;
    }
    /**
     * 添加鸡蛋库存
     * @param $number
     * @return bool
     */
    public function redEggs($number) {
        global $_W;
        $tableName = tablename($this->table);
        $sql = " UPDATE {$tableName} SET " .
            "`egg_stock` = `egg_stock` - {$number} ," .
            "`last_egg_stock` = `last_egg_stock` - {$number} " .
            " WHERE `uniacid` = '{$_W['uniacid']}' " .
            " AND `openid` = '{$_W['openid']}' ; ";
        $query = pdo_query($sql);
        return $query;
    }
    /**
     * 鸡下蛋
     * @param $chicken
     * @return array
     */
    public function layEggs($chicken) {
        global $_W;
        // 可能下蛋的饲料数
        $laySum = $chicken['feeding_sum'] + $chicken['lay_eggs_sum'];
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $seting = $seting->getInfo(true);
        // 设置默认剩余饲料数量
        $number = $laySum;
        // 吃多少会下蛋
        $layEggsEatNumber = $chicken['lay_eggs_eat'] ? $chicken['lay_eggs_eat'] : $seting['lay_eggs_eat'];
        // 设置下蛋数量
        $eggSum = 0;
        // 多次设置
        $eggs = array();
        // 判断当前饲料是否能下蛋
        if ($laySum >= $layEggsEatNumber) {
            // 下蛋次数
            $layEggSum = floor($laySum / $layEggsEatNumber);
            // 能下蛋的饲料数
            $layEggsFeed = $layEggSum * $layEggsEatNumber;
            // 下完蛋所剩的饲料累计数
            $layEggsFeedSum = $laySum - $layEggsFeed;
            // 能下蛋次数大于0
            if ($layEggSum > 0) {
                // 计算本次下蛋数量
                for ($i = 0; $i < $layEggSum; $i++) {
                    $one = rand($seting['lay_eggs_number_min'], $seting['lay_eggs_number_max']);
                    $eggSum += $one;
                    $eggs[] = $one;
                }
                // 循环下彩蛋
                for ($i = 0; $i < $layEggSum; $i++) {
                    $this->surprised();
                }
                $data['lay_eggs_eat'] = $seting['lay_eggs_eat'];
                pdo_update($this->table, $data);
            }
            $number = $layEggsFeedSum;
        }
        $data = array(
            'number' => $number,
            'egg_sum' => $eggSum,
            'eggs' => $eggs,
        );
        return $data;
    }
    /**
     * 鸡下彩蛋
     */
    public function surprised() {
        global $_W;
        // 清理无用彩蛋
        $surprised = new Surprised_EweiShopV2Page();
        $surprised->clearCouponSurprised();
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $setingInfo = $seting->getInfo(true);
        // 鸡下彩蛋的概率
        $setingInfo['surprised_probability'];
        $surprisedData = array(
            'yes' => $setingInfo['surprised_probability'],
            'no' => 100 - $setingInfo['surprised_probability'],
        );
        $surprised = $this->model->getRand($surprisedData);
        if ($surprised === 'yes') {
            // 查询所有彩蛋
            $surprisedTable = 'ewei_open_farm_surprised';
            $where = array(
                'uniacid' => $_W['uniacid'],
            );
            $surprisedArr = pdo_getall($surprisedTable, $where);
            if (!$surprisedArr) {
                return $surprisedArr;
            }
            $probabilityArr = array();
            foreach ($surprisedArr as $key => $value) {
                $probabilityArr[$value['id']] = $value['probability'];
            }
            $prize = $this->model->getRand($probabilityArr);
            $userSurprised = 'ewei_open_farm_user_surprised';
            $data = array(
                'uniacid' => $_W['uniacid'],
                'openid' => $_W['openid'],
                'surprised_id' => $prize,
                'status' => '否',
                'create_time' => date('Y-m-d H:i:s'),
            );
            $query = pdo_insert($userSurprised, $data);
            return $query;
        }
    }
    /**
     * 添加用户经验值
     * @param $chicken
     * @return void
     */
    public function incExperience($chicken) {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $setingInfo = $seting->getInfo(true);
        $number = $chicken['feeding_sum'] * $setingInfo['eat_experience'];
        $grade = new Grade_EweiShopV2Page();
        $gradeData = array(
            'level' => $chicken['level'],
            'experience' => $number + $chicken['experience'],
        );
        $gradeData = $grade->checkLevel($gradeData);
        $where = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
        );
        pdo_update($this->table, $gradeData, $where);
    }
    /**
     * 添加用户积分
     * @param $number
     * @return bool
     */
    public function incIntegral($number) {
        global $_W;
        $chicken = $this->getInfo(true);
        $gradeData = array(
            'integral' => $chicken['integral'] + $number,
        );
        $where = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
        );
        $query = pdo_update($this->table, $gradeData, $where);
        return $query;
    }
    /**
     * 进食时间
     * @param $feed
     * @return float|int
     */
    public function calFeeding($feed) {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $setingInfo = $seting->getInfo(true);
        // 计算吃饲料的时间
        $eatTime = (float)$setingInfo['eat_time'] * (float)$feed;
        // 查询用户当前等级
        $chicket = $this->getInfo(true);
        // 当前用户等级加速
        $chicket['accelerate'];
        // 判断当前等级是否有加速
        if (isset($chicket['accelerate']) && !empty($chicket['accelerate'])) {
            // 加速后时间
            $eatTime -= (float)(($chicket['accelerate'] / 100) * $eatTime);
        }
        // 保留两位小数
        $eatTime = sprintf("%.2f", $eatTime);
        return $eatTime;
    }
    /**
     * 计算饲料数
     * @param $second
     * @return float|int
     */
    public function calFeed($second) {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $setingInfo = $seting->getInfo(true);
        // 计算吃饲料的时间
        $feed = $second / $setingInfo['eat_time'];
        return $feed;
    }
    /**
     * 更新上次鸡蛋库存
     * @param $number
     * @return bool
     */
    public function updateLastEgg($number) {
        global $_W;
        $tableName = tablename($this->table);
        $sql = " UPDATE {$tableName} SET " .
            "`last_egg_stock` = {$number} " .
            " WHERE `uniacid` = '{$_W['uniacid']}' " .
            " AND `openid` = '{$_W['openid']}' ; ";
        $query = pdo_query($sql);
        return $query;
    }
    /**
     * 添加使用鸡蛋记录
     * @param $number
     */
    public function eggLog($number) {
        global $_W;
        $table = 'ewei_open_farm_egg';
        $data = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
            'sum' => $number,
            'use_sum' => 0,
            'status' => '否',
            'receive' => '否',
            'create_time' => date('Y-m-d H:i:s'),
        );
        pdo_insert($table, $data);
    }
    /**
     * 给鸡喂食
     */
    public function feeding() {
        // 查询用户信息
        $chicken = $this->getInfo(true);
        // 判断当前是否在喂食当中(判断最后一次喂食是否有数)
        $chicken['feeding_sum'];
        // 下蛋数量
        $egg = 0;
        // 下蛋数量
        $bowl = 0;
        // 下的彩蛋
        $surprised = array();
        // 下蛋的分批数量
        $eggs = array();
        // 有最后一次饲料数
        if ($chicken['feeding_sum']) {
            // 当前时间
            $now = time();
            // 喂食时间
            $lately = strtotime($chicken['feeding_time']);
            // 饲料数
            $chicken['feeding_sum'];
            // 计算吃完所需时间
            $finish = $this->calFeeding($chicken['feeding_sum']);
            // 吃完到的时间
            $reach = $lately + $finish;
            // 判断是否吃完了
            if ($reach <= $now) {
                // 添加经验
                $this->incExperience($chicken);
                // 更新吃过的饲料数(下蛋)
                $data = $this->updateFeed($chicken);
                // 下蛋的数量
                $egg = $data['egg_sum'];
                // 下蛋的分批数量
                $eggs = $data['eggs'];
                // 查询彩蛋
                $surprised = $this->getSurprisedList();
                // 更新喂食信息
                $this->feedingEnd();
                // 喂食
                $data = $this->runFeeding($chicken);
                // 剩下的时间
                $time = $data['time'];
                // 剩下的饲料
                $bowl = $data['bowl'];
                // 没吃完
            } else {
                // 剩下的时间
                $time = $reach - $now;
                // 剩下的饲料
                $bowl = $this->calFeed($time);
                // 更新用户鸡食盆信息
                $chicken = array(
                    'bowl_stock' => $bowl,
                );
                $where = array(
                    'openid' => $chicken['openid'],
                    'uniacid' => $chicken['uniacid'],
                );
                pdo_update($this->table, $chicken, $where);
            }
            // 没有最后一次饲料数
        } else {
            // 更新喂食信息
            $this->feedingEnd();
            // 喂食
            $data = $this->runFeeding($chicken);
            // 剩下的时间
            $time = $data['time'];
            // 剩下的饲料
            $bowl = $data['bowl'];
        }
        if ($egg > 0 && count($eggs) > 0) {
            foreach ($eggs as $value) {
                // 添加鸡蛋记录
                $this->eggLog($value);
                // 添加日志
                $presentation = new Presentation_EweiShopV2Page();
                $content = "主人主人,我吃完饲料下了 {$value} 颗蛋,一定要记得领取哦~";
                $presentation->addInfo($content);
            }
        }
        $surprisedSum = count($surprised);
        if ($surprisedSum > 0) {
            foreach ($surprised as $value) {
                // 添加彩蛋日志
                $presentation = new Presentation_EweiShopV2Page();
                $content = "主人主人,恭喜你成功获得了 1 颗彩蛋,快打开看看吧~";
                $presentation->addInfo($content);
            }
        }
        $response = array(
            'time' => $time < 0 ? 0 : $time,
            'bowl' => $bowl < 0 ? 0 : $bowl,
            'egg' => $egg < 0 ? 0 : $egg,
            'eggs' => $eggs,
            'surprised' => $surprised,
        );
        $this->model->returnJson($response);
    }

    /**
     * 验证吃完饲料
     */
    public function checkFeedingEnd() {
        // 查询用户信息
        $chicken = $this->getInfo(true);
        // 判断当前是否在喂食当中(判断最后一次喂食是否有数)
        $chicken['feeding_sum'];
        // 下蛋数量
        $egg = 0;
        // 下的彩蛋
        $surprised = array();
        // 下蛋的分批数量
        $eggs = array();
        // 有最后一次饲料数
        if ($chicken['feeding_sum']) {
            // 当前时间
            $now = time();
            // 喂食时间
            $lately = strtotime($chicken['feeding_time']);
            // 饲料数
            $chicken['feeding_sum'];
            // 计算吃完所需时间
            $finish = $this->calFeeding($chicken['feeding_sum']);
            // 吃完到的时间
            $reach = $lately + $finish;
            // 判断是否吃完了
            if ($reach <= $now) {
                // 添加经验
                $this->incExperience($chicken);
                // 更新吃过的饲料数(下蛋)
                $data = $this->updateFeed($chicken);
                // 下蛋的数量
                $egg = $data['egg_sum'];
                // 下蛋的分批数量
                $eggs = $data['eggs'];
                // 查询彩蛋
                $surprised = $this->getSurprisedList();
                // 更新喂食信息
                $this->feedingEnd();
                // 剩下的时间
                $time = 0;
                // 剩下的饲料
                $bowl = 0;
                // 没吃完
            } else {
                // 剩下的时间
                $time = $reach - $now;
                // 剩下的饲料
                $bowl = $this->calFeed($time);
                // 更新用户鸡食盆信息
                $chicken = array(
                    'bowl_stock' => $bowl,
                );
                $where = array(
                    'openid' => $chicken['openid'],
                    'uniacid' => $chicken['uniacid'],
                );
                pdo_update($this->table, $chicken, $where);
            }
            // 没有最后一次饲料数
        } else {
            // 剩下的时间
            $time = 0;
            // 剩下的饲料
            $bowl = 0;
        }
        if ($egg > 0 && count($eggs) > 0) {
            foreach ($eggs as $value) {
                // 添加鸡蛋记录
                $this->eggLog($value);
                // 添加日志
                $presentation = new Presentation_EweiShopV2Page();
                $content = "主人主人,我吃完饲料下了 {$value} 颗蛋,一定要记得领取哦~";
                $presentation->addInfo($content);
            }
        }
        $surprisedSum = count($surprised);
        if ($surprisedSum > 0) {
            foreach ($surprised as $value) {
                // 添加彩蛋日志
                $presentation = new Presentation_EweiShopV2Page();
                $content = "主人主人,恭喜你成功获得了 1 颗彩蛋,快打开看看吧~";
                $presentation->addInfo($content);
            }
        }
        $response = array(
            'time' => $time < 0 ? 0 : $time,
            'bowl' => $bowl < 0 ? 0 : $bowl,
            'egg' => $egg < 0 ? 0 : $egg,
            'eggs' => $eggs,
            'surprised' => $surprised,
        );
        $this->model->returnJson($response);
    }
    /**
     * 吃完饲料
     */
    public function feedingEnd() {
        global $_W;
        // 更新用户的饲料数据
        $data = array(
            'feeding_time' => '0000-00-00 00:00:00',
            'bowl_stock' => 0,
            'feeding_sum' => 0,
        );
        $where = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
        );
        pdo_update($this->table, $data, $where);
    }
    /**
     * 查询所有没有提示过的彩蛋
     */
    public function getSurprisedList($mode = false) {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $seting = $seting->getInfo(true);
        // 系统彩蛋过期时间(小时)
        $seting['surprised_invalid_time'];
        // 用户鸡信息
        $chicken = $this->getInfo(true);
        // 用户彩蛋守护时间(小时)
        $chicken['surprised_guard'];
        // 彩蛋过期时间(小时)
        $invalid = $seting['surprised_invalid_time'] + $chicken['surprised_guard'];
        // 当前系统时间过期的彩蛋生成时间戳(界限)
        $limit = date('Y-m-d H:i:s', strtotime(" - {$invalid} hours "));
        // 用户彩蛋表
        $table = 'ewei_open_farm_user_surprised';
        $tableName = tablename($table);
        // 删除所有过期的蛋
        $sql = " DELETE FROM {$tableName} " .
            " WHERE `create_time` < '{$limit}' " .
            " AND `uniacid` = '{$_W['uniacid']}' " .
            " AND `openid` = '{$_W['openid']}' " .
            " AND `receive` = '否' ";
        pdo_query($sql);
        $table = 'ewei_open_farm_user_surprised';
        $where = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
            'status' => '否',
        );
        $infoList = pdo_getall($table, $where);
        $infoList = $this->getSurprisedDetails($infoList);
        $data = array(
            'status' => '是',
        );
        pdo_update($table, $data, $where);
        //检查$infoList
        foreach ($infoList as $key => $val)
        {
            if(empty($val))
                unset($infoList[$key]);
        }
        $infoList = array_values($infoList);
        if(!count($infoList))
            $infoList = array();
        return $infoList;
    }
    /**
     * 获取彩蛋的详情
     * @param $data
     * @return array
     */
    public function getSurprisedDetails($data) {
        global  $_W;
        if ($data && count($data) > 0) {
            // 循环彩蛋数组
            foreach ($data as $key => $value) {
                // 查询彩蛋信息
                $surprised = $this->surprisedInfo($value['surprised_id']);
                switch ($surprised['category']) {
                    case '优惠券':
                        $table = 'ewei_shop_coupon';
                        $where = array(
                            'id' => $surprised['value'],
                        );
                        $fields = array(
                            'couponname',
                            'enough',
                            'backtype',
                            'deduct',
                            'discount',
                            'backmoney',
                            'backcredit',
                            'backredpack',
                            'total',
                        );
                        $info = pdo_get($table, $where, $fields);
                        //如果优惠券数量为0,不产生优惠券彩蛋
                        $gettotal = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_data') . ' where couponid=:couponid and uniacid=:uniacid limit 1', array(':couponid' => $surprised['value'], ':uniacid' => $_W['uniacid']));
                        $left_count = $info['total'] -  $gettotal;
                        $left_count = intval($left_count);
                        if($info['total']!= -1 && $left_count <= 0)
                        {
                            unset($data[$key]);
                        }else{
                            $data[$key] = array_merge($data[$key], $surprised, $info);
                        }
                        break;
                    case '积分':
                        $data[$key] = array_merge($data[$key], $surprised);
                        break;
                    default:
                        break;
                }
            }
        }
        return array_values($data);
    }
    /**
     * 查询彩蛋详情
     * @param $id
     * @return bool
     */
    public function surprisedInfo($id) {
        $table = 'ewei_open_farm_surprised';
        $where = array(
            'id' => $id,
        );
        $field = array(
            'category',
            'value',
        );
        $query = pdo_get($table, $where, $field);
        return $query;
    }
    /**
     * 喂食
     * @param $chicken
     * @return array
     */
    public function runFeeding($chicken) {
        global $_W;
        // 查出系统设置
        $seting = new Seting_EweiShopV2Page();
        $seting = $seting->getInfo(true);
        // 当前用户饲料库存
        $chicken['feed_stock'];
        // 喂食数量
        $bowl = $chicken['feed_stock'] > $seting['bowl'] ? $seting['bowl'] : $chicken['feed_stock'];
        $data = array();
        // 计算吃饲料所需时间
        $time = $this->calFeeding($bowl);
        $data['feeding_time'] = date('Y-m-d H:i:s');
        $data['feeding_sum'] = $bowl;
        $data['lay_eggs_eat'] = $seting['lay_eggs_eat'];
        $data['bowl_stock'] = $bowl;
        $where = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
        );
        // 更新用户鸡数据
        pdo_update($this->table, $data, $where);
        $data = array(
            'bowl' => $bowl,
            'time' => $time,
        );
        $this->redFeed($bowl);
        return $data;
    }
    /**
     * 获取彩蛋
     * @param bool $method
     * @return array|bool
     */
    public function getSurprised($method = false) {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $seting = $seting->getInfo(true);
        // 系统彩蛋过期时间(小时)
        $seting['surprised_invalid_time'];
        // 用户鸡信息
        $chicken = $this->getInfo(true);
        // 用户彩蛋守护时间(小时)
        $chicken['surprised_guard'];
        // 彩蛋过期时间(小时)
        $invalid = $seting['surprised_invalid_time'] + $chicken['surprised_guard'];
        // 当前系统时间过期的彩蛋生成时间戳(界限)
        $limit = date('Y-m-d H:i:s', strtotime(" - {$invalid} hours "));
        // 用户彩蛋表
        $table = 'ewei_open_farm_user_surprised';
        $tableName = tablename($table);
        // 删除所有过期的蛋
        $sql = " DELETE FROM {$tableName} " .
            " WHERE `create_time` < '{$limit}' " .
            " AND `uniacid` = '{$_W['uniacid']}' " .
            " AND `openid` = '{$_W['openid']}' " .
            " AND `receive` = '否' ";
        pdo_query($sql);
        // 查询没有领取的用户彩蛋
        $sql = " SELECT * FROM {$tableName} " .
            " WHERE `uniacid` = {$_W['uniacid']} " .
            " AND `openid` = '{$_W['openid']}' " .
            " AND `receive` = '否' " .
            " ORDER BY `id` ASC ";
        $query = pdo_fetch($sql);
        // 查询彩蛋信息的详情
        $surprisedInfo = $this->surprisedInfo($query['surprised_id']);
        $query = array_merge($query, $surprisedInfo);
        // 判断是否是优惠券
        if ($query['category'] === '优惠券') {
            // 查询人人商城优惠券信息
            $table = 'ewei_shop_coupon';
            $where = array(
                'id' => $surprisedInfo['value'],
            );
            $fields = array(
                'couponname',
                'enough',
                'backtype',
                'deduct',
                'discount',
                'backmoney',
                'backcredit',
                'backredpack',
                'total',
            );
            $info = pdo_get($table, $where, $fields);
            $gettotal = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_data') . ' where couponid=:couponid and uniacid=:uniacid limit 1', array(':couponid' => $surprisedInfo['value'], ':uniacid' => $_W['uniacid']));
            $left_count = $info['total'] -  $gettotal;
            $left_count = intval($left_count);
            if($info['total']!=-1 && $left_count <= 0 )
            {
                $query = array();
            }else{
                $query = array_merge($query, $info);
            }
        }
        if ($method) {
            return $query;
        }
        $this->model->returnJson($query);
    }
    /**
     * 领取优惠券
     */
    public function coupon() {
        global $_W, $_GPC;
        $id = $_GPC['__input']['id'];
        $dataid = $_GPC['__input']['dataid'];
        $category = $_GPC['__input']['category'];
        $surprisedId = $_GPC['__input']['surprised_id'];
        $table = 'ewei_open_farm_user_surprised';
        $url = false;
        $where = array(
            'id' => $id,
        );
        $data = array(
            'receive' => '是',
            'status' => '是',
        );
        $query = pdo_update($table, $data, $where);
        $table = 'ewei_open_farm_surprised';
        $where = array(
            'id' => $surprisedId,
        );
        $surprised = pdo_get($table, $where);
        if ($category === '积分') {
            m('member')
                ->setCredit(
                    $_W['openid'],
                    'credit1',
                    $surprised['value'],
                    array(
                        0,
                        '农场积分彩蛋'
                    )
                );
        } else {
            $data = array(
                'id' => $dataid,
            );
            $url = mobileUrl('sale/coupon/my/detail', $data, true);
        }
        if ($query !== false) {
            $this->model->returnJson(true, false, false, $url);
        }
        $this->model->returnJson($query, false, false, $url);
    }
    /**
     * 查询所有没有提示过的彩蛋
     * @param bool $method
     * @return int
     */
    public function getEggs($method = false) {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $seting = $seting->getInfo(true);
        // 系统鸡蛋过期时间(小时)
        $seting['egg_invalid_time'];
        // 当前系统时间过期的彩蛋生成时间戳(界限)
        $limit = date('Y-m-d H:i:s', strtotime(" - {$seting['egg_invalid_time']} hours "));
        // 用户下蛋表
        $table = 'ewei_open_farm_egg';
        $tableName = tablename($table);
        // 查询今天没领取的鸡蛋
        $sql = " SELECT * FROM {$tableName}" .
            " WHERE `create_time` >= '{$limit}' " .
            " AND `uniacid` = '{$_W['uniacid']}' " .
            " AND `openid` = '{$_W['openid']}' " .
            " AND `receive` = '否' " .
            " AND `status` = '否' ";
        $eggArr = pdo_fetchall($sql);
        $eggSum = 0;
        foreach ($eggArr as $key => $value) {
            $eggSum += $value['sum'];
        }
        if ($method) {
            return $eggSum;
        }
        $this->model->returnJson($eggSum);
    }
    /**
     * 领取鸡蛋
     */
    public function receiveEgg() {
        global $_W;
        // 查询系统设置
        $seting = new Seting_EweiShopV2Page();
        $seting = $seting->getInfo(true);
        // 系统鸡蛋过期时间(小时)
        $seting['egg_invalid_time'];
        // 当前系统时间过期的彩蛋生成时间戳(界限)
        $limit = date('Y-m-d H:i:s', strtotime(" - {$seting['egg_invalid_time']} hours "));
        // 用户下蛋表
        $table = 'ewei_open_farm_egg';
        $tableName = tablename($table);
        // 查询今天没领取的鸡蛋
        $sql = " SELECT * FROM {$tableName}" .
            " WHERE `create_time` >= '{$limit}' " .
            " AND `uniacid` = '{$_W['uniacid']}' " .
            " AND `openid` = '{$_W['openid']}' " .
            " AND `receive` = '否' " .
            " AND `status` = '否' ";
        $eggArr = pdo_fetchall($sql);
        $eggSum = 0;
        $eggIdArr = array();
        foreach ($eggArr as $key => $value) {
            $eggSum += $value['sum'];
            $eggIdArr[] = $value['id'];
        }
        $idStr = implode(',', $eggIdArr);
        // 领取所有未过期的蛋
        $this->receiveEggLog($idStr);
        $this->incEggs($eggSum);
        $chicken = $this->getInfo(true);
        $this->updateLastEgg($chicken['egg_stock']);
        pdo_query($sql);
        $this->model->returnJson($eggSum);
    }
    /**
     * 更新蛋日志
     * @param $idStr
     */
    public function receiveEggLog($idStr) {
        $sql = " UPDATE `ims_ewei_open_farm_egg` " .
            " SET `receive` = '是' " .
            " WHERE `id` IN ({$idStr}); ";
        pdo_query($sql);
    }
    /**
     * 异步下载头像
     */
    public function downloadPortrait() {
        // 获取用户信息
        $chicken = $this->getInfo(true);
        // 用户头像
        $chicken['portrait'];
        // 保存地址
        $saveFolder = __DIR__ . '/../../static/mobile/portrait/';
        if (!file_exists($saveFolder)) {
            mkdir($saveFolder);
        }
        $filePath = $saveFolder.$chicken['uniacid'].'-'.$chicken['openid'].'.jpg';
        if(!is_file($filePath)){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $chicken['portrait']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            $file = curl_exec($ch);
            curl_close($ch);
            $resource = fopen($filePath, 'a');
            fwrite($resource, $file);
            fclose($resource);
        }
//        // 异步下载头像
//        $runCommunication = "nohup wget -O {$saveFolder}{$chicken['uniacid']}-{$chicken['openid']}.jpg -x {$chicken['portrait']} >> {$saveFolder}download.log 2>&1 &";
//        shell_exec("{$runCommunication}");
    }
}