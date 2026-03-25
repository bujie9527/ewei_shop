<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

/**
 * Class Touch_loopModel
 */
class Touch_loopModel extends PluginModel
{
    const LISTTABLENAME = 'ewei_shop_touch_loop_list';
    const ORDERBY = 't.sort desc,t.id desc';
    const PAGENUM = 20;
    public $image;
    public $goods;
    public $member;
    const FONT_SIZE = '14px';

    /**
     * 获取商品一键发圈的列表配置
     * @param int $uniacid
     * @param array $params
     * @param int $page
     * @return array
     */
    public function getList($uniacid = 0, $params = array(), $page = 0)
    {
        if (empty($uniacid)) {
            return array();
        }
        $list = pdo_fetchall('select t.id,t.status,t.isrecommand, g.title,g.thumb,g.id as gid,t.sort,t.content from ' . tablename(self::LISTTABLENAME) . ' AS t LEFT JOIN ' . tablename('ewei_shop_goods') . ' AS g on g.id = t.goods_id and g.uniacid = t.uniacid where t.uniacid = :uniacid  ' . $params['andWhere'] . ' order by ' . self::ORDERBY . ' limit ' . ($page - 1) * self::PAGENUM . ',' . self::PAGENUM, array(':uniacid' => $uniacid));
        $total = pdo_fetchcolumn('select count(t.goods_id) from ' . tablename(self::LISTTABLENAME) . ' AS t LEFT JOIN ' . tablename('ewei_shop_goods') . ' AS g on g.id = t.goods_id and g.uniacid = t.uniacid where t.uniacid = :uniacid ' . $params['andWhere'] . ' order by ' . self::ORDERBY, array(':uniacid' => $uniacid));
        $pager = pagination2($total, $page, self::PAGENUM);

        return (compact('list', 'total', 'pager'));
    }

    /**
     * 获取传入的id
     * @param $request
     * @return false|string[]
     */
    public function getId($request)
    {
        $id = $request['ids'];
        if (empty($id)) {
            $id = $request['id'];
            $id = array_filter(explode(',', $id));
        }
        if (empty($id)) {
            show_json(0, '请选择需要操作的记录');
        }
        return $id;
    }

    public function create($data)
    {
        if (empty($data['goods_id'])) {
            show_json(0, '商品必选');
        }
        if (empty($data['content']))
        {
            show_json(0,'分享内容为必填');
        }
        $data['credted_at'] = time();
        pdo_begin();
        pdo_insert(self::LISTTABLENAME, $data);
        $id = pdo_insertid();
        if ($id) {
            pdo_commit();
            return true;
        }
        pdo_rollback();
        return false;
    }

    public function updateRecord($primaryKeyData, $data)
    {
        pdo_begin();
        $ret = pdo_update(self::LISTTABLENAME, $data, $primaryKeyData);
        if ($ret != true) {
            pdo_rollback();
            return false;
        }
        pdo_commit();
        return true;
    }

    /**
     * 获取商品详情
     * @param $uniacid
     * @param $id
     * @return array
     */
    public function getInfo($uniacid, $id)
    {
        $item = (pdo_get('ewei_shop_touch_loop_list', compact('uniacid', 'id')));
        if (empty($item)) {
            return false;
        }
        $item['thumbs'] = unserialize($item['thumbs']);
        $goods = pdo_get('ewei_shop_goods', array('id' => $item['goods_id'], 'uniacid' => $uniacid));
        return array($item, $goods);
    }

    public function createGoodsPoster()
    {
        $set = $this->getSet();
        if (empty($set['post_id'])) {
            //走默认的商品二维码类型
        }
    }

    /**
     * 构建二维码
     * @param $params
     */
    public function buildQr($params)
    {
        $qrcode = p('app')->getCodeUnlimit(array(
            'scene' => 'id=' . $this->goods['id'] . '&mid=' . $this->member['id'],
            'page' => 'pages/goods/detail/index'
        ));
        //创建一个真彩画布 不然就是黑白照片
        $bg = imagecreatetruecolor($params['width'], $params['height']);
        //从图片加载资源进来
        $ret = imagecreatefromstring($qrcode);
        return $this->buildImage($params, $ret);

    }

    public function buildHead($params)
    {
        //从图片加载资源进来
        $ret = imagecreatefromstring(file_get_contents($this->member['avatar']));
        return $this->buildImage($params, $ret);

    }

    public function buildNickname($params)
    {
        return $this->buildText($params, $this->member['nickname']);
    }

    public function buildTitle($params)
    {
        $goods = $this->goods;

        return $this->buildText($params, $this->goods['title']);
    }

    public function buildMarketprice($params)
    {
        return $this->buildText($params, '¥' . $this->goods['marketprice']);
    }

    public function buildProductprice($params)
    {
        return $this->buildText($params, '¥' . $this->goods['productprice']);

    }

    public function buildImg($params)
    {
        //创建一个真彩画布 不然就是黑白照片
        //从图片加载资源进来
        if (empty($params['src']))
        {
            return ;
        }
        $ret = imagecreatefromstring(file_get_contents(tomedia($params['src'])));
        return $this->buildImage($params, $ret);
    }

    public function buildImage($params, $ret)
    {
        $params = $this->makeData($params);
        $w = imagesx($ret);
        $h = imagesy($ret);
        imagecopyresized($this->image, $ret, $params['left'], $params['top'], 0, 0, $params['width'], $params['height'], $w, $h);
        imagedestroy($ret);
    }


    public function buildText($params, $text)
    {
        $params = $this->makeData($params);

        $text = mb_substr($text, 0, 5);
        $font = EWEI_SHOPV2_LOCAL . 'static/fonts/msyh.ttf';
        $colors = $this->hex2rgb($params['color']);
        if ($params['type'] == 'marketprice') {
            $params['top'] = (int)($params['top']) -50;
        }
        if ($params['type'] == 'productprice') {
            $params['top'] = (int)($params['top']) -50;
        }
//        if ($params['type'] == 'title') {
//            $title_width = (int)(($params['width']) / $params['size'] / 1.2);
//            $width_left = 0;
//            while ($width_left < strlen($this->goods['title'])) {
//                //mb_substr 防止截取字符串乱码
//                $title = mb_substr($this->goods['title'], $width_left, $title_width, 'utf-8');
//                $width_left += $title_width;
//                $params['top'] += $params['size'] * 2.4;
//            }
//        }
        $color = imagecolorallocate($this->image, $colors['red'], $colors['green'], $colors['blue']);
        imagettftext($this->image, $params['size'], 0, $params['left'], $params['top'] - $params['size'], $color, $font, $text);

    }

    public function makeData($data)
    {
        $data['left'] = intval(str_replace('px', '', $data['left'])) * 2;
        $data['top'] = (intval(str_replace('px', '', $data['top'])) * 2);
        $data['width'] = intval(str_replace('px', '', $data['width'])) * 2;
        $data['height'] = intval(str_replace('px', '', $data['height'])) * 2;
        $data['size'] = intval(str_replace('px', '', $data['size'])) * 2;
        $data['src'] = tomedia($data['src']);
        return $data;
    }

    public function hex2rgb($colour)
    {
        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }
        if (strlen($colour) == 6) {
            list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
        } elseif (strlen($colour) == 3) {
            list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
        } else {
            return false;
        }
        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return array('red' => $r, 'green' => $g, 'blue' => $b);
    }


    public function deleteImage($params)
    {
        global $_W;
        $version = $params['version'];
        for ($i = 1; $i < 10; $i++) {
            if ($i == $version) {
                continue;
            }
            $params['version'] = $i;
            $filename = md5(json_encode($params)) . '.png';
            $path = IA_ROOT . "/addons/ewei_shopv2/data/toouch_loop/code/" . $_W['uniacid'] . '/';
            $target = $path . $filename;
            if (is_file($target)) {
                @unlink($target);
            }
        }
    }

}