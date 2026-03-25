<?php


class PcModel extends PluginModel
{

    /**
     * 顶部导航菜单
     */
    const MENU_TOP = 0;

    /**
     * 底部导航菜单
     */
    const MENU_BOTTOM = 1;


    /**
     * 商品ID
     */
    const GOODS_ID = 0;

    /**
     * 商品分类
     */
    const GOODS_CATE = 1;

    /**
     * 商品组
     */
    const GOODS_GROUP = 2;

    /**
     * 类Map
     * @var array
     */
    protected static $classMap = array();

    public $set;

    public function __construct($name = '')
    {
        global $_W;
        if (empty($this->set)) {
            $setting = pdo_get('ewei_shop_domain_bindings', array('uniacid' => $_W['uniacid']));
            $this->set = $setting;
        }
//        dd($this->set);
        parent::__construct($name);
    }

    /**
     * 获取某个页面的数据
     * 以下划线开头的都是获取数据的方法
     * @param $page
     * @return mixed
     * @author: Vencenty
     * @time: 2019/5/24 16:52
     */
    public function getData($page)
    {
        $method = "_{$page}Data";
        // 方法不存在的话,直接抛出错误
        if (!method_exists($this, $method)) {
            die('获取数据错误,没有相应的方法');
        }

        // 获取数据
        $data = $this->$method();

        return $data;
    }


    /**
     * 获取首页数据
     * @author: Vencenty
     * @time: 2019/5/24 16:55
     */
    protected function _homeData()
    {
        global $_W, $_GPC;

        $data = array(
            // 广告
            'advs' => $this->getAdvSettings(),
            // 商品组|楼层
            'goodsGroups' => $this->getGoodsGroupSettings(),
            // 猜你喜欢商品,目前是随机获取几个正常的商品
            'guessFavorGoods' => $this->getGuessFavorGoods()
        );


        return $this->setFullPath($data);
    }

    /**
     * 猜你喜欢商品
     * @author: Vencenty
     * @time: 2019/5/27 11:02
     */
    public function getGuessFavorGoods()
    {
        global $_W;
        $goods = pdo_fetchall("select id,thumb,title,marketprice,productprice,minprice from " . tablename('ewei_shop_goods') . " where uniacid =:uniacid and `deleted` = 0 and `checked` = 0 and status=1 and total > 0 and
         `type` =1 order by rand() limit 5", array('uniacid' => $_W['uniacid']));

        // 带上全部参数
        array_walk($goods, function (&$value) {
            $value['url'] = pcUrl('pc.goods.detail', array('id' => $value['id']), true);
            $value['thumb'] = tomedia($value['thumb']);
        });

        return $goods;

    }


    /**
     * 获取树形结构分类数据
     * @author: Vencenty
     * @time: 2019/5/25 11:26
     */
    public function getAllCategories()
    {
        global $_W;
        $data = pdo_getall("ewei_shop_category",
            array("uniacid" => $_W['uniacid'], 'enabled' => 1),
            array('id', 'parentid', 'name', 'level'),
            '',
            'displayorder desc'
        );


        // 无限极分类
        $unlimitedTree = $this->unlimitedSort($data, 0, 'parentid', 'child');
        // 设置分类列表的打开方式, 可选属性有
        // _blank, _parent, _self, _top, 当前选用调新页的方式
        // $unlimitedTree['open_type'] = '_self';

        return $unlimitedTree;
    }

    /**
     * 获取侧边栏信息和设置信息
     *
     */
    public function getSetting()
    {
        global $_W;
        $data = m('common')->getPluginset('pc');
        if (!empty($data['search'])) {
            str_replace('，', ',', $data['search']);
            $data['search'] = explode(',', $data['search']);
        }
        return $data;
    }

    /**
     * 获取广告设置
     * @param $key
     * @return array|null
     * @author: Vencenty
     * @time: 2019/5/23 19:45
     */
    public function getAdvSettings($key = null)
    {
        global $_W;
        $settings = pdo_get('ewei_shop_pc_adv', array('uniacid' => $_W['uniacid']));
        $settings = json_decode($settings['settings'], true);


        if (is_null($key)) {
            return $settings;
        }
        return isset($settings[$key]) ? $settings[$key] : null;
    }

    /**
     * 获取导航栏菜单
     * @author: Vencenty
     * @time: 2019/5/24 17:11
     */
    public function getMenuSettings()
    {
        global $_W;
        $menus = pdo_getall(
            "ewei_shop_pc_menu",
            array('uniacid' => $_W['uniacid'], 'status' => 1),
            array('link', 'type', 'title', 'displayorder'),
            array(),
            'displayorder desc'
        );

        // 区分顶部和底部菜单
        $result = array();
        foreach ($menus as $menu) {
            if ($menu['type'] == static::MENU_TOP) {
                $result['top'][] = $menu;
            } else {
                $result['bottom'][] = $menu;
            }
        }

        return $result;
    }

    /**
     * 获取商品组列表|楼层设置
     * @author: Vencenty
     * @time: 2019/5/24 17:22
     */
    public function getGoodsGroupSettings()
    {
        global $_W;
        // 楼层限制(不允许超过6个)
        $floorLimit = 9;
        $settings = pdo_getall("ewei_shop_pc_goods", array(
            'uniacid' => $_W['uniacid'],
            'status' => 1
        ), array(), '', 'sort desc', $floorLimit);
        // 循环读取里面的设置
        array_walk($settings, function (&$setting) {
            $setting['goods'] = $this->fetchGoodsByType($setting['goods_info'], $setting['goods_type']);
        });

        return $settings;

    }


    /**
     * 根据商品类型获取商品
     * @param $id
     * @param $type
     * @return array|bool
     * @author: Vencenty
     * @time: 2019/5/25 11:06
     */
    public function fetchGoodsByType($id, $type)
    {
        global $_W;

        if (!in_array($type, array(static::GOODS_ID, static::GOODS_CATE, static::GOODS_GROUP))) {
            return false;
        }

        $goods = array();

        switch ($type) {
            case static::GOODS_ID:
                $goods = $this->fetchGoods($id);
                break;
            case static::GOODS_CATE:
                // 传递进来的是商品分类id
                $cateId = $id;
                $goodsIds = pdo_fetchall("select id from " . tablename('ewei_shop_goods') . " where uniacid = :uniacid  AND FIND_IN_SET({$cateId}, cates) ", array(
                    ':uniacid' => $_W['uniacid']
                ));
//                // 获取所有的商品ID
                $goodsIds = implode(',', array_column($goodsIds, 'id'));
                $goods = $this->fetchGoods($goodsIds);
                break;
            case static::GOODS_GROUP:
                $goodsIds = pdo_fetchcolumn("select goodsids from " . tablename('ewei_shop_goods_group') . " where uniacid = :uniacid and id = :id", array(
                    ':uniacid' => $_W['uniacid'],
                    ':id' => $id
                ));
                $goods = $this->fetchGoods($goodsIds);
                break;
        }

        return $goods;
    }

    /**
     * 获取商品
     * @param $ids
     * @param string $fields
     * @param int $limit
     * @return array|bool
     * @author: Vencenty
     * @time: 2019/5/25 10:47
     */
    public function fetchGoods($ids, $fields = 'id,title,marketprice,productprice,thumb,displayorder,total,minprice', $limit = 8)
    {
        global $_W;

        $goods = pdo_fetchall("select $fields from " . tablename('ewei_shop_goods') . " where uniacid =:uniacid and id in ({$ids}) limit $limit", array(
            ":uniacid" => $_W['uniacid'],
        ));

        // 带上全部参数
        array_walk($goods, function (&$value) {
            $value['url'] = pcUrl('pc.goods.detail', array('id' => $value['id']), true);
        });

        return $goods;
    }

    /**
     * 给结果附加参数
     * @param $goods
     * @param $field
     * @param $params
     * @author: Vencenty
     * @time: 2019/5/28 13:32
     */
    protected function attachParams($goods, $field, $params)
    {

    }

    /**
     * 给哪些字段设置tomedia方法,后期什么字段需要这个函数直接添加对应字段即可
     * @param null $data
     * @return false|null
     * @author: Vencenty
     * @time: 2019/5/27 11:15
     */
    protected function setFullPath(&$data = null)
    {
        // 需要设置全路径的key
        $allKeys = array(
            'thumb', 'import_image', 'bottom_image', 'top_image',
            'img', 'bottom_adv_image'
        );

        array_walk_recursive($data, function (&$value, $index) use ($allKeys) {
            if (in_array($index, $allKeys)) {
                    $value = empty($value) ? false : tomedia($value);
            }
        });

        return $data;
    }


    /**
     * 获取无限极分类
     * @param $data
     * @param $pid
     * @param string $field
     * @param string $childNode
     * @return array
     * @author: Vencenty
     * @time: 2019/5/25 11:38
     */
    public function unlimitedSort($data, $pid, $field = 'parentid', $childNode = 'child')
    {
        $tree = array();
        foreach ($data as $item) {
            if ($item[$field] == $pid) {
                // 单独添加分类的url
                $item['url'] = pcUrl('list', array('cate' => $item['id']));
                $item[$childNode] = $this->unlimitedSort($data, $item['id'], $field);
                // 卸载掉空的数组元素
                if ($item[$childNode] == null) {
                    unset($item[$childNode]);
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }


    /**
     * PC端自定义链接选择器
     * @return array
     * @author: Vencenty
     * @time: 2019/5/23 21:05
     */
    public function getLinkList()
    {
        return array(
            array(
                'name' => '商城',
                'list' => array(
                    array(
                        'name' => '首页',
                        'url_wxapp' => pcUrl(''),
//                        'url_wxapp' => '刘瑞琦',
                    ),
                    array(
                        'name' => '商品列表页',
                        'url_wxapp' => pcUrl('list'),
//                        'url_wxapp' => '故事的小黄花'
                    ),
                    array(
                        'name' => '个人中心',
                        'url_wxapp' => pcUrl('member'),
//                        'url_wxapp' => '离开的借口'
                    ),
                    array(
                        'name' => '购物车',
                        'url_wxapp' => pcUrl('member.cart'),
//                        'url_wxapp' => '离开的借口'
                    ),
                )
            ),
            array(
                'name' => '插件类',
                'list' => array(
                    array(
                        'name' => '拼团列表',
                        'url_wxapp' => pcUrl('list.groups'),
//                        'url_wxapp' => '离开的借口'
                    ),
                    array(
                        'name' => '砍价列表',
                        'url_wxapp' => pcUrl('list.bargain'),
//                        'url_wxapp' => '宁死不屈小强'
                    ),
                    array(
                        'name' => '秒杀列表',
                        'url_wxapp' => pcUrl('list.seckill'),
//                        'url_wxapp' => '老王的傻儿子'
                    )
                )
            )
        );
    }


    /**
     * 获取模板全局变量,全局都需要注入的变量需要写到这里
     * @author: Vencenty
     * @time: 2019/5/25 14:47
     */
    public function getTemplateGlobalVariables()
    {
        global $_W, $_GPC;
        if (empty($_W['openid'])) {
            $userinfo = $this->checkOpenid();
            if (!$userinfo) {
                $userinfo = array();
            }
        } else {
            $userinfo = m('member')->getMember($_W['openid']);
        }
        if (!empty($userinfo)) {
            $userinfo['cartcount'] = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('ewei_shop_member_cart') . " WHERE openid = :openid and deleted =0", array(':openid' => $userinfo['openid']));
        }

        return array(
            // 全局参数
            'params' => $_GPC,
            // 上下导航链接
            'menus' => $this->getMenuSettings(),
            // 获取商品分类
            'categories' => $this->getAllCategories(),
            // 获取页面布局
            'layout' => $this->getTemplateSetting(),
            // 导航链接
            'navs' => array(
                // 登录
                'login' => pcUrl('member.login', '', true),
                // 注册
                'register' => pcUrl('member.register', '', true),
                // 订单
                'order' => pcUrl('order', '', true),
                // 购物车
                'cart' => pcUrl('member.cart', '', true),
                // 个人中心
                'user_center' => pcUrl('member', '', true),
                // 我的收藏
                'favor' => pcUrl('member.favor', '', true),
                //登出
                'loginout' => pcUrl('member.logout', '', true),
            ),
            'userinfo' => $userinfo,
            // PC设置
            'setting' => $this->getSetting(),
            // 小程序设置,商城LOGO名称乱七八糟的在这里面
            'shopSetting' => m('common')->getSysset('shop'),
        );
    }

    public function getHomeLayout()
    {
    }


    /**
     * 调用app下面文件的某个方法,具体用法 self::invoke(goods.index::main)
     * 因为控制器一般没有参数,所以此处不考虑参数问题
     * @param $name
     * @param $returnArray bool 是否返回数组,false返回默认控制器结果
     * @return mixed
     * @author: Vencenty
     * @time: 2019/5/29 15:04
     */
    public function invoke($name, $returnArray = true)
    {
        $appFilePath = EWEI_SHOPV2_PLUGIN . '/app/core/mobile/';

        $splitArr = explode('::', $name);
        // 切割后的数组，最后一个元素是要调用的方法
        $name = current($splitArr);
        $method = end($splitArr);

        // 如果存在直接返回,避免重复实例化
        if (isset(static::$classMap[$name])) {
            if ($returnArray) {
                return json_decode(static::$classMap[$name]->$method(), true);
            }
            return static::$classMap[$name]->$method();
        }

        $path = explode('.', $name);

        // 最后一个元素肯是文件名称
        $className = array_pop($path);

        $fileName = $className . '.php';

        $path[] = $fileName;

        $fileName = implode($path, '/');

        $realPath = $appFilePath . $fileName;

        if (!is_file($realPath)) {
            die("{$realPath}下面的{$fileName}不存在");
        }

        include $realPath;
        $className = ucfirst($className) . '_EweiShopV2Page';
        $instance = new $className();
        static::$classMap[$name] = $instance;

        if ($returnArray) {
            return json_decode($instance->$method(), true);
        }

        return $instance->$method();

    }


    /**
     * 检测登录
     * @return mixed
     * author sunc
     */
    public function checkLogin()
    {

        global $_W;

        if (empty($_W['openid'])) {

            $openid = $this->checkOpenid();
            if (!empty($openid)) {
                return $_W['openid'] = $openid['openid'];
            }
            $url = urlencode(base64_encode($_SERVER['QUERY_STRING']));
            $loginurl = pcUrl('pc/member/login');

            if ($_W['isajax']) {
                show_json(0, array('url' => $loginurl, 'message' => '请先登录!'));
            }

            header('location: ' . $loginurl);
            exit;
        }
    }

    /**
     * 输入商品ID，获取商品面包屑导航
     * @param $goodsId
     * @return array
     * @author: Vencenty
     * @time: 2019/6/11 18:21
     */
    public function getBreadcrumb($goodsId)
    {
        global $_W;
        $result = array(
            array(
                'link' => pcUrl('list'),
                'title' => '全部商品',
            )
        );

        // 获取所有商品
        $goods = pdo_get('ewei_shop_goods', array('id' => $goodsId), array('ccate', 'tcate', 'pcate', 'title'));
        $title = $goods['title'];
        unset($goods['title']);
        $categoriesIds = array_values($goods);

        $r = pdo_fetchall("select id,`name`,`level`,`parentid` from " . tablename('ewei_shop_category') . " where uniacid = :uniacid and enabled = 1 and id in(" . implode(',', $categoriesIds) . ")", array(':uniacid' => $_W['uniacid']));
        $levelCondition = array_column($r, 'level');
        // 根据等级排序
        array_multisort($r, SORT_ASC, $levelCondition);

        array_walk($r, function (&$item) use (&$result) {
            $data = array(
                'title' => $item['name'],
                'link' => pcUrl('list', array('cate' => $item['id']))
            );
            array_push($result, $data);
        });

        $result[] = array(
            'link' => 'javascript:void(0)',
            'title' => $title
        );

        return $result;
    }


    /**
     * @return mixed
     * 检测openid
     * author sunc
     */
    public function checkOpenid()
    {
        global $_W, $_GPC;
        $key = '__ewei_shopv2_member_session_' . $_W['uniacid'];
        if (isset($_GPC[$key])) {
            $session = json_decode(base64_decode($_GPC[$key]), true);
            if (is_array($session)) {
                $member = m('member')->getMember($session['openid']);
                if (is_array($member) && $session['ewei_shopv2_member_hash'] == md5($member['pwd'] . $member['salt'])) {
                    $GLOBALS['_W']['ewei_shopv2_member_hash'] = md5($member['pwd'] . $member['salt']);
                    $GLOBALS['_W']['ewei_shopv2_member'] = $member;
                    return $member;
                } else {
                    isetcookie($key, false, -100);
                }
            }
        }
    }

    public function getOrderCount()
    {
        global $_W;
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $statics = array(
                'order_0' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=0 and (isparent=1 or (isparent=0 and parentid=0)) and paytype<>3 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_1' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and (status=1 or (status=0 and paytype=3)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_2' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and (status=2 or (status=1 and sendtype>0)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_4' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and refundstate>0 and isparent=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_3' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and uniacid=:uniacid and status=3 and userdeleted=0', $params),
            );
        } else {
            $statics = array(
                'order_0' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and status=0 and isparent=0 and paytype<>3 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_1' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and (status=1 or (status=0 and paytype=3)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_2' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and (status=2 or (status=1 and sendtype>0)) and isparent=0 and refundid=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_4' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and refundstate>0 and isparent=0 and uniacid=:uniacid and istrade=0 and userdeleted=0', $params),
                'order_5' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and uniacid=:uniacid and iscycelbuy=1 and status in(0,1,2) and userdeleted=0', $params),
                'order_3' => pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and ismr=0 and uniacid=:uniacid and status=3 and userdeleted=0', $params)
            );
        }
        return $statics;
    }

    /**
     * 获取当前PC模板设置
     * @author: Vencenty
     * @time: 2019/6/12 11:37
     */
    public function getTemplateSetting()
    {
        global $_W, $_GPC;
        $data = pdo_get('ewei_shop_pc_template', array('uniacid' => $_W['uniacid']));
        // 当前设置
        $setting = json_decode($data['setting'], true);

        // 如果设置是空的,读取一个默认配置
        if (empty($setting)) {
            $setting = array('seckill' => array('text' => '秒杀栏', 'visible' => 1, 'order' => 0, 'block' => 'seckill', 'display' => 1), 'select' => array('text' => '为您优选', 'visible' => 1, 'order' => 1, 'block' => 'select', 'display' => 1), 'goods' => array('text' => '商品组', 'visible' => 1, 'order' => 2, 'block' => 'goods', 'display' => 1));
        }
        // 排序字段
        $orderField = array_column($setting, 'order');
        // 排好顺序
        array_multisort($orderField, SORT_ASC, $setting);

        foreach ($setting as $key => $item) {
            $supportSortArea = array('goods', 'seckill', 'select');
            if (in_array($key, $supportSortArea)) {
                $setting['sortBlock'][] = $item;
            }
        }


        return $setting;
    }


    /**
     * 获取用户浏览记录,并且进行设置
     * @param $goodsId int
     * @param int $rowNumber
     * @return array|bool
     * @author: Vencenty
     * @time: 2019/6/12 16:46
     */
    public function getUserFooterMark($goodsId, $rowNumber = 3)
    {
        global $_W, $_GPC;

        $userInfo = $this->getUserInfo();

        if (!$userInfo) {
            return array();
        }

        // 获取用户浏览记录
        $footerMarkGoodsIDs = $this->getOrSetUserBrowseHistoryId($userInfo['id'], $goodsId, $rowNumber);
        // 没有记录返回false
        if (!$footerMarkGoodsIDs) {
            return array();
        }

        $footerMarkGoodsIDs = implode(',', $footerMarkGoodsIDs);

        $goodsFooterMark = pdo_fetchall("select id,title,thumb,marketprice from " . tablename('ewei_shop_goods') . " where id in ({$footerMarkGoodsIDs})");


        // 返回商品足迹信息
        array_walk($goodsFooterMark, function (&$item) {
            $item['thumb'] = tomedia($item['thumb']);
        });

        return $goodsFooterMark;
    }


    /**
     * 获取或者创建用户浏览历史记录
     * @param $userId int 用户ID
     * @param $goodsId int 商品ID
     * @param $rowNumber int 记录条数
     * @return array|bool|null
     * @author: Vencenty
     * @time: 2019/6/12 15:39
     */
    public function getOrSetUserBrowseHistoryId($userId, $goodsId, $rowNumber)
    {
        global $_W, $_GPC;
        $data = pdo_get("ewei_shop_pc_browse_history", array('uid' => $userId));
        // 没有记录的情况下先生成一条记录
        if (!$data) {
            $history = array();
            $history[] = array(
                'id' => $goodsId,
                'time' => time(),
            );
            $data = array(
                'uid' => $userId,
                'uniacid' => $_W['uniacid'],
                'history' => json_encode($history)
            );
            pdo_insert('ewei_shop_pc_browse_history', $data);

            // 返回浏览记录中的id
            return array();
        }


        // 如果已经存在历史记录,获取所有的浏览记录
        $browseHistory = json_decode($data['history'], true);

        // 浏览商品的id集合
        $browseGoodsIDs = array_column($browseHistory, 'id');
        // 获取浏览信息
        $browseInfo = array('time' => time(), 'id' => $goodsId);

        if (in_array($goodsId, $browseGoodsIDs)) {
            // 如果记录已经存在,先把当前记录删除掉,然后塞到数组最前面
            foreach ($browseHistory as $key => $item) {
                if ($item['id'] == $goodsId) {
                    unset($browseHistory[$key]);
                }
            }
        }
        // 否则的话不删除原先记录,直接
        array_unshift($browseHistory, $browseInfo);

        $history = json_encode($browseHistory);
        // 更新记录
        pdo_update('ewei_shop_pc_browse_history', array('history' => $history), array('id' => $data['id']));
        $goodsIds = array_column($browseHistory, 'id');

        // 存在多个浏览记录的情况下,从第二个浏览记录开始截取,往后截取三个,因为第一个元素是当前浏览的商品
        return array_splice($goodsIds, 1, $rowNumber);

    }


    /**
     * 获取用户信息
     * @return mixed
     * @author: Vencenty
     * @time: 2019/6/13 10:23
     */
    public function getUserInfo()
    {
        return $this->checkOpenid();
    }


    /**
     * 检查商品是否存在
     * @param $goodsId
     * @return bool
     * @author: Vencenty
     * @time: 2019/6/14 11:22
     */
    public function checkGoodsExists($goodsId)
    {
        return (bool)pdo_fetchcolumn("select count(*) from " . tablename('ewei_shop_goods') . " where id = :id", array(":id" => $goodsId));
    }

}