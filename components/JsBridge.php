<?php

require_once 'IPSPPayment.php';

/**
 * Class JsBridge
 */
class JsBridge
{
	const C_RESPONSE_OK = 1;
	const C_RESPONSE_3DS_REQUIRED = 2;

	const C_ERROR_INITIAL = 200;
	const C_ERROR_UNKNOWN = 201;
	const C_ERROR_FATAL = 202;
	const C_ERROR_INTERNAL = 203;

	const C_ERROR_WRONG_REQUEST_ACTION = 300;
	const C_ERROR_WRONG_REQUEST_DATA = 301;
	const C_ERROR_WRONG_REQUEST_DATA_FORMAT = 302;
	const C_ERROR_INCOMPLETE_REQUEST_DATA = 303;
	const C_ERROR_INVALID_RESPONSE_URL = 304;

	const C_ERROR_UNKNOWN_KEY = 351;
	const C_ERROR_INVALID_SIGN = 352;

	const C_ERROR_TOKEN_NOT_EXIST = 400;
	const C_ERROR_TOKEN_EXPIRED = 401;
	const C_ERROR_TOKEN_ALREADY_EXIST = 402;
	const C_ERROR_INVALID_TOKEN_FORMAT = 403;
	const C_ERROR_INCOMPLETE_TOKEN_DATA = 404;
	const C_ERROR_WRONG_TOKEN_DATA = 405;

	const C_ERROR_WRONG_IPSP_RESPONSE = 500;

	const C_ERROR_PAYMENT_NOT_ACTIVE = 600;
	const C_ERROR_PRIMARY_RECURRING_PAYMENT_NOT_APPROVED = 601;

	const C_PAYMENT_STATE_NEW = 0;
	const C_PAYMENT_STATE_ACTIVE = 1;
	const C_PAYMENT_STATE_CLOSED = 2;
	const C_PAYMENT_STATE_3DS_FAILED = 3;
	const C_PAYMENT_STATE_REVERSED = 4;

	const C_ACTION_GET_TOKEN_AUTH_PAYMENT = 'getTokenAuthPayment';
	const C_ACTION_AUTH_PAYMENT_COMPLETION = 'authPaymentCompletion';
	const C_ACTION_PAYMENT_3DS_COMPLETION = 'payment3dsCompletion';
	const C_ACTION_RECURRING_PAYMENT_PRIMARY = 'recurringPaymentPrimary';
	const C_ACTION_RECURRING_PAYMENT_SECONDARY = 'recurringPaymentSecondary';

	const C_PAYMENT_COMPLETION_TYPE_COMPLETION = 'completion';
	const C_PAYMENT_COMPLETION_TYPE_REVERSAL = 'reversal';


	/**
	 * Ошибки
	 *
	 * @var array
	 */
	private static $aResponseMessages = [
		self::C_RESPONSE_OK                                  => 'OK',
		self::C_RESPONSE_3DS_REQUIRED                        => '3DS required',
		self::C_ERROR_INITIAL                                => 'Initial error',
		self::C_ERROR_UNKNOWN                                => 'Unknown error',
		self::C_ERROR_FATAL                                  => 'Fatal error',
		self::C_ERROR_INTERNAL                               => 'Internal error',
		self::C_ERROR_WRONG_REQUEST_ACTION                   => 'Wrong request action',
		self::C_ERROR_WRONG_REQUEST_DATA                     => 'Wrong request data',
		self::C_ERROR_WRONG_REQUEST_DATA_FORMAT              => 'Wrong request data format',
		self::C_ERROR_INCOMPLETE_REQUEST_DATA                => 'Incomplete request data',
		self::C_ERROR_INVALID_RESPONSE_URL                   => 'Invalid response URL',
		self::C_ERROR_UNKNOWN_KEY                            => 'Unknown key',
		self::C_ERROR_INVALID_SIGN                           => 'Invalid sign',
		self::C_ERROR_TOKEN_NOT_EXIST                        => 'Token is not exist',
		self::C_ERROR_TOKEN_EXPIRED                          => 'Expired tokens lifetime',
		self::C_ERROR_TOKEN_ALREADY_EXIST                    => 'Token is already exist',
		self::C_ERROR_INVALID_TOKEN_FORMAT                   => 'Invalid token format',
		self::C_ERROR_INCOMPLETE_TOKEN_DATA                  => 'Incomplete token data',
		self::C_ERROR_WRONG_TOKEN_DATA                       => 'Wrong token data',
		self::C_ERROR_WRONG_IPSP_RESPONSE                    => 'Wrong iPSP response',
		self::C_ERROR_PAYMENT_NOT_ACTIVE                     => 'Payment is not active',
		self::C_ERROR_PRIMARY_RECURRING_PAYMENT_NOT_APPROVED => 'Primary recurring payment is not approved',

	];

	/**
	 * Адрес iPSP Brige
	 *
	 * @var string
	 */
	private $sBridgeUrl;

	/**
	 * @var IPSPPayment
	 */
	private $oIPSPPayment;

	/**
	 * Время жизни токена
	 *
	 * @var int
	 */
	private $iTokenLifetime;

	/**
	 * Объект PDO
	 *
	 * @var PDO
	 */
	private $oDb;

	/**
	 * Требуемое действие
	 *
	 * @var string
	 */
	private $sAction;

	/**
	 * Данные из запроса
	 *
	 * @var array
	 */
	private $aData = [];

	/**
	 * Ответ в JSON
	 *
	 * @var string
	 */
	private $sResponse = null;


	public function __construct()
	{
		try {
			$this->sBridgeUrl = rtrim(Config::init()->get('bridge_url'), '/');
			$this->oIPSPPayment = new IPSPPayment(
				Config::init()->get('ipsp.product_id'),
				Config::init()->get('ipsp.pass_code'),
				Config::init()->get('ipsp.api_url'),
				Config::init()->get('ipsp.api_form_url')
			);
			$this->iTokenLifetime = Config::init()->get('token_lifetime');

			$this->oDb = new PDO(
				sprintf(
					'mysql:host=%s; dbname=%s',
					Config::init()->get('mysql.host'),
					Config::init()->get('mysql.database')
				),
				Config::init()->get('mysql.user'),
				Config::init()->get('mysql.password')
			);
			$this->oDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->oDb->query('SET NAMES UTF8');

		} catch (Exception $oException) {
			Logger::init()->error($oException);
			$this->setResponse(self::C_ERROR_INITIAL);
		}
	}

	/**
	 * Задать действие и данные запроса
	 *
	 * @param string $sAction
	 * @param array  $aData
	 */
	public function setRequest($sAction, array $aData = [])
	{
		Logger::init()->debug([], 'Called');

		try {

			$this->sAction = $sAction;

			Logger::init()->info($this->sAction, 'Action');

			$aData = $this->prepareRequestData($sAction, $aData);
			$this->aData = $this->translateRequestDataToIpspFormat($aData);

			Logger::init()->info($this->aData);

		} catch (JsBridgeException $oBridgeException) {
			Logger::init()->error($oBridgeException);
			$this->setResponse(
				$oBridgeException->getCode()
			);
		} catch (Exception $oException) {
			Logger::init()->error($oException);
			$this->setResponse(self::C_ERROR_FATAL);
		}
	}

	/**
	 * Получить ответ в формате JSON
	 *
	 * @return string
	 */
	public function getResponse()
	{
		Logger::init()->debug([], 'Called');

		if ($this->sResponse) {

			Logger::init()->warning([], 'Response existed before the action');
			Logger::init()->info($this->sResponse, 'Bridge response');

			return $this->sResponse;
		}

		try {

			$this->processAction();

		} catch (Exception $oException) {
			Logger::init()->error($oException);
			$this->setResponse(self::C_ERROR_FATAL);
		}

		Logger::init()->info($this->sResponse, 'Bridge response');

		return $this->sResponse;
	}

	/**
	 * Обработать действие
	 *
	 * @throws JsBridgeException
	 */
	private function processAction()
	{
		Logger::init()->debug([], 'Called');

		switch ($this->sAction) {
			case self::C_ACTION_GET_TOKEN_AUTH_PAYMENT:
				$this->actionGetTokenAuthPayment();
				break;
			case self::C_ACTION_AUTH_PAYMENT_COMPLETION:
				$this->actionAuthPaymentCompletion();
				break;
			case self::C_ACTION_PAYMENT_3DS_COMPLETION:
				$this->actionPayment3dsCompletion();
				break;
			case self::C_ACTION_RECURRING_PAYMENT_PRIMARY:
				$this->actionRecurringPaymentPrimary();
				break;
			case self::C_ACTION_RECURRING_PAYMENT_SECONDARY:
				$this->actionRecurringPaymentSecondary();
				break;
			default:
				throw new JsBridgeException(self::C_ERROR_WRONG_REQUEST_ACTION);
		}
	}

	/**
	 * Действие: получить токен и инициализировать платеж с авторизацией
	 */
	private function actionGetTokenAuthPayment()
	{
		Logger::init()->debug([], 'Called');

		try {
			list($iPublicKeyId, $sToken) = $this->parseAndVerifyChannelIdData($this->aData['channel_id']);

			// дополнить данные обязательными для iPSP полями, которые не передает скрипт JS-части
			// описание для БМ - обязательный параметр
			$aKeys = [
				'desc' => 'Description',
			];
			$this->aData = $this->completeArray($this->aData, $aKeys);

			// инициализировать новый авторизированный платеж
			$iResponseCode = $this->oIPSPPayment->authPayment(
				$this->aData
			);

			if ($iResponseCode == IPSPPayment::C_RESULT_FAIL) {
				$this->setResponse(
					self::C_ERROR_WRONG_IPSP_RESPONSE,
					[
						'jsonPFunction' => $this->aData['jsonPFunction'],
						'ipsp_code'     => $this->oIPSPPayment->getError()['code'],
					]
				);

				return;
			}

			if ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS) {
				// создать активный токен для платежа, если не нужен 3ds
				$iState = self::C_PAYMENT_STATE_ACTIVE;
				$this->setResponse(
					self::C_RESPONSE_OK,
					[
						'jsonPFunction' => $this->aData['jsonPFunction'],
						'ipsp_code'     => $this->oIPSPPayment->getError()['code'],
						'token'         => $sToken,
					]
				);
			} elseif ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS_3DS) {
				// создать новый токен для платежа, ожидающий активации после прохождения 3DS
				$iState = self::C_PAYMENT_STATE_NEW;
				$this->setResponse(
					self::C_RESPONSE_3DS_REQUIRED,
					[
						'jsonPFunction' => $this->aData['jsonPFunction'],
						'ipsp_code'     => $this->oIPSPPayment->getError()['code'],
						'token'         => $sToken,
						'3ds_data'      => array_merge(
							$this->oIPSPPayment->get3dsData(),
							[
								'TermUrl' => $this->getBackUrl($sToken),
							]
						),
					]
				);
			} else {
				throw new JsBridgeException(self::C_ERROR_WRONG_IPSP_RESPONSE);
			}

			$this->createToken(
				$sToken,
				$iPublicKeyId,
				$this->oIPSPPayment->getPaymentId(),
				$iState
			);

		} catch (JsBridgeException $oJsBridgeException) {
			Logger::init()->error(
				$oJsBridgeException,
				$this->getResponseMessage($oJsBridgeException->getCode())
			);
			$this->setResponse(
				$oJsBridgeException->getCode(),
				[
					'jsonPFunction' => $this->aData['jsonPFunction'],
				]
			);
		}
	}

	/**
	 * Действие: завершить платеж с авторизацией (одобрить или отменить)
	 */
	private function actionAuthPaymentCompletion()
	{
		Logger::init()->debug([], 'Called');

		try {
			$aTokenData = $this->getTokenDataIfExist($this->aData['token']);

			// проверить подпись
			$sMessage = $this->aData['token'] . $this->aData['type'] . $this->aData['timestamp'];
			$this->verifySign(
				$aTokenData['public_key_id'],
				$this->aData['sign'],
				$sMessage
			);

			$sType = $this->aData['type'];

			if ($aTokenData['payment_state'] != self::C_PAYMENT_STATE_ACTIVE) {
				throw new JsBridgeException(self::C_ERROR_PAYMENT_NOT_ACTIVE);
			}

			// платеж будет обработан по payment_id из токена и типу завершения
			if ($sType == self::C_PAYMENT_COMPLETION_TYPE_COMPLETION) {
				// провести платеж
				$iResponseCode = $this->oIPSPPayment->authPaymentCompletion(
					[
						'payment_id' => $aTokenData['payment_id'],
					]
				);
				$iPaymentState = self::C_PAYMENT_STATE_CLOSED;
			} elseif ($sType == self::C_PAYMENT_COMPLETION_TYPE_REVERSAL) {
				// отменить платеж
				$iResponseCode = $this->oIPSPPayment->authPaymentReversal(
					[
						'payment_id' => $aTokenData['payment_id'],
					]
				);
				$iPaymentState = self::C_PAYMENT_STATE_REVERSED;
			} else {
				throw new JsBridgeException(self::C_ERROR_WRONG_REQUEST_DATA);
			}

			if ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS) {
				// закрыть токен
				$this->updateTokenCompletion($this->aData['token'], $iPaymentState);
				$this->setResponse(
					self::C_RESPONSE_OK
				);
			} elseif ($iResponseCode == IPSPPayment::C_RESULT_FAIL) {
				$this->setResponse(
					self::C_ERROR_WRONG_IPSP_RESPONSE
				);
			} else {
				throw new JsBridgeException(self::C_ERROR_WRONG_IPSP_RESPONSE);
			}
		} catch (JsBridgeException $oJsBridgeException) {
			Logger::init()->error(
				$oJsBridgeException,
				$this->getResponseMessage($oJsBridgeException->getCode())
			);
			$this->setResponse(
				$oJsBridgeException->getCode()
			);
		}
	}

	/**
	 * Действие: запрос на завершение 3DS
	 */
	private function actionPayment3dsCompletion()
	{
		Logger::init()->debug([], 'Called');

		try {
			// проверка реальности токена
			$aTokenData = $this->getTokenDataIfExist($this->aData['token']);

			$this->checkTokenData($aTokenData, self::C_PAYMENT_STATE_NEW);

			$iResponseCode = $this->oIPSPPayment->payment3dsCompletion(
				$this->aData
			);

			$sRealResponseUrl = $this->getRealResponseUrl($aTokenData['response_url']);

			if ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS) {
				// активировать токен
				$this->updateTokenCompletion($this->aData['token'], self::C_PAYMENT_STATE_ACTIVE);
				// переадресация на страницу завершения
				$sRedirectUrl = sprintf(
					'%s?token=%s',
					$sRealResponseUrl,
					$aTokenData['token']
				);
			} elseif ($iResponseCode == IPSPPayment::C_RESULT_FAIL) {
				// заблокировать токен
				$this->updateTokenCompletion($this->aData['token'], self::C_PAYMENT_STATE_3DS_FAILED);

				// переадресация на страницу завершения с неудачным результатом
				$sRedirectUrl = sprintf(
					'%s?token=%s&message=%s',
					$sRealResponseUrl,
					$aTokenData['token'],
					$this->oIPSPPayment->getError()['message']
				);
			} else {
				throw new JsBridgeException(self::C_ERROR_WRONG_IPSP_RESPONSE);
			}

			Logger::init()->info($sRedirectUrl, '3DS completion redirect url');

			header('location: ' . $sRedirectUrl);

		} catch (JsBridgeException $oJsBridgeException) {
			Logger::init()->error(
				$oJsBridgeException,
				$this->getResponseMessage($oJsBridgeException->getCode())
			);
			// ничего не делать - будет показана пустая страница
		}
	}

	/**
	 * Действие: первичный периодический платеж
	 */
	private function actionRecurringPaymentPrimary()
	{
		Logger::init()->debug([], 'Called');

		try {
			list($iPublicKeyId, $sToken) = $this->parseAndVerifyChannelIdData($this->aData['channel_id']);

			$aKeys = [
				'desc'       => 'Description',
				'recur_freq' => Config::init()->get('recur_freq_default'),
			];
			$this->aData = $this->completeArray($this->aData, $aKeys);

			// инициализировать новый авторизированный платеж
			$iResponseCode = $this->oIPSPPayment->recurringPaymentPrimary(
				$this->aData
			);

			if ($iResponseCode == IPSPPayment::C_RESULT_FAIL) {
				$this->setResponse(
					self::C_ERROR_WRONG_IPSP_RESPONSE,
					[
						'jsonPFunction' => $this->aData['jsonPFunction'],
						'ipsp_code'     => $this->oIPSPPayment->getError()['code'],
					]
				);

				return;
			}

			if ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS) {
				// создать активный токен для платежа, если не нужен 3ds
				$iState = self::C_PAYMENT_STATE_ACTIVE;
				$this->setResponse(
					self::C_RESPONSE_OK,
					[
						'jsonPFunction' => $this->aData['jsonPFunction'],
						'ipsp_code'     => $this->oIPSPPayment->getError()['code'],
						'token'         => $sToken,
					]
				);
			} elseif ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS_3DS) {
				// создать новый токен для платежа, ожидающий активации после прохождения 3DS
				$iState = self::C_PAYMENT_STATE_NEW;
				$this->setResponse(
					self::C_RESPONSE_3DS_REQUIRED,
					[
						'jsonPFunction' => $this->aData['jsonPFunction'],
						'ipsp_code'     => $this->oIPSPPayment->getError()['code'],
						'token'         => $sToken,
						'3ds_data'      => array_merge(
							$this->oIPSPPayment->get3dsData(),
							[
								'TermUrl' => $this->getBackUrl($sToken),
							]
						),
					]
				);
			} else {
				throw new JsBridgeException(self::C_ERROR_WRONG_IPSP_RESPONSE);
			}

			$this->createToken(
				$sToken,
				$iPublicKeyId,
				$this->oIPSPPayment->getPaymentId(),
				$iState
			);

		} catch (JsBridgeException $oJsBridgeException) {
			Logger::init()->error(
				$oJsBridgeException,
				$this->getResponseMessage($oJsBridgeException->getCode())
			);
			$this->setResponse(
				$oJsBridgeException->getCode(),
				[
					'jsonPFunction' => $this->aData['jsonPFunction'],
				]
			);
		}
	}

	/**
	 * Действие: вторичный периодический платеж
	 */
	private function actionRecurringPaymentSecondary()
	{
		Logger::init()->debug([], 'Called');

		try {
			$aTokenData = $this->getTokenDataIfExist($this->aData['token']);

			// проверить подпись
			$sMessage = $this->aData['token'] . $this->aData['biller_client_id'] . $this->aData['amount'] .
				$this->aData['currency'] . $this->aData['timestamp'];
			$this->verifySign(
				$aTokenData['public_key_id'],
				$this->aData['sign'],
				$sMessage
			);

			// токен должен быть закрыт, т.к. первичный платеж должен быть проведен
			if ($aTokenData['payment_state'] != self::C_PAYMENT_STATE_CLOSED) {
				throw new JsBridgeException(self::C_ERROR_PRIMARY_RECURRING_PAYMENT_NOT_APPROVED);
			}

			$aKeys = [
				'desc' => 'Description',
			];
			$this->aData = $this->completeArray($this->aData, $aKeys);

			// провести платеж
			$iResponseCode = $this->oIPSPPayment->recurringPaymentSecondary(
				$this->aData
			);

			if ($iResponseCode == IPSPPayment::C_RESULT_SUCCESS) {
				$this->setResponse(
					self::C_RESPONSE_OK
				);
			} elseif ($iResponseCode == IPSPPayment::C_RESULT_FAIL) {
				$this->setResponse(
					self::C_ERROR_WRONG_IPSP_RESPONSE
				);
			} else {
				throw new JsBridgeException(self::C_ERROR_WRONG_IPSP_RESPONSE);
			}
		} catch (JsBridgeException $oJsBridgeException) {
			Logger::init()->error(
				$oJsBridgeException,
				$this->getResponseMessage($oJsBridgeException->getCode())
			);
			$this->setResponse(
				$oJsBridgeException->getCode()
			);
		}
	}

	/**
	 * Проверить продпись сообщения
	 *
	 * @param int    $iPublicKeyId
	 * @param string $sSign
	 * @param string $sMessage
	 *
	 * @throws JsBridgeException
	 * @return bool
	 */
	private function verifySign($iPublicKeyId, $sSign, $sMessage)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($iPublicKeyId, 'PublicKeyId');
		Logger::init()->info($sSign, 'Sign');
		Logger::init()->info($sMessage, 'Message');

		try {
			$oStatement = $this->oDb->prepare(
				'SELECT `public_key` FROM `public_keys` WHERE `id` = :id'
			);

			$oStatement->bindParam(':id', $iPublicKeyId, PDO::PARAM_INT);

			$oStatement->execute();

			$sResult = $oStatement->fetch(PDO::FETCH_NUM);

			if (empty($sResult[0])) {
				throw new JsBridgeException(self::C_ERROR_UNKNOWN_KEY);
			}

			$sPublicKey = $sResult[0];

			if (openssl_verify($sMessage, pack("H*", $sSign), $sPublicKey, OPENSSL_ALGO_SHA1) != 1) {
				throw new JsBridgeException(self::C_ERROR_INVALID_SIGN);
			}

			return true;

		} catch (PDOException $oPDOException) {
			Logger::init()->warning($oPDOException);
			throw new JsBridgeException(self::C_ERROR_INTERNAL);
		}
	}

	/**
	 * Создать новый токен c определенным статусом и добавить его в БД
	 *
	 * @param string $sToken
	 * @param int    $iPublicKeyId
	 * @param int    $iPaymentId
	 * @param int    $iPaymentCompletion
	 *
	 * @throws JsBridgeException
	 * @return bool
	 */
	private function createToken($sToken, $iPublicKeyId, $iPaymentId, $iPaymentCompletion)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($sToken, 'Token');
		Logger::init()->info($iPublicKeyId, 'PublicKeyId');
		Logger::init()->info($iPaymentId, 'PaymentId');
		Logger::init()->info($iPaymentCompletion, 'PaymentCompletion');

		// $iPaymentCompletion может быть 0
		if (!$sToken || !$iPublicKeyId || !$iPaymentId) {
			throw new JsBridgeException(self::C_ERROR_INCOMPLETE_TOKEN_DATA);
		}

		// неверный URL для завершения 3DS
		if (
			strpos(
				$this->aData['response_url'],
				sprintf('%s/?parentUrl=', $this->sBridgeUrl)
			) !== 0
		) {
			throw new JsBridgeException(self::C_ERROR_INVALID_RESPONSE_URL);
		}

		if ($this->checkTokenExist($sToken)) {
			throw new JsBridgeException(self::C_ERROR_TOKEN_ALREADY_EXIST);
		}

		try {
			$sDate = date('Y-m-d H:i:s', time());

			$oStatement = $this->oDb->prepare(
				'INSERT INTO `tokens`
				(`token`, `public_key_id`, `jsonPFunction`, `response_url`, `payment_id`, `payment_state`, `dt_add`)
				VALUES
				(:token, :public_key_id, :jsonPFunction, :response_url, :payment_id, :payment_state, :dt_add)'
			);

			$oStatement->bindParam(':token', $sToken, PDO::PARAM_STR);
			$oStatement->bindParam(':public_key_id', $iPublicKeyId, PDO::PARAM_INT);
			$oStatement->bindParam(':jsonPFunction', $this->aData['jsonPFunction'], PDO::PARAM_STR);
			$oStatement->bindParam(':response_url', $this->aData['response_url'], PDO::PARAM_STR);
			$oStatement->bindParam(':payment_id', $iPaymentId, PDO::PARAM_INT);
			$oStatement->bindParam(':payment_state', $iPaymentCompletion, PDO::PARAM_INT);
			$oStatement->bindParam(':dt_add', $sDate, PDO::PARAM_STR);

			$oStatement->execute();

			return true;

		} catch (PDOException $oPDOException) {
			Logger::init()->warning($oPDOException);
			throw new JsBridgeException(self::C_ERROR_INTERNAL);
		}
	}

	/**
	 * Получить данные по токену, если он существует
	 *
	 * @param string $sToken
	 *
	 * @throws JsBridgeException
	 * @return array
	 */
	private function getTokenDataIfExist($sToken)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($sToken, 'Token');

		$aTokenData = $this->getTokenData($sToken);

		if (!$aTokenData) {
			throw new JsBridgeException(self::C_ERROR_TOKEN_NOT_EXIST);
		}

		return $aTokenData;
	}

	/**
	 * Получить данные по токену
	 *
	 * @param string $sToken
	 *
	 * @throws JsBridgeException
	 * @return array
	 */
	private function getTokenData($sToken)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($sToken, 'Token');

		$this->checkTokenFormat($sToken);

		try {
			$oStatement = $this->oDb->prepare(
				'SELECT * FROM `tokens` WHERE `token` = :token'
			);

			$oStatement->bindParam(':token', $sToken, PDO::PARAM_STR);

			$oStatement->execute();

			$aTokenData = $oStatement->fetch(PDO::FETCH_ASSOC);

			if (!$aTokenData) {
				return [];
			}

			return $aTokenData;

		} catch (PDOException $oPDOException) {
			Logger::init()->warning($oPDOException);
			throw new JsBridgeException(self::C_ERROR_INTERNAL);
		}
	}

	/**
	 * Изменить статус завершенности токена
	 *
	 * @param string $sToken
	 * @param string $iPaymentState
	 *
	 * @throws JsBridgeException
	 */
	private function updateTokenCompletion($sToken, $iPaymentState)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($sToken, 'Token');
		Logger::init()->info($iPaymentState, 'PaymentState');

		try {
			$oStatement = $this->oDb->prepare(
				'UPDATE `tokens` SET `payment_state` = :payment_state, `dt_update` = :dt_update WHERE `token` = :token'
			);
			$oStatement->bindParam(':token', $sToken, PDO::PARAM_STR);
			$oStatement->bindParam(':payment_state', $iPaymentState, PDO::PARAM_INT);
			$oStatement->bindParam(':dt_update', date('Y-m-d H:i:s', time()), PDO::PARAM_STR);

			$oStatement->execute();

		} catch (PDOException $oPDOException) {
			Logger::init()->warning($oPDOException);
			throw new JsBridgeException(self::C_ERROR_INTERNAL);
		}
	}

	/**
	 * Задать ответ сервиса
	 *
	 * @param int   $iCode
	 * @param array $aData
	 * @param bool  $bRewrite
	 */
	private function setResponse($iCode, array $aData = [], $bRewrite = false)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($iCode, 'Response code');
		Logger::init()->info($aData, 'Response data');

		// будет возвращен первый заданный ответ, если нет флага перезаписи
		if ($this->sResponse !== null && !$bRewrite) {
			return;
		}

		// задать особую форму ответа для некоторых действий
		switch ($this->sAction) {
			case self::C_ACTION_GET_TOKEN_AUTH_PAYMENT:
				$this->setTokenResponse($iCode, $aData);
				break;
			case self::C_ACTION_AUTH_PAYMENT_COMPLETION:
				$this->setPaymentResponse($iCode);
				break;
			case self::C_ACTION_RECURRING_PAYMENT_PRIMARY:
				$this->setTokenResponse($iCode, $aData);
				break;
			case self::C_ACTION_RECURRING_PAYMENT_SECONDARY:
				$this->setSecondaryRecurringResponse($iCode);
				break;
			default:
				$this->setEmptyResponse();
		}
	}

	/**
	 * Задать ответ на запрос токена
	 *
	 * @param int   $iCode
	 * @param array $aData
	 */
	private function setTokenResponse($iCode, array $aData = [])
	{
		Logger::init()->debug([], 'Called');

		// дополнить
		$aKeys = [
			'jsonPFunction' => '',
			'ipsp_code'     => 0,
			'token'         => '',
			'3ds_data'      => [],
		];
		$aData = $this->completeArray($aData, $aKeys);

		// данные стандартного ответа
		$aResponse = [
			'transaction' => [
				'payment'        => [
					'code' => $aData['ipsp_code'],
				],
				'processing'     => [
					'code'      => $iCode,
					'result'    => 'ACK',
					'timestamp' => date('Y-m-d H:i:s', time()),
				],
				'identification' => [
					'uniqueId' => $aData['token'],
				],
			],
		];

		// ошибка
		if ($iCode != self::C_RESPONSE_OK && $iCode != self::C_RESPONSE_3DS_REQUIRED) {
			$aResponse['error']['message'] = $this->getResponseMessage($iCode);
			$aResponse['transaction']['processing']['result'] = 'NOK';
		} elseif ($iCode == self::C_RESPONSE_3DS_REQUIRED && $aData['3ds_data']) {
			// данные для 3DS, если нужны
			$aResponse['transaction']['processing']['redirect'] = [
				'parameters' => [
					'PaReq'   => $aData['3ds_data']['PaReq'],
					'TermUrl' => $aData['3ds_data']['TermUrl'],
					'MD'      => $aData['3ds_data']['MD'],
				],
				'url'        => $aData['3ds_data']['Url'],
			];
		}

		$this->sResponse = sprintf('%s(%s)', $aData['jsonPFunction'], json_encode($aResponse, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Задать ответ на запрос завершения платежа
	 *
	 * @param int $iCode
	 */
	private function setPaymentResponse($iCode)
	{
		Logger::init()->debug([], 'Called');

		if ($iCode == self::C_RESPONSE_OK) {
			$aResponse = [
				'result' => 'ACK',
			];
		} else {
			$aResponse = [
				'result' => 'NOK',
				'error'  => [
					'code'    => $iCode,
					'message' => $this->getResponseMessage($iCode),
				],
			];
		}

		$this->sResponse = json_encode($aResponse, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Задать ответ на запрос совершения повторного периодического платежа
	 *
	 * @param int $iCode
	 */
	private function setSecondaryRecurringResponse($iCode)
	{
		Logger::init()->debug([], 'Called');

		if ($iCode == self::C_RESPONSE_OK) {
			$aResponse = [
				'result' => 'ACK',
			];
		} else {
			$aResponse = [
				'result' => 'NOK',
				'error'  => [
					'code'    => $iCode,
					'message' => $this->getResponseMessage($iCode),
				],
			];
		}

		$this->sResponse = json_encode($aResponse, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Задать пустой ответ
	 */
	private function setEmptyResponse()
	{
		Logger::init()->debug([], 'Called');

		$this->sResponse = '';
	}

	/**
	 * Получить сообщение для ответа по коду
	 *
	 * @param int $iCode
	 *
	 * @return mixed
	 */
	private function getResponseMessage($iCode)
	{
		Logger::init()->debug([], 'Called');

		if (!array_key_exists($iCode, self::$aResponseMessages)) {
			$iCode = self::C_ERROR_UNKNOWN;
		}

		return self::$aResponseMessages[$iCode];
	}

	/**
	 * Дополнить массив ключами со значениями по умолчанию, если таких ключей нет
	 *
	 * @param array $aData
	 * @param array $aKeys
	 *
	 * @return array
	 */
	private function completeArray(array $aData, array $aKeys)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->debug($aData, 'Data');
		Logger::init()->debug($aKeys, 'Keys');

		array_walk(
			$aKeys,
			function ($mDefaultValue, $sKey) use (&$aData) {
				if (empty($aData[$sKey])) {
					$aData[$sKey] = $mDefaultValue;
				}
			}
		);

		return $aData;
	}

	/**
	 * Проверить данные токена
	 *
	 * @param array $aTokenData
	 * @param int   $iPaymentCompletion
	 *
	 * @throws JsBridgeException
	 * @return bool
	 */
	private function checkTokenData(array $aTokenData, $iPaymentCompletion = self::C_PAYMENT_STATE_ACTIVE)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($aTokenData, 'TokenData');
		Logger::init()->info($iPaymentCompletion, 'PaymentCompletion');

		// не найден
		if (!$aTokenData) {
			throw new JsBridgeException(self::C_ERROR_TOKEN_NOT_EXIST);
		}

		// не в нужном состоянии
		if ($aTokenData['payment_state'] != $iPaymentCompletion) {
			throw new JsBridgeException(self::C_ERROR_WRONG_TOKEN_DATA);
		}

		// просрочен
		if ((time() - strtotime($aTokenData['dt_add'])) >= Config::init()->get('token_lifetime')) {
			throw new JsBridgeException(self::C_ERROR_TOKEN_EXPIRED);
		}

		return true;
	}

	/**
	 * Проверить формат токена (длина, символы)
	 *
	 * @param string $sToken
	 *
	 * @throws JsBridgeException
	 * @return bool
	 */
	private function checkTokenFormat($sToken)
	{
		Logger::init()->debug([], 'Called');

		if (!preg_match('|^[a-zA-Z0-9]{32}$|', $sToken)) {
			throw new JsBridgeException(self::C_ERROR_INVALID_TOKEN_FORMAT);
		}

		return true;
	}

	/**
	 * Проверить существует ли токен
	 *
	 * @param string $sToken
	 *
	 * @throws JsBridgeException
	 * @return bool
	 */
	public function checkTokenExist($sToken)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($sToken, 'Token');

		if (count($this->getTokenData($sToken))) {
			return true;
		}

		return false;
	}

	/**
	 * Получить стандартный обратный адрес
	 *
	 * @param string $sToken
	 *
	 * @return string
	 */
	private function getBackUrl($sToken)
	{
		Logger::init()->debug([], 'Called');
		Logger::init()->info($sToken, 'Token');

		return sprintf(
			'%s/?action=%s&token=%s',
			$this->sBridgeUrl,
			self::C_ACTION_PAYMENT_3DS_COMPLETION,
			$sToken
		);
	}

	/**
	 * Подготовить массив данных для определенного действия
	 *
	 * @param string $sAction
	 * @param array  $aRequestData
	 *
	 * @throws JsBridgeException
	 * @return array
	 */
	private function prepareRequestData($sAction, array $aRequestData)
	{
		Logger::init()->debug([], 'Called');

		$aNotRequiredFields = [];

		switch ($sAction) {
			case self::C_ACTION_GET_TOKEN_AUTH_PAYMENT:
				$aRequiredFields = [
					'channel_id',
					'jsonPFunction',
					'account_number',
					'account_expiry_month',
					'account_expiry_year',
					'account_verification',
					'account_holder',
					'presentation_amount3D',
					'presentation_currency3D',
					'response_url', // нужен для 3DS, приходит всегда
					'ip_address', // нужен iPSP, приходит всегда
				];
				break;
			case self::C_ACTION_AUTH_PAYMENT_COMPLETION:
				$aRequiredFields = [
					'token',
					'type',
					'timestamp',
					'sign',
				];
				break;
			case self::C_ACTION_PAYMENT_3DS_COMPLETION:
				$aRequiredFields = [
					'token',
					'PaRes',
					'MD',
				];
				break;
			case self::C_ACTION_RECURRING_PAYMENT_PRIMARY:
				$aRequiredFields = [
					'channel_id',
					'jsonPFunction',
					'account_number',
					'account_expiry_month',
					'account_expiry_year',
					'account_verification',
					'account_holder',
					'presentation_amount3D',
					'presentation_currency3D',
					'response_url',
					'ip_address',
					'recurring_biller_client_id',
					'recurring_perspayee_expiry_month',
					'recurring_perspayee_expiry_year',
				];
				$aNotRequiredFields = [
					'recurring_recur_freq',
				];
				break;
			case self::C_ACTION_RECURRING_PAYMENT_SECONDARY:
				$aRequiredFields = [
					'token',
					'timestamp',
					'sign',
					'biller_client_id',
					'amount',
					'currency',
				];
				break;
			default:
				$aRequiredFields = [];
		}

		Logger::init()->debug($aRequiredFields, 'RequiredFields');

		$aData = [];

		foreach ($aRequiredFields as $sField) {
			// пустое значение в существующем обязательном поле валидно
			if (!isset($aRequestData[$sField])) {
				throw new JsBridgeException(self::C_ERROR_INCOMPLETE_REQUEST_DATA);
			}
			$aData[$sField] = $aRequestData[$sField];
		}
		foreach ($aNotRequiredFields as $sField) {
			$aData[$sField] = $aRequestData[$sField];
		}

		// дата истечения карты
		if (!empty($aData['account_expiry_month']) && !empty($aData['account_expiry_year'])) {
			list($aData['account_expiry_month'], $aData['account_expiry_year']) =
				$this->prepareDate(
					$aData['account_expiry_month'], $aData['account_expiry_year']
				);
		}

		// дата истечения периодического платежа. формат: mmyy
		if (!empty($aData['recurring_perspayee_expiry_month']) && !empty($aData['recurring_perspayee_expiry_year'])) {
			list($aData['recurring_perspayee_expiry_month'], $aData['recurring_perspayee_expiry_year']) =
				$this->prepareDate(
					$aData['recurring_perspayee_expiry_month'], $aData['recurring_perspayee_expiry_year']
				);
			$aData['recurring_perspayee_expiry'] =
				$aData['recurring_perspayee_expiry_month'] . $aData['recurring_perspayee_expiry_year'];
		}

		return $aData;
	}

	/**
	 * Перевести названия полей из формата запроса в формат iPSP
	 *
	 * @param array $aRequestData
	 *
	 * @return array
	 */
	private function translateRequestDataToIpspFormat(array $aRequestData)
	{
		Logger::init()->debug([], 'Called');

		$aTranslate = [
			'account_number'             => 'pan',
			'account_expiry_month'       => 'exp_date_m',
			'account_expiry_year'        => 'exp_date_y',
			'account_verification'       => 'cvv',
			'account_holder'             => 'cardholder',
			'presentation_amount3D'      => 'amount',
			'presentation_currency3D'    => 'currency',
			'recurring_biller_client_id' => 'biller_client_id',
			'recurring_perspayee_expiry' => 'perspayee_expiry',
			'recurring_recur_freq'       => 'recur_freq',
		];

		foreach ($aTranslate as $sRequestKey => $sIpspKey) {
			if (isset($aRequestData[$sRequestKey])) {
				$aRequestData[$sIpspKey] = $aRequestData[$sRequestKey];
				unset($aRequestData[$sRequestKey]);
			}
		}

		return $aRequestData;
	}

	/**
	 * Разобрать данные channel_id из запроса и проверить подпись переданного токена.
	 * Возвращается массив из id публичного ключа RSA и токена
	 *
	 * @param string $sChannelId
	 *
	 * @throws JsBridgeException
	 * @return array
	 */
	private function parseAndVerifyChannelIdData($sChannelId)
	{
		Logger::init()->debug([], 'Called');

		$iPublicKeyId = substr($sChannelId, 0, 6);
		$sToken = substr($sChannelId, 6, 32);
		$sTimestamp = substr($sChannelId, 38, 10);
		$sSign = substr($sChannelId, 48);

		// проверить подпись
		$sMessage = substr($sChannelId, 0, 48);
		$this->verifySign(
			intval($iPublicKeyId),
			$sSign,
			$sMessage
		);

		if ((time() - $sTimestamp) >= Config::init()->get('token_lifetime')) {
			throw new JsBridgeException(self::C_ERROR_TOKEN_EXPIRED);
		}

		$this->checkTokenFormat($sToken);

		return [$iPublicKeyId, $sToken];
	}

	/**
	 * Подготовить дату: сократить год до последних 2 цифр, ведущий ноль перед месяцем.
	 *
	 * @param string $sMonth
	 * @param string $sYear
	 *
	 * @throws JsBridgeException
	 * @return array
	 */
	private function prepareDate($sMonth, $sYear)
	{
		Logger::init()->debug([], 'Called');

		$sMonth = str_pad($sMonth, 2, '0', STR_PAD_LEFT);
		$sYear = substr($sYear, -2, 2);

		return [$sMonth, $sYear];
	}

	/**
	 * Получить реальный адрес страницы, с которой был выполнен запрос на проведение платежа
	 *
	 * @param string $sResponseUrl
	 *
	 * @return string
	 */
	private function getRealResponseUrl($sResponseUrl)
	{
		Logger::init()->debug([], 'Called');

		$sRealResponseUrl = urldecode(
			urldecode(
				substr($sResponseUrl, strpos($sResponseUrl, 'parentUrl=') + 10) // 10 - strlen('parentUrl=')
			)
		);

		return rtrim($sRealResponseUrl, '?&');
	}
}

class JsBridgeException extends Exception
{
	public function __construct($code = 0, $message = "")
	{
		$this->code = $code;
		$this->message = $message;
	}
}