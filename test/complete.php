<?php
require_once '../components/Config.php';

if (Config::$sCurrentEnvironment == Config::ENVIRONMENT_PRODUCTION) {
	$aConfig = require_once '../config/production/config.php';
} else {
	$aConfig = require_once '../config/local/config.php';
}
Config::init()->setCurrentConfig($aConfig);

if (!empty($_POST)) {
	header(
		sprintf(
			"location: %s/?action=authPaymentCompletion&token=%s&type=%s&timestamp=%s&sign=%s",
			Config::init()->get('bridge_url'),
			$_POST['token'],
			$_POST['type'],
			$_POST['timestamp'],
			getSign($_POST['token'] . $_POST['type'] . $_POST['timestamp'])
		)
	);
}

function getSign($sMessage)
{
	$sPrivateKey = file_get_contents('private.pem');
	$rPrivateKey = openssl_pkey_get_private($sPrivateKey);
	openssl_sign($sMessage, $sSign, $rPrivateKey, OPENSSL_ALGO_SHA1);

	return bin2hex($sSign);
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-responsive.no-icons.min.css">
</head>
<body>
<div class="container span8">
	<!-- START: Payment form -->
	<div class="clearfix"></div>
	<div class="well span5">
		<form id="payment-complete-form" action="" method="POST">
			<div class="clearfix"></div>

			<input type="hidden" id="timestamp" name="timestamp" value="<?= time() ?>">

			<div class="span3"><label for="type">Payment completion type</label>
				<select id="type" name="type">
					<option value="completion">Completion</option>
					<option value="reversal">Reversal</option>
				</select>
			</div>

			<div class="span3"><label for="token">Token</label>
				<span id="token_container">
					<input id="token" name="token" class="token span4" type="text" size="20" value=""/>
				</span>
			</div>

			<div class="controls">
				<div class="span3">
					<button class="payment-submit-button btn btn-primary" type="submit">Complete payment</button>
				</div>
			</div>
		</form>
	</div>

	<div class="controls">
		<div class="well span5 payment-errors-container" style="background-color: #ffcaba; display: none;">
			<div class="payment-errors text-error"></div>
		</div>
	</div>
	<!-- END: Payment form -->
</div>
</body>
</html>