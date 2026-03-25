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

class IndexController extends PluginMobilePage
{
    /**
     * @var PcModel
     */
    public $model;

    /**
     * 渲染模板界面
     * @return string|void
     * @throws \Twig\Error\SyntaxError
     * @author: Vencenty
     * @time: 2019/5/27 21:02
     */
    public function main()
    {
        global $_W, $_GPC;

        $data = $this->model->getData('home');
        $textData = m('common')->getPluginset('pc');
        // 获取布局
        $data['layout'] = $this->model->getTemplateSetting();
        // 当前活动标题
        $info = m('common')->getSysset('shop');

        $data['title'] = empty($info['name']) ? '人人商城' : $info['name'];
        // 当前秒杀相关信息
        $data['seckill'] = plugin_run('seckill::getTaskSeckillInfo');


        if (isset($_GET['debug'])) {
            print_r($this->model->getTemplateGlobalVariables());
            exit;
            
        }
        try {
            return $this->view('index', $data);

        }catch(Throwable $exception)
        {
            dd($exception);
        }
    }

    public function debug()
    {
        $r = $this->model->getData('home');

    }

    public function seckill()
    {
        $seckill_list = $this->model->invoke('seckill.index::get_list', false);
        $currentSecKillActivity = $seckill_list['times'][$seckill_list['timeindex']];

    }

    /**
     * 全局变量
     * @author: Vencenty
     * @time: 2019/5/27 19:09
     */
    public function globalVariables()
    {
        $r = $this->model->getTemplateGlobalVariables();
        print_r($r);
        exit;
    }

    /**
     * 获取二维码
     */
    public function getCode()
    {
        global $_W, $_GPC;
        $id = $_GPC['id'];


        $site = $_W['siteroot'];
        $_W['siteroot'] = $this->model->set['mobile_domain'] ? $this->model->set['mobile_domain'] : $_W['siteroot'];

        $url = mobileUrl($_GPC['url'], array('id' => $id), true);
        $_W['siteroot'] = $site;
        $qrcode = m('qrcode')->createQrcode($url);
        return json_encode(array('status' => 1, 'img' => $qrcode));
    }


}
