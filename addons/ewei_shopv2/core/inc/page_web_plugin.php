<?php
/*珍贵资源 请勿转卖*/
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class PluginWebPage extends WebPage
{

    public $pluginname;
    /**
     * @var $model AppModel
     */
    public $model;
    public $plugintitle;
    public $set;

    public $beforeRequest = array();

    public $afterRequest;

    public function __construct($_init = true)
    {

        parent::__construct($_init);
        global $_W,$_GPC;




        if (com('perm') && !com('perm')->check_plugin($_W['plugin'])) {
            $this->message("你没有相应的权限查看");
        }
        $this->pluginname = $_W['plugin'];
        $this->modulename = 'ewei_shopv2';
        $this->plugintitle = m('plugin')->getName($this->pluginname);
        //判断是否是第三方的应用
        if(strpos($this->pluginname,'open_messikefu')!==false){
            $redis = redis();
            if(!function_exists('redis') || is_error($redis)){
                $this->message('请联系管理员开启 redis 支持，才能使用第三方插件','','error');
                exit;
            }
            $key = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_open_plugin')." WHERE plugin = :plugin", array(':plugin' => $this->pluginname));

//            如果key为空或者没有设置的有问题的时候
            if(empty($key['key']) || $key['status'] ==2){
                $this->message('key未填写或key验证失败！', webUrl('util.open', array('plugin' => $this->pluginname,'title'=>$this->plugintitle)), 'error');
            }
            $redis_key = $this->pluginname;

            if($key['expirtime'] <=time() || is_null($redis->get($redis_key))){
            $info =  $this->checkOpen($key['key'],$key['plugin'],$key['domain']);
                //过期时间一个月
                if($info && $info['errno']==-1){
                    $this->message($info['errmsg'], webUrl('util.open', array('plugin' => $this->pluginname,'title'=>$this->plugintitle)),'error');
                }
               if (!is_error($redis)) {
                   if ($redis->setnx($redis_key, time())) {
                       $redis->expireAt($redis_key, time() + 172800);
                   }
               }
                pdo_update('ewei_shop_open_plugin',array('expirtime'=>time()+ 172800),array('id'=>$key['id']));
            }
        }

        // PC插件必须开启全网通和用户手机号绑定
        if ($this->pluginname == 'pc') {
            $wapSetting = m('common')->getSysset('wap');
            if (!$wapSetting['open']) {
                $this->message('使用PC必须开启全网通WAP访问');
            }
            if (!$wapSetting['mustbind']) {
                $this->message('使用PC必须开启强制绑定手机号');
            }
        }


        $this->model = m('plugin')->loadModel($this->pluginname);
        $this->set = $this->model->getSet();
        if ($_W['ispost']) {
            rc($this->pluginname);
        }
    }

    public function getSet()
    {
        return $this->set;
    }

    public function updateSet($data = array())
    {
        $this->model->updateSet($data);
    }

    private  function checkOpen($key='',$plugin='',$domain=''){
        global $_W;
        $auth = get_auth();
        $ip = $_SERVER['HTTP_ALI_CDN_REAL_IP'];
        if(!$ip){
            $ip = gethostbyname($domain);
        }
        $data = array(
            'ip' =>$ip,
            'site_id' => $auth['id'],
            'auth_key' => $auth['code'],
            'domain' => $domain,
            'plugins'=>$plugin,
            'app_key'=>$key
        );
        $resp = ihttp_post(EWEI_SHOPV2_AUTH_WXAPP . "/grant", $data);

        if (empty($resp['content']))
        {
            return array('errno'=>-1,'errmsg'=>'访问失败');

        }
        $result = json_decode($resp['content'], true);
        return $result;
    }

}
