<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}
define('HTTP_X_FOR', (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://');
define('EWEI_SHOPV2_NEW_AUTH_URL' , base64_decode('bWUuc3kxMzguY24vYXBwL2V3ZWkucGhwP2E9Y2hlY2s='));

if (!defined('PHP_VERSION_ID')) {
	$version = explode('.', PHP_VERSION);
	define('PHP_VERSION_ID', $version[0] * 10000 + $version[1] * 100 + $version[2]);
}

class Upgrade_new_EweiShopV2Page extends SystemPage
{

	public function check()
	{
		global $_W;
		global $_GPC;
		$plugins = pdo_fetchall('select `identity` from ' . tablename('ewei_shop_plugin'), array(), 'identity');
		load()->func('db');
		load()->func('communication');
		$auth = get_auth();

		set_time_limit(0);
		$version = defined('EWEI_SHOPV2_VERSION') ? EWEI_SHOPV2_VERSION : '2.0.0';
		$release = defined('EWEI_SHOPV2_RELEASE') ? EWEI_SHOPV2_RELEASE : '201605010000';
		$file_md5 = $this->fileGlob(EWEI_SHOPV2_PATH, true);
		$start = microtime(true);
		$resp = ihttp_post(HTTP_X_FOR.EWEI_SHOPV2_NEW_AUTH_URL , array('ip' => $auth['ip'], 'id' => $auth['id'], 'code' => $auth['code'], 'domain' => trim(preg_replace('/http(s)?:\\/\\//', '', trim($_W['siteroot'], '/'))), 'version' => $version, 'release' => $release, 'manual' => $check, 'file_md5' => json_encode($file_md5), 'plugins' => array_keys($plugins), 'phpversion' => PHP_VERSION_ID));
		$end = microtime(true);

		if (empty($resp['content'])) {
			show_json(0);
		}

		$result = json_decode($resp['content'], true);

		if (is_array($result)) {
			if ($result['status'] == 1) {
				show_json(1, array('result' => 1, 'version' => $upgrade['version'], 'release' => $upgrade['release'], 'files' => $upgrade['files'], 'database' => $database, 'upgrades' => $upgrade['upgrades'], 'log' => nl2br($log), 'new_log' => $upgrade['new_log'], 'templatefiles' => $templatefiles));
			}
			show_json(0, $upgrade['message']);
		}

		if (is_file(EWEI_SHOPV2_PATH . 'tmp')) {
			@unlink(EWEI_SHOPV2_PATH . 'tmp');
		}

		show_json(0, $resp['content']);
	}
}

?>
