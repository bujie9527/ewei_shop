<?php

class AdvController extends PluginWebPage
{
    /**
     * 推荐广告
     */
    const ADV_RECOMMEND = 'recommend';
    /**
     * 首页轮播
     */
    const ADV_BANNER = 'banner';
    /**
     * 搜索广告
     */
    const ADV_SEARCH = 'search';

    /**
     * 广告首页
     * @author: Vencenty
     * @time: 2019/5/23 17:53
     */
    public function main()
    {

    }



    public function search()
    {
        global $_W, $_GPC;

        // 如果是post请求
        if ($_W['ispost']) {

        }

        include $this->template();
    }

    /**
     * 推荐广告
     * @author: Vencenty
     * @time: 2019/5/24 9:30
     */
    public function recommend()
    {
        global $_W, $_GPC;


        $cubes = $this->getAdvSettings(static::ADV_RECOMMEND);
        if ($_W['ispost']) {

            $cubeUrls = $_GPC['cube_url'];
            $cubeImages = $_GPC['cube_img'];

            $cubes = array();
            if (is_array($cubeImages)) {
                foreach ($cubeImages as $key => $img) {
                    $cubes[] = array(
                        'img' => save_media($img),
                        'url' => trim($cubeUrls[$key])
                    );
                }
            }

            $this->updateAdvSettings(static::ADV_RECOMMEND, $cubes)
                ? show_json(1)
                : show_json(0);

        }

        include $this->template();
    }

    public function banner()
    {
        global $_W, $_GPC;

        if ($_W['ispost']) {
            $bannerUrls = $_GPC['banner_url'];
            $bannerImages = $_GPC['banner_img'];
            
            $adv = array(
                'bottom_adv_image' => $_GPC['bottom_adv_image'],
                'bottom_adv_image_link' => $_GPC['bottom_adv_image_link'],
            );


            $banners = array();
            if (is_array($bannerImages)) {
                foreach ($bannerImages as $key => $img) {
                    $banners[] = array(
                        'img' => save_media($img),
                        'url' => trim($bannerUrls[$key])
                    );
                }
            }

            $data = array(
                'banners' => $banners,
                'adv' => $adv
            );

            $this->updateAdvSettings(static::ADV_BANNER, $data)
                ? show_json(1)
                : show_json(0,'操作失败');
        }

        $settings = $this->getAdvSettings(static::ADV_BANNER);
        $banners = $settings['banners'];
        $adv = $settings['adv'];
        include $this->template();
    }

    /**
     * 获取或设置广告设置
     * 如果get拿不到key的内容, 会执行callback
     * @param $key
     * @return array|null
     * @author: Vencenty
     * @time: 2019/5/23 19:45
     */
    public function getAdvSettings($key)
    {
        global $_W;
        $settings = pdo_get('ewei_shop_pc_adv', array('uniacid' => $_W['uniacid']));
        $settings = json_decode($settings['settings'], true);

        return isset($settings[$key]) ? $settings[$key] : null;
    }

    /**
     * 更新广告设置
     * @param $key
     * @param $value
     * @return bool
     * @author: Vencenty
     * @time: 2019/5/23 20:03
     */
    public function updateAdvSettings($key, $value)
    {
        global $_W;

        // 不是预设的缓存键不能随便设置,防止出现乱七八糟的情况
        $preSetKey = array(static::ADV_BANNER, static::ADV_RECOMMEND, static::ADV_SEARCH);
        if (!in_array($key, $preSetKey)) {
            return null;
        }

        $settings = pdo_get('ewei_shop_pc_adv', array('uniacid' => $_W['uniacid']));

        if (empty($settings)) {
            // 没有记录先写一条,然后给setting一个默认值
            $settings = array();
            $row = array(
                'uniacid' => $_W['uniacid'],
                'settings' => ''
            );
            pdo_insert('ewei_shop_pc_adv', $row);
            // 获得id
            $id = pdo_insertid();
        }
        // 解压其中内容进行更新
        $id = $settings['id'];
        $settings = json_decode($settings['settings'], true);
        $settings[$key] = $value;

        return (bool)pdo_update(
            'ewei_shop_pc_adv',
            array('settings' => json_encode($settings)),
            array('id' =>$id)
        );

    }
}