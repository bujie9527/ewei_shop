<?php


use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class PluginMobilePage extends MobilePage
{

    /**
     * @var Environment
     */
    protected $twig;
    /**
     * 默认模板后缀
     * @var string
     */
    const DEFAULT_TEMPLATE_SUFFIX = '.twig';

    public $model;
    public $set;

    public function __construct()
    {

        parent::__construct();
        $this->model = m('plugin')->loadModel($GLOBALS["_W"]['plugin']);
        $this->set = $this->model->getSet();
    }

    public function getSet()
    {
        return $this->set;
    }

    public function qr()
    {
        global $_W, $_GPC;
        $url = trim($_GPC['url']);
        require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
        QRcode::png($url, false, QR_ECLEVEL_L, 16, 1);
    }

    /**
     * 解析模板路径参数
     * @param $template
     * @return string
     * @author: Vencenty
     * @time: 2019/5/20 15:43
     */
    protected function resolveTemplatePath($template)
    {
        $template = trim($template);
        // 把 path/to/template | path.to.template 全部替换成 path/to/template
        $replaceTemplate = str_replace(array('.', '/'), '/', $template);
        // 拆解参数
        $params = explode('/', $replaceTemplate);
        // 获取最后一个参数
        $lastElement = array_pop($params);
        // 最后一个参数拼接上文件后缀
        $templateFile = $lastElement . static::DEFAULT_TEMPLATE_SUFFIX;
        // 然后在拼接到原先数组
        array_push($params, $templateFile);
        // 拼接地址  path/to/template.twig
        $relativePath = implode('/', $params);

        return $relativePath;
    }


    /**
     * 渲染模板
     * eg.
     * $this->view('index') 渲染当前插件下 template/mobile/default/index.twig模板
     * $this->view('goods.detail.index') | $this->view('goods/detail/index') 则是渲染当前插件下 template/mobile/default/goods/detail/index.twig 模板
     * @param $template
     * @param array $params
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @author: Vencenty
     * @time: 2019/5/24 14:31
     */
    protected function view($template, $params = array())
    {
        // 获取模板地址
        global $_GPC;

        // 获取模板相对文件路径
        $templateFilePath = $this->resolveTemplatePath($template);
        $routeParams = isset($_GPC['r']) ? $_GPC['r'] : null;
        $routeParams = explode('.', $routeParams);
        // 获取当前插件名字
        $plugin = current($routeParams);
        $pluginTemplatePath = EWEI_SHOPV2_PLUGIN . "{$plugin}" . "/template/mobile/default/";


        // 模板需要的全局变量
        if ($plugin == 'pc') {
            $loader = new FilesystemLoader($pluginTemplatePath);
            $this->twig = new Environment($loader, array(
                'debug' => true
            ));

            // 注册全局函数
            $this->addFunction();
            // 注册全局参数
            $this->addGlobal();
            // 注册全局过滤器
            $this->addFilter();
        }
        // 模板需要的默认参数
        $defaultParams = array(
            'basePath' => EWEI_SHOPV2_LOCAL . "plugin/{$plugin}/static",
            'staticPath' => EWEI_SHOPV2_LOCAL . "static/",
            'appJsPath' => EWEI_SHOPV2_LOCAL . "static/js/app",
            'title' => '人人商城',
        );

        if (empty($params)) {
            $params = array();
        }

        // 合并默认参数
        $params = array_merge($defaultParams, $params);

        // 模板文件绝对路径
        $templateFileRealPath = $pluginTemplatePath . $templateFilePath;
        if (!file_exists($templateFileRealPath)) {
            die("模板文件 {$templateFileRealPath} 不存在");
        }

        return $this->twig->render($templateFilePath, $params);
    }

    /**
     * 添加全局函数
     * @author: Vencenty
     * @time: 2019/5/27 20:45
     */
    private function addFunction()
    {
        // 需要扩展的函数
        $extendFunctions = array(
            'tomedia' => function ($src) {
                return tomedia($src);
            },

            'html2html' => function ($data) {
                return trim(htmlspecialchars_decode($data));
            },
            // mobileUrl 别名
            'pcUrl' => function ($do = '', $query = [], $full = false) {
                global $_W, $_GPC;
                $result = m('common')->getPluginSet('pc');
                if (strpos($do, 'pc') === false) {
                    $do = 'pc.' . $do;
                }
                if (isset($result['domain']) && mb_strlen($result['domain'])) {
                    return ($full === true ? $_W['siteroot'] : './') . (empty($do) ? '' : ('?r=' . $do . '&')) . http_build_query($query);
                } else {
                    return mobileUrl($do, $query, $full);
                }
            },
            // 获取时间戳
            'time' => function ($format = null) {
                // 如果传入了格式化运算符,那么按照传入的格式进行格式化
                if (!empty($format)) {
                    return date($format, time());
                }
                return time();
            },
            'ispc' => function () {
                // 如果传入了格式化运算符,那么按照传入的格式进行格式化
                $result = m('common')->getPluginSet('pc');
                if (mb_strlen($result['domain']) > 0) {
                    return true;
                }
                return false;
            },
            // 获取数组长度
            'count' => function ($array = array(), $model = COUNT_NORMAL) {
                return count($array, $model);
            },
            // 打印变量
            'dump' => function ($params) {
                return print_r($params);
            },
            // 检查登录状态
            'checkLogin' => function () {
                return $this->model->checkLogin();
            }
        );


        foreach ($extendFunctions as $functionName => $callback) {
            $function = new Twig_SimpleFunction($functionName, $callback);
            $this->twig->addFunction($function);
        }
    }

    /**
     * 增加全局变量
     * @author: Vencenty
     * @time: 2019/5/27 20:37
     */
    protected function addGlobal()
    {
        global $_W, $_GPC;

        $params = array(
            // 从model里面获取所有的模板全局变量
            'global' => p('pc')->getTemplateGlobalVariables(),
            // 版本,目前先挂上时间戳,不然每次更新
            'v' => str_replace('.', '', microtime(true)),
            // 挂载到window全局对象下的参数,一般用来书写全局变量
            'params' => json_encode($_GPC),
            // 挂载到window全局对象下的属性,一般书写需要的路由
            'api' => json_encode(array(
                // 加入购物车
                'addShopCart' => pcUrl('goods.addShopCart', array(), true),
                // 评论列表
                'commentList' => pcUrl('goods.comment_list', array(), true),
                // 具体的评论
                'comments' => pcUrl('goods.comments', array(), true),
                // 计算多规格商品价格 目前该接口废弃,直接传给前端所有规格数据来进行计算
                'calcSpecGoodsPrice' => pcUrl('goods.calcSpecGoodsPrice', array(), true),
                // 图片上传
                'imageUpload' => pcUrl('foundation.imageUpload', array(), true)

            ), JSON_UNESCAPED_UNICODE),
        );

        foreach ($params as $key => $value) {
            $this->twig->addGlobal($key, $value);
        }
    }

    /**
     * 添加全局过滤器
     * @author: Vencenty
     * @time: 2019/5/27 20:45
     */
    protected function addFilter()
    {
        // 扩展过滤器
        $extendFilters = array(
            // 强转float
            'float' => function ($number) {
                return (float)$number;
            },
            // 强转布尔
            'bool' => function ($params) {
                return (bool)$params;
            },
            // 字符串超出部分...显示
            'format' => function ($string) {
                $output = $string;
                if (mb_strlen($output) > 8) {
                    $output = mb_substr($output, 0, 8, 'utf-8');
                }

                return $output;
            }
        );

        foreach ($extendFilters as $filterName => $extendFilter) {
            $filter = new Twig_SimpleFilter($filterName, $extendFilter);
            $this->twig->addFilter($filter);
        }
    }


}
