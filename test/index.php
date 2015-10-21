<?php
require_once '../components/Config.php';

if (Config::$sCurrentEnvironment == Config::ENVIRONMENT_PRODUCTION) {
	$aConfig = require_once '../config/production/config.php';
} else {
	$aConfig = require_once '../config/local/config.php';
}
Config::init()->setCurrentConfig($aConfig);

$sIpspJavaScript = require_once 'ipspjs.js.template.php';
$sIpspJavaScript = str_replace('%IPSPJS_HOST_NAME%', Config::init()->get('bridge_url'), $sIpspJavaScript);
file_put_contents('ipspjs.js', $sIpspJavaScript);

function getIpspjsPublicKey($sKeyId)
{
	$sTimestamp = time();
	$sToken = md5(date('Y-m-d H:i:s', $sTimestamp . mt_rand(0, 10000)));

	$sData = str_pad($sKeyId, 6, '0', STR_PAD_LEFT) . $sToken . $sTimestamp;

	$sPrivateKey = file_get_contents('private.pem');
	$rPrivateKey = openssl_pkey_get_private($sPrivateKey);
	openssl_sign($sData, $sSign, $rPrivateKey, OPENSSL_ALGO_SHA1);

	// дополнить токен идентификатором ключа расшифровки
	return $sData . bin2hex($sSign);
}

$aCardData = [
	'exp_month'  => '01',
	'exp_year'   => '2017',
	'cardholder' => 'TEST CARDHOLDER',
	'amount'     => sprintf('%s.%s', mt_rand(1, 100), mt_rand(1, 99)),
	'currency'   => 'RUB',
];
// mastercard
$aCardData['number'] = '5417150396276825';
$aCardData['cvv'] = '789';
// visa (3ds)
$aCardData3Ds['number'] = '4652060573334999';
$aCardData3Ds['cvv'] = '067';

// ID ключа для расшифровки
$sIpspjsKeyId = 1;
// зашифрованный токен
$sIpspjsPublicKey = getIpspjsPublicKey($sIpspjsKeyId);
// id покупателя, назначенный ТСП (только для периодических платежей)
$sBillerClientId = md5(time() + rand(1, 10000));
// Месяц окончания периодического платежа
$sPerspayeeExpiry[] = '01';
// Год окончания периодического платежа
$sPerspayeeExpiry[] = '2016';
// Периодичность
$sRecurFreq = '7';
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-responsive.no-icons.min.css">
	<script type="text/javascript">
		var IPSPJS_PUBLIC_KEY = '<?= $sIpspjsPublicKey ?>';
	</script>
</head>
<body>
<div class="container span12">
	<!-- START: Payment form -->
	<div class="clearfix"></div>
	<div class="well span8">

		<div class="controls controls-row">
			<div class="btn-group span8">
				<button id="btn-paymenttype-cc-normal"  class="paymenttype btn btn-primary" value="cc" translate="iframe">Mastercard (Non 3DS)</button>
				<button id="btn-paymenttype-cc-3ds" class="paymenttype btn" value="cc-3ds" translate="iframe">Visa (3DS)</button>
				<button id="btn-paymenttype-rcp" class="paymenttype btn" value="rcp" translate="iframe">Recurring (Non 3DS)</button>
				<button id="btn-paymenttype-rcp-3ds" class="paymenttype btn" value="rcp-3ds" translate="iframe">Recurring (3DS)</button>
			</div><br /><br />
		</div>

		<form id="payment-form" action="" method="POST">
			<div class="clearfix"></div>

			<div id="payment-form-cc" class="payment-input">
				<div class="controls controls-row">
					<div class="span3"><label for="card-number">Card number</label>
						<input id="card-number" class="card-number span3" type="text" size="20" value="<?= $aCardData['number'] ?>"/>
					</div>
					<div class="span1"><label for="card-cvc">CVC</label>
						<input id="card-cvc" class="card-cvc span1" type="text" size="4" value="<?= $aCardData['cvv'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span4">
						<label for="card-holdername">Card holder</label>
						<input id="card-holdername" class="card-holdername span4" type="text" size="20" value="<?= $aCardData['cardholder'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span3">
						<label for="card-expiry-month">Valid until (MM/YYYY)</label>
						<input id="card-expiry-month" class="card-expiry-month span1" type="text" size="2" value="<?= $aCardData['exp_month'] ?>"/>
						<span> / </span>
						<input id="card-expiry-year" class="card-expiry-year span1" type="text" size="4" value="<?= $aCardData['exp_year'] ?>"/>
						<label for="card-expiry-year" hidden="hidden"></label>
					</div>
				</div>
				<div class="controls">
					<div class="span3"><label for="card-amount-int">Amount</label>
						<input id="card-amount-int" class="card-amount-int span3" type="text" size="20" value="<?= $aCardData['amount'] ?>" />
					</div>
				</div>
				<div class="controls">
					<div class="span1"><label for="card-currency">Currency</label>
						<input id="card-currency" class="card-currency span1" type="text" size="4" value="<?= $aCardData['currency'] ?>" disabled />
					</div>
				</div>
			</div>

			<div id="payment-form-cc-3ds" class="payment-input" style="display: none;">
				<div class="controls controls-row">
					<div class="span3"><label for="card-number-3ds">Card number</label>
						<input id="card-number-3ds" class="card-number-3ds span3" type="text" size="20" value="<?= $aCardData3Ds['number'] ?>"/>
					</div>
					<div class="span1"><label for="card-cvc-3ds">CVC</label>
						<input id="card-cvc-3ds" class="card-cvc-3ds span1" type="text" size="4" value="<?= $aCardData3Ds['cvv'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span4">
						<label for="card-holdername-3ds">Card holder</label>
						<input id="card-holdername-3ds" class="card-holdername-3ds span4" type="text" size="20" value="<?= $aCardData['cardholder'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span3">
						<label for="card-expiry-month-3ds">Valid until (MM/YYYY)</label>
						<input id="card-expiry-month-3ds" class="card-expiry-month-3ds span1" type="text" size="2" value="<?= $aCardData['exp_month'] ?>"/>
						<span> / </span>
						<input id="card-expiry-year-3ds" class="card-expiry-year-3ds span1" type="text" size="4" value="<?= $aCardData['exp_year'] ?>"/>
						<label for="card-expiry-year-3ds" hidden="hidden"></label>
					</div>
				</div>
				<div class="controls">
					<div class="span3"><label for="card-amount-int-3ds">Amount</label>
						<input id="card-amount-int-3ds" class="card-amount-int-3ds span3" type="text" size="20" value="<?= $aCardData['amount'] ?>" />
					</div>
				</div>
				<div class="controls">
					<div class="span1"><label for="card-currency-3ds">Currency</label>
						<input id="card-currency-3ds" class="card-currency-3ds span1" type="text" size="4" value="<?= $aCardData['currency'] ?>" disabled />
					</div>
				</div>
			</div>

			<div id="payment-form-rcp" class="payment-input" style="display: none;">
				<div class="controls controls-row">
					<div class="span3"><label for="card-number-rcp">Card number</label>
						<input id="card-number-rcp" class="card-number-rcp span3" type="text" size="20" value="<?= $aCardData['number'] ?>"/>
					</div>
					<div class="span1"><label for="card-cvc-rcp">CVC</label>
						<input id="card-cvc-rcp" class="card-cvc-rcp span1" type="text" size="4" value="<?= $aCardData['cvv'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span4">
						<label for="card-holdername-rcp">Card holder</label>
						<input id="card-holdername-rcp" class="card-holdername-rcp span4" type="text" size="20" value="<?= $aCardData['cardholder'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span3">
						<label for="card-expiry-month-rcp">Valid until (MM/YYYY)</label>
						<input id="card-expiry-month-rcp" class="card-expiry-month-rcp span1" type="text" size="2" value="<?= $aCardData['exp_month'] ?>"/>
						<span> / </span>
						<input id="card-expiry-year-rcp" class="card-expiry-year-rcp span1" type="text" size="4" value="<?= $aCardData['exp_year'] ?>"/>
						<label for="card-expiry-year-rcp" hidden="hidden"></label>
					</div>
				</div>
				<div class="span3"><label for="card-amount-int-rcp">Amount</label>
					<input id="card-amount-int-rcp" class="card-amount-int-rcp span3" type="text" size="20" value="<?= $aCardData['amount'] ?>" />
				</div>
				<div class="span1"><label for="card-currency-rcp">Currency</label>
					<input id="card-currency-rcp" class="card-currency-rcp span1" type="text" size="4" value="<?= $aCardData['currency'] ?>" disabled />
				</div>

				<div class="controls">
					<div class="span4">
						<label for="biller-client-id-rcp">Biller client ID</label>
						<input id="biller-client-id-rcp" class="biller-client-id-rcp span4" type="text" size="20" value="<?= $sBillerClientId ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span2">
						<label for="perspayee-expiry-month-rcp">Expiry (MM/YYYY)</label>
						<input id="perspayee-expiry-month-rcp" class="perspayee-expiry-month-rcp span1" type="text" size="2" value="<?= $sPerspayeeExpiry[0] ?>"/>
						<span> / </span>
						<input id="perspayee-expiry-year-rcp" class="perspayee-expiry-year-rcp span1" type="text" size="4" value="<?= $sPerspayeeExpiry[1] ?>"/>
						<label for="perspayee-expiry-year-rcp" hidden="hidden"></label>
					</div>
				</div>
				<div class="controls">
					<div class="span1">
						<label for="recur-freq-rcp">Freq</label>
						<input id="recur-freq-rcp" class="recur-freq-rcp span1" type="text" size="4" value="<?= $sRecurFreq ?>"/>
					</div>
				</div>
			</div>

			<div id="payment-form-rcp-3ds" class="payment-input" style="display: none;">
				<div class="controls controls-row">
					<div class="span3"><label for="card-number-rcp-3ds">Card number</label>
						<input id="card-number-rcp-3ds" class="card-number-rcp-3ds span3" type="text" size="20" value="<?= $aCardData3Ds['number'] ?>"/>
					</div>
					<div class="span1"><label for="card-cvc-rcp-3ds">CVC</label>
						<input id="card-cvc-rcp-3ds" class="card-cvc-rcp-3ds span1" type="text" size="4" value="<?= $aCardData3Ds['cvv'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span4">
						<label for="card-holdername-rcp-3ds">Card holder</label>
						<input id="card-holdername-rcp-3ds" class="card-holdername-rcp-3ds span4" type="text" size="20" value="<?= $aCardData['cardholder'] ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span3">
						<label for="card-expiry-month-rcp-3ds">Valid until (MM/YYYY)</label>
						<input id="card-expiry-month-rcp-3ds" class="card-expiry-month-rcp-3ds span1" type="text" size="2" value="<?= $aCardData['exp_month'] ?>"/>
						<span> / </span>
						<input id="card-expiry-year-rcp-3ds" class="card-expiry-year-rcp-3ds span1" type="text" size="4" value="<?= $aCardData['exp_year'] ?>"/>
						<label for="card-expiry-year-rcp-3ds" hidden="hidden"></label>
					</div>
				</div>
				<div class="span3"><label for="card-amount-int-rcp-3ds">Amount</label>
					<input id="card-amount-int-rcp-3ds" class="card-amount-int-rcp-3ds span3" type="text" size="20" value="<?= $aCardData['amount'] ?>" />
				</div>
				<div class="span1"><label for="card-currency-rcp-3ds">Currency</label>
					<input id="card-currency-rcp-3ds" class="card-currency-rcp-3ds span1" type="text" size="4" value="<?= $aCardData['currency'] ?>" disabled />
				</div>

				<div class="controls">
					<div class="span4">
						<label for="biller-client-id-rcp-3ds">Biller client ID</label>
						<input id="biller-client-id-rcp-3ds" class="biller-client-id-rcp-3ds span4" type="text" size="20" value="<?= $sBillerClientId ?>"/>
					</div>
				</div>
				<div class="controls">
					<div class="span2">
						<label for="perspayee-expiry-month-rcp-3ds">Expiry (MM/YYYY)</label>
						<input id="perspayee-expiry-month-rcp-3ds" class="perspayee-expiry-month-rcp-3ds span1" type="text" size="2" value="<?= $sPerspayeeExpiry[0] ?>"/>
						<span> / </span>
						<input id="perspayee-expiry-year-rcp-3ds" class="perspayee-expiry-year-rcp-3ds span1" type="text" size="4" value="<?= $sPerspayeeExpiry[1] ?>"/>
						<label for="perspayee-expiry-year-rcp-3ds" hidden="hidden"></label>
					</div>
				</div>
				<div class="controls">
					<div class="span1">
						<label for="recur-freq-rcp-3ds">Freq</label>
						<input id="recur-freq-rcp-3ds" class="recur-freq-rcp-3ds span1" type="text" size="4" value="<?= $sRecurFreq ?>"/>
					</div>
				</div>
			</div>

			<div class="controls">
				<div class="span6">
					<button class="submit-button btn btn-primary" type="submit">Buy now</button>
				</div>
			</div>
		</form>
	</div>
	<div class="well span8 token-result">
		<div class="span8"><label for="token">Token</label>
				<span id="token_container">
					<input id="token" name="token" class="token span6" type="text" size="20" value="" disabled />
				</span>
		</div>
	</div>

	<div class="controls">
		<div class="well span5 payment-errors-container" style="background-color: #ffcaba; display: none;">
			<div class="payment-errors text-error"></div>
		</div>
	</div>
	<!-- END: Payment form -->
</div>
<script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
<script src="ipspjs.js"></script>
<script language="javascript" type="text/javascript">
	$(document).ready(function () {

		var UrlVars = getUrlVars();

		// для 3DS через БМ
		if (UrlVars.message) {
			UrlVars.message = decodeURIComponent(UrlVars.message);
			IpspjsResponseHandler(UrlVars, null);
			return;
		} else if (UrlVars.token) {
			IpspjsResponseHandler(null, UrlVars);
			return;
		}

		function getUrlVars() {
			var vars = [], hash;
			var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
			for (var i = 0; i < hashes.length; i++) {
				hash = hashes[i].split('=');
				vars.push(hash[0]);
				vars[hash[0]] = hash[1];
			}
			return vars;
		}

		function IpspjsResponseHandler(error, result) {
			if (error) {
				$(".payment-errors-container").show();
				$(".payment-errors").text(error.message);
			} else {
				$(".payment-errors").text("");
				var token = result.token;
				$("#token").remove();
				$(".token-result").css({'background-color' : '#D2FBD3'});
				$("#token_container").append('<input id="token" name="token" class="token span4" type="text" size="20" value="' + token + '" />');
				$(".payment-submit-button").removeAttr("disabled");
			}
			$(".submit-button").removeAttr("disabled");
		}

		$("#payment-form").submit(function (event) {

			var cardNumber = $('.card-number').val();
			var cardExpiryMonth = $('.card-expiry-month').val();
			var cardExpiryYear = $('.card-expiry-year').val();

			var cardNumber3Ds = $('.card-number-3ds').val();
			var cardExpiryMonth3Ds = $('.card-expiry-month-3ds').val();
			var cardExpiryYear3Ds = $('.card-expiry-year-3ds').val();

			var cardNumberRcp = $('.card-number-rcp').val();
			var cardExpiryMonthRcp = $('.card-expiry-month-rcp').val();
			var cardExpiryYearRcp = $('.card-expiry-year-rcp').val();

			var cardNumberRcp3Ds = $('.card-number-rcp-3ds').val();
			var cardExpiryMonthRcp3Ds = $('.card-expiry-month-rcp-3ds').val();
			var cardExpiryYearRcp3Ds = $('.card-expiry-year-rcp-3ds').val();

			$('.submit-button').attr("disabled", "disabled");

			if (false == ipspjs.validateCardNumber(cardNumber)) {
				$(".payment-errors").text("Invalid card number");
				$(".submit-button").removeAttr("disabled");
				return false;
			}
			if (false == ipspjs.validateExpiry(cardExpiryMonth, cardExpiryYear)) {
				$(".payment-errors").text("Invalid date of expiry");
				$(".submit-button").removeAttr("disabled");
				return false;
			}

			if (false == ipspjs.validateCardNumber(cardNumber3Ds)) {
				$(".payment-errors").text("Invalid card number");
				$(".submit-button").removeAttr("disabled");
				return false;
			}
			if (false == ipspjs.validateExpiry(cardExpiryMonth3Ds, cardExpiryYear3Ds)) {
				$(".payment-errors").text("Invalid date of expiry");
				$(".submit-button").removeAttr("disabled");
				return false;
			}

			if (false == ipspjs.validateCardNumber(cardNumberRcp)) {
				$(".payment-errors").text("Invalid card number");
				$(".submit-button").removeAttr("disabled");
				return false;
			}
			if (false == ipspjs.validateExpiry(cardExpiryMonthRcp, cardExpiryYearRcp)) {
				$(".payment-errors").text("Invalid date of expiry");
				$(".submit-button").removeAttr("disabled");
				return false;
			}

			if (false == ipspjs.validateCardNumber(cardNumberRcp3Ds)) {
				$(".payment-errors").text("Invalid card number");
				$(".submit-button").removeAttr("disabled");
				return false;
			}
			if (false == ipspjs.validateExpiry(cardExpiryMonthRcp3Ds, cardExpiryYearRcp3Ds)) {
				$(".payment-errors").text("Invalid date of expiry");
				$(".submit-button").removeAttr("disabled");
				return false;
			}

			var method = 'cc';
			var action = 'getTokenAuthPayment';

			if (jQuery('#btn-paymenttype-cc-3ds').hasClass('btn-primary')) {
				method = 'cc-3ds';
			}
			if (jQuery('#btn-paymenttype-rcp').hasClass('btn-primary')) {
				method = 'rcp';
				action = 'recurringPaymentPrimary';
			}
			if (jQuery('#btn-paymenttype-rcp-3ds').hasClass('btn-primary')) {
				method = 'rcp-3ds';
				action = 'recurringPaymentPrimary';
			}

			switch (method) {
				// обычный
				case "cc":
					var params = {
						action:         action,
						amount:         $('.card-amount-int').val(),
						currency:       $('.card-currency').val(),
						number:         cardNumber,
						exp_month:      cardExpiryMonth,
						exp_year:       cardExpiryYear,
						cvc:            $('.card-cvc').val(),
						cardholder:     $('.card-holdername').val()
					};
					break;
				// 3DS
				case "cc-3ds":
					var params = {
						action:         action,
						amount:         $('.card-amount-int-3ds').val(),
						currency:       $('.card-currency-3ds').val(),
						number:         cardNumber3Ds,
						exp_month:      cardExpiryMonth3Ds,
						exp_year:       cardExpiryYear3Ds,
						cvc:            $('.card-cvc-3ds').val(),
						cardholder:     $('.card-holdername-3ds').val()
					};
					break;
				// первичный периодический обычный
				case "rcp":
					var params = {
						action:                 action,
						amount:                 $('.card-amount-int-rcp').val(),
						currency:               $('.card-currency-rcp').val(),
						number:                 cardNumberRcp,
						exp_month:              cardExpiryMonthRcp,
						exp_year:               cardExpiryYearRcp,
						cvc:                    $('.card-cvc-rcp').val(),
						cardholder:             $('.card-holdername-rcp').val(),
						biller_client_id:       $('.biller-client-id-rcp').val(),
						perspayee_expiry_month: $('.perspayee-expiry-month-rcp').val(),
						perspayee_expiry_year:  $('.perspayee-expiry-year-rcp').val(),
						recur_freq:             $('.recur-freq-rcp').val()
					};
					break;
				// первичный периодический 3DS
				case "rcp-3ds":
					var params = {
						action:                 action,
						amount:                 $('.card-amount-int-rcp-3ds').val(),
						currency:               $('.card-currency-rcp-3ds').val(),
						number:                 cardNumberRcp3Ds,
						exp_month:              cardExpiryMonthRcp3Ds,
						exp_year:               cardExpiryYearRcp3Ds,
						cvc:                    $('.card-cvc-rcp-3ds').val(),
						cardholder:             $('.card-holdername-rcp-3ds').val(),
						biller_client_id:       $('.biller-client-id-rcp-3ds').val(),
						perspayee_expiry_month: $('.perspayee-expiry-month-rcp-3ds').val(),
						perspayee_expiry_year:  $('.perspayee-expiry-year-rcp-3ds').val(),
						recur_freq:             $('.recur-freq-rcp-3ds').val()
					};
					break;
			}

			ipspjs.createToken(params, IpspjsResponseHandler);

			return false;
		});

		$(".paymenttype").click(function() {
			if (jQuery(this).hasClass('btn-primary')) return;

			var paymentType = jQuery('.paymenttype');
			var paymentInput = jQuery('.payment-input');

			paymentType.removeClass('btn-primary');
			jQuery(this).addClass('btn-primary');
			var index = paymentType.index(this);

			paymentInput.hide();
			paymentInput.eq(index).show();
		});
	});
</script>
</body>
</html>