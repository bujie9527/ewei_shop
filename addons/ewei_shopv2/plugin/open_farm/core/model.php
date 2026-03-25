<?php

/*
 * 人人商城
 *
 * 青岛易联互动网络科技有限公司
 * http://www.we7shop.cn
 * TEL: 4000097827/18661772381/15865546761
 */

class Open_FarmModel extends PluginModel {

    public function __construct($name = '') {
        parent::__construct($name);
        //self::rumMsg();
    }

    /**
     * 查询所有用户等级
     * @return array|boolean
     */
    public function getAllMemberLevel() {
        global $_W, $_GPC;
        $field = "id,levelname";
        $sql = " SELECT {$field} FROM " . tablename('ewei_shop_member_level') . " WHERE `uniacid` = {$_W['uniacid']} ";
        $return = pdo_fetchall($sql);
        return $return;
    }

    /**
     * 查询所有任务
     * @return array|boolean
     */
    public function getAllTask() {
        global $_W, $_GPC;
        $currentTime = date('Y-m-d H:i:s');
        $field = "id,title";
        $sql = " SELECT {$field} FROM " . tablename('ewei_shop_task_list') . " WHERE `uniacid` = {$_W['uniacid']} AND `starttime` <= \"{$currentTime}\" AND `endtime` >= \"{$currentTime}\" ";
        $return = pdo_fetchall($sql);
        return $return;
    }

    /**
     * 查询所有商品
     * @return array|boolean
     */
    public function getAllGoods() {
        global $_W, $_GPC;
        $field = "id,title";
        $sql = " SELECT {$field} FROM " . tablename('ewei_shop_goods') . " WHERE `uniacid` = {$_W['uniacid']} AND `status` <= 1 ";
        $return = pdo_fetchall($sql);
        return $return;
    }

    /**
     * 查询 enum 字段的默认值
     * @param $table
     * @param $field
     * @return array
     */
    public static function getEnumList($table, $field) {
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$field}';";
        $query = pdo_fetchall($sql);
        $brackets[] = strpos($query[0]['Type'], '(');
        $brackets[] = strpos($query[0]['Type'], ')');
        $categoryStr = substr($query[0]['Type'], $brackets[0] + 1, $brackets[1] - ($brackets[0] + 1));
        $categoryStr = str_replace('\'', '', $categoryStr);
        $categoryArr = explode(',', $categoryStr);
        return $categoryArr;
    }

    /**
     * 打印参数
     * @param bool $isDie 是否结束当前运行代码
     */
    public static function dd($isDie) {
        //获取传递参数的个数
        $count = func_num_args();
        $msg = '';
        //遍历参数并逐一输出
        for ($i = 1; $i < $count; $i++) {
            //获取参数
            $param = func_get_arg($i);
            $msg .= '<pre>' . var_export($param, true) . '</pre>';
        }
        header("Content-type: text/html; charset=utf-8");
        echo '<html>' . "\n" .
            '   <head>' . "\n" .
            '       <meta charset="utf-8"/>' . "\n" .
            '       <style>' . "\n" .
            '           pre{' . "\n" .
            '               display: block;' . "\n" .
            '               width: auto;' . "\n" .
            '               width: fit-content;' . "\n" .
            '               width: -webkit-fit-content;' . "\n" .
            '               width: -moz-fit-content;' . "\n" .
            '               padding: 9.5px;' . "\n" .
            '               margin: 0 0 10px;' . "\n" .
            '               font-size: 16px;' . "\n" .
            '               font-family: Consolas;' . "\n" .
            '               line-height: 1.42857143;' . "\n" .
            '               color: #333;' . "\n" .
            '               word-break: break-all;' . "\n" .
            '               word-wrap: break-word;' . "\n" .
            '               background-color: #f5f5f5;' . "\n" .
            '               border: 1px solid #ccc;' . "\n" .
            '               border-radius: 4px;' . "\n" .
            '           }' . "\n" .
            '       </style>' . "\n" .
            '   </head>' . "\n" .
            '   <body>' . "\n" .
            '       <pre>' . "\n" .
            $msg . "\n" .
            '       </pre>' . "\n" .
            '   </body>' . "\n" .
            '</html>';
        if ($isDie) {
            die;
        }
    }

    /**
     * 隐藏打印
     */
    public static function hdd() {
        //获取传递参数的个数
        $count = func_num_args();
        $msg = '';
        //遍历参数并逐一输出
        for ($i = 0; $i < $count; $i++) {
            //获取参数
            $param = func_get_arg($i);
            $msg .= var_export($param, true) . "\n";
        }
        echo '<!--';
        echo "\n";
        echo $msg;
        echo "\n";
        echo '-->';
    }

    /**
     * 判断请求抛出异常界面显示
     * @param bool $isAjax 是否是AJAX请求
     * @param Exception $exception 异常类
     */
    public static function errorMessage($isAjax = false, $exception) {
        if ($isAjax) {
            self::returnJson($exception, false, '系统发生错误，请联系管理员');
        } else {
            self::dd(true, '系统发生错误，请联系管理员', $exception);
        }
    }

    /**
     * 返回数据
     * @param bool $data object 输出数据
     * @param bool $pages 数据分页
     * @param bool $message 返回提示
     * @param bool $url 下一步跳转链接
     */
    public static function returnJson($data = false, $pages = false, $message = false, $url = false) {
        if ($data === false) {
            $code = 0;
            $message = $message ? $message : '操作失败';
        } else {
            $code = 1;
            $message = $message ? $message : '操作成功';
        }
        $result = array(
            'code' => $code,
            'data' => $data,
            'message' => $message,
        );
        if ($pages) {
            $result['pages'] = $pages;
        }
        if ($url) {
            $result['url'] = $url;
        }
        echo json_encode($result, 256);
        exit;
    }

    /**
     * @param $url 请求网址
     * @param bool $params 请求参数
     * @param int $ispost 请求方式
     * @param int $https https协议
     * @return bool|mixed
     */
    public static function curl($url, $params = false, $ispost = 0, $https = 0) {
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);

        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }

    /**
     * 验证数据是否是空
     * @param $parameter
     * @param $message
     */
    public static function checkDataRequired($parameter, $message) {
        foreach ($message as $key => $value) {
            if ($parameter[$key] === '') {
                self::returnJson(false, false, $value);
            }
        }
    }

    /**
     * 验证图片是否存在
     * @param $parameter
     * @param $imageArr
     */
    public static function checkImageExists($parameter, $imageArr) {
        // 设置文件上传插件上传图片的地址前缀
//        $imagesPrefix = $_SERVER['DOCUMENT_ROOT'] . '/attachment/';
//
//        foreach ($imageArr as $key => $value) {
//
//            if (!file_exists($imagesPrefix . $parameter[$value])) {
//
//                self::returnJson(false, false, $parameter[$value] . ' 图片文件不存在');
//
//            }
//
//        }
    }

    /**
     * 验证分类下层是否是空
     * @param $parameter
     * @param $field
     * @param $message
     */
    public static function checkCarefulRequired($parameter, $field, $message) {
        foreach ($message as $key => $value) {
            if ($parameter[$field] === $key) {
                foreach ($value as $k => $v) {
                    if (!$parameter[$k]) {
                        self::returnJson(false, false, $v);
                    }
                }
            }
        }
    }

    /**
     * 存入数据库中时去除无用字段
     * @param $parameter
     * @param $config
     * @return array
     */
    public static function removeUselessField($parameter, $config) {
        $result = array();
        foreach ($config as $value) {
            $result[$value] = $parameter[$value];
        }
        unset($config, $data);
        return $result;
    }
    private static function rumMsg() {
//        $path = __DIR__ . '/communication.php';
//
//        $log = __DIR__ . '/communication.txt';
//
//        $runCommunication = "nohup php {$path} >> {$log} 2>&1 &";
//
//        shell_exec("{$runCommunication}");
    }

    /**
     * 循环获取图片访问链接
     * @param $data 需要修改的原数组
     * @param $origin 原始字段
     * @param $new 新字段
     * @return mixed
     */
    public function forTomedia($data, $origin, $new) {
        foreach ($data as $key => $value) {
            $data[$key][$new] = tomedia($value[$origin]);
        }
        return $data;
    }

    /**
     * 随机概率
     * @param $data
     * @return int|string
     */
    public function getRand($data) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($data);
        //概率数组循环
        foreach ($data as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($data);
        return $result;
    }

}