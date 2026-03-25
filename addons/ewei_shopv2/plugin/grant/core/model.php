<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class GrantModel extends PluginModel
{
    public function checkplugin($identity)
    {

        global $_W;
        $uniacid = $_W['uniacid'];
        //$acid = pdo_fetch("SELECT acid,uniacid FROM " . tablename('account_wechats') . " WHERE uniacid=:uniacid limit 1", array(':uniacid' => $uniacid));
        $setting = pdo_fetch("select * from " . tablename('ewei_shop_system_plugingrant_setting') . " where 1 = 1 limit 1 ");
        $plugins = array_filter(explode(',',$setting['plugin']));
        $coms = array_filter(explode(',',$setting['com']));

        if (false === in_array($identity,$plugins)  && false === in_array($identity,$coms)) {
            $plugin = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_system_plugingrant_log') . " WHERE uniacid = " . $uniacid . " and `identity` = '" . $identity . "'order by permendtime desc ");

            if ($plugin['month'] == 0 && $plugin['isperm'] == 1) {
                return true;
            } elseif ($plugin['month'] > 0 && $plugin['isperm'] == 1) {
                if ($plugin['permendtime'] < time()) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }
        //授权应用管理设置

        return true;
    }

    /*
     * 应用授权
     * */
    public function pluginGrant($id)
    {
        $item = pdo_fetch("SELECT uniacid,pluginid,`month` FROM " . tablename('ewei_shop_system_plugingrant_log') . " WHERE id = " . $id . " and isperm = 0  ");
        if (empty($item)) {
            message('抱歉，该记录不存在！', '', 'error');
        }
        $data = array('isperm' => 1);
        $lastitem = pdo_fetch("SELECT MAX(permendtime) as permendtime,permlasttime FROM " . tablename('ewei_shop_system_plugingrant_log') . " 
                            WHERE uniacid = " . $item['uniacid'] . " and pluginid = " . $item['pluginid'] . " and isperm = 1 limit 1");
        if (!empty($lastitem) && $lastitem['permendtime'] > 0) {
            $data['permendtime'] = strtotime("+" . $item['month'] . " month", $lastitem['permendtime']);
            $data['permlasttime'] = $lastitem['permendtime'];
        } else {
            $data['permendtime'] = strtotime("+" . $item['month'] . " month");
        }
        pdo_update('ewei_shop_system_plugingrant_log', $data, array('id' => $id));
    }

    public function wechat_native_build($params, $wechat, $type = 0)
    {
        global $_W;
        $package = array();
        $package['appid'] = $wechat['appid'];
        $package['mch_id'] = $wechat['mchid'];
        $package['nonce_str'] = random(32);
        $package['body'] = $params['title'];
        $package['device_info'] = isset($params['device_info']) ? "ewei_shopv2:" . $params['device_info'] : "ewei_shopv2";
        $package['attach'] = (isset($params['uniacid']) ? $params['uniacid'] : $_W['uniacid']) . ':' . $type;
        $package['out_trade_no'] = $params['tid'];
        $package['total_fee'] = $params['fee'] * 100;
        $package['spbill_create_ip'] = CLIENT_IP;
        $package['product_id'] = $params['tid'];
        if (!empty($params["goods_tag"]))
            $package['goods_tag'] = $params['goods_tag'];  //商品标记，代金券或立减优惠功能的参数
        $package['time_start'] = date('YmdHis', TIMESTAMP);
        $package['time_expire'] = date('YmdHis', TIMESTAMP + 3600);
        $package['notify_url'] = empty($params['notify_url']) ? $_W['siteroot'] . "addons/ewei_shopv2/payment/wechat/notify.php" : $params['notify_url'];
        $package['trade_type'] = 'NATIVE';
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key={$wechat['apikey']}";
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
        if (is_error($response)) {
            return $response;
        }
        libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if (strval($xml->return_code) == 'FAIL') {
            return error(-1, strval($xml->return_msg));
        }
        if (strval($xml->result_code) == 'FAIL') {
            return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
        }
        $result = json_decode(json_encode($xml), true);
        return $result;
    }

    public function aliconfig()
    {
        global $_W;
        $setting = pdo_fetch("select * from " . tablename('ewei_shop_system_plugingrant_setting') . " where 1 = 1 limit 1 ");
        $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
        $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
        $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
        $config = array(
            //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
            'partner' => $setting['partner'],
            //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
            'seller_id' => $setting['partner'],
//		'seller_id'=>'yilianhudong@163.com',
            // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
            'key' => $setting['secret'],
            // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
            'notify_url' => $_W['siteroot'] . 'addons/ewei_shopv2/payment/alipay/pluginverify.php',
            // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
            'return_url' => webUrl('plugingrant/success', array(), true),
            //签名方式
            'sign_type' => 'MD5',
            //字符编码格式 目前支持 gbk 或 utf-8
            'input_charset' => 'UTF-8',
            //ca证书路径地址，用于curl中ssl校验
            //请保证cacert.pem文件在当前文件夹目录中
//		'cacert'=>__DIR__.'/cacert.pem',
            //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            'transport' => 'https',
            // 支付类型 ，无需修改
            'payment_type' => '1',
            // 产品类型，无需修改
            'service' => 'create_direct_pay_by_user',
            //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

            //↓↓↓↓↓↓↓↓↓↓ 请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            // 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
            'anti_phishing_key' => '',
            // 客户端的IP地址 非局域网的外网IP地址，如：221.0.0.1
            'exter_invoke_ip' => ''
            //↑↑↑↑↑↑↑↑↑↑请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        );
        return array(
            "alipay_gateway_new" => $alipay_gateway_new,
            "https_verify_url" => $https_verify_url,
            "http_verify_url" => $http_verify_url,
            "config" => $config,
        );
    }


    public function build(array $params)
    {
        $aliconfig = $this->aliconfig();
        $config = $aliconfig['config'];
        $parameter = array(
            "service" => $config['service'],
            "partner" => $config['partner'],
            "seller_id" => $config['seller_id'],
            "payment_type" => $config['payment_type'],
            "notify_url" => $config['notify_url'],
            "return_url" => $config['return_url'],
            "anti_phishing_key" => $config['anti_phishing_key'],
            "exter_invoke_ip" => $config['exter_invoke_ip'],
            "out_trade_no" => $params['tid'],
            "subject" => $params['title'],
            "total_fee" => $params['price'],
            "body" => $params['body'],
            "it_b_pay" => '30m',
            "_input_charset" => $config['input_charset']
        );
        unset($params['tid'], $params['title'], $params['price'], $params['body']);
        $parameter = array_merge($parameter, $params);
        $prepares = array();
        foreach ($parameter as $key => $value) {
            if ($key == "sign" || $key == "sign_type" || $value == "") {
                continue;
            } else {
                $prepares[$key] = $parameter[$key];
            }
        }
        $prepares = $this->argSort($prepares);
        $prestr = $this->createLinkstring($prepares);
        $my_sign = $this->md5Sign($prestr, $config['key']);
        $prepares['sign'] = $my_sign;
        $prepares['sign_type'] = strtoupper(trim($config['sign_type']));
        return $aliconfig['alipay_gateway_new'] . http_build_query($prepares, '', '&');
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Sign($prestr, $key)
    {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    public function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    public function verifyNotify($post)
    {
        //$post = $_POST;
        if (empty($post)) {//判断POST来的数组是否为空
            return false;
        } else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($post, $post["sign"]);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'false';
            if (!empty($post["notify_id"])) {
                $responseTxt = $this->getResponse($post["notify_id"]);
            }
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i", $responseTxt) && $isSign) {

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    public function getSignVeryfy($para_temp, $sign)
    {
        $aliconfig = $this->aliconfig();
        $config = $aliconfig['config'];
        //除去待签名参数数组中的空值和签名参数
        $para_filter = array();
        foreach ($para_temp as $key => $value) {
            if ($key == "sign" || $key == "sign_type" || $value == "") {
                continue;
            } else {
                $para_filter[$key] = $para_temp[$key];
            }
        }
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $isSgin = false;
        $isSgin = $this->md5Verify($prestr, $sign, $config['key']);
        return $isSgin;
    }

    /**
     * 验证签名
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    public function getResponse($notify_id)
    {
        $aliconfig = $this->aliconfig();
        $config = $aliconfig['config'];
        $transport = strtolower(trim($config['transport']));
        $partner = trim($config['partner']);
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $aliconfig['https_verify_url'];
        } else {
            $veryfy_url = $aliconfig['https_verify_url'];
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $config['cacert']);
        return $responseTxt;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * return 远程输出的数据
     */
    public function getHttpResponseGET($url, $cacert_url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
//        curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    public function verifyReturn($get)
    {
        //$get = $_GET;
        if (empty($get)) {//判断GET来的数组是否为空
            return false;
        } else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($get, $get["sign"]);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'false';
            if (!empty($get["notify_id"])) {
                $responseTxt = $this->getResponse($get["notify_id"]);
            }
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i", $responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }
}