<?php
// TODO
// это полностью работающий прототип, но если планируется полноценное коммерческое внедрение,
// то рекомендуется реализовать на базе какого-либо фреймворка (например, на Laravel)

require_once 'components/Config.php';

if (Config::$sCurrentEnvironment == Config::ENVIRONMENT_PRODUCTION) {
	@$aConfig = require_once dirname(__FILE__) . '/config/production/config.php';
} else {
	@$aConfig = require_once dirname(__FILE__) . '/config/local/config.php';
}
Config::init()->setCurrentConfig($aConfig);

if (Config::init()->get('service_off') !== false) {
	header('HTTP/1.1 503 Service Unavailable');
	echo '<h1>503 Service Unavailable</h1>';
	exit();
}

if (!isset($_POST) && !isset($_GET)) {
	exit();
}

$aData = array_merge($_POST, $_GET);
$aData['ip_address'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

require_once 'components/JsBridge.php';
require_once 'components/Logger.php';

Logger::init()->info([], '=== BEGIN ===');
Logger::init()->info($_SERVER, '$_SERVER');
Logger::init()->info($_POST, '$_POST');
Logger::init()->info($_GET, '$_GET');

if (isset($_POST['action'])) {
	$sAction = $_POST['action'];
} elseif (isset($_GET['action'])) {
	$sAction = $_GET['action'];
} else {
	exit();
}

try {
	$oJsBridge = new JsBridge();
	$oJsBridge->setRequest($sAction, $aData);
	echo $oJsBridge->getResponse();
	Logger::init()->info([], '=== END ===');
} catch (Exception $oException) {
	Logger::init()->error($oException);
	exit();
}