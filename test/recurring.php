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
			"location: %s/?action=recurringPaymentSecondary&token=%s&biller_client_id=%s&amount=%s&currency=%s&timestamp=%s&sign=%s",
			Config::init()->get('bridge_url'),
			$_POST['token'],
			$_POST['biller_client_id'],
			$_POST['amount'],
			$_POST['currency'],
			$_POST['timestamp'],
			getSign($_POST['token'] . $_POST['biller_client_id'] . $_POST['amount'] . $_POST['currency'] . $_POST['timestamp'])
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
	<!-- START: Recurring form -->
	<div class="clearfix"></div>
	<div class="well span5">
		<form id="secondary-recurring-form" action="" method="POST">
			<div class="clearfix"></div>

			<input type="hidden" id="timestamp" name="timestamp" value="<?= time() ?>">
			<input type="hidden" id="currency_h" name="currency" value="RUB">

			<div class="span3"><label for="biller_client_id">Biller client ID</label>
				<input id="biller_client_id" name="biller_client_id" class="biller_client_id span4" type="text" size="20" value=""/>
			</div>

			<div class="span3"><label for="token">Token</label>
				<input id="token" name="token" class="token span4" type="text" size="20" value=""/>
			</div>

			<div class="span3"><label for="amount">Amount</label>
				<input id="amount" name="amount" class="amount span3" type="text" size="20" value="<?= sprintf('%s.%s', mt_rand(1, 100), mt_rand(1, 99)) ?>" />
			</div>

			<div class="span1"><label for="currency">Currency</label>
				<input id="currency" class="currency span1" type="text" size="4" value="RUB" disabled />
			</div>

			<div class="controls">
				<div class="span3">
					<button class="payment-submit-button btn btn-primary" type="submit">Recurring payment</button>
				</div>
			</div>
		</form>
	</div>

	<div class="controls">
		<div class="well span5 payment-errors-container" style="background-color: #ffcaba; display: none;">
			<div class="payment-errors text-error"></div>
		</div>
	</div>
	<!-- END: Recurring form -->
</div>
</body>
</html>