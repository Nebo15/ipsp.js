<?php

/**
 * Class IPSPPayment
 */
class IPSPPayment
{
	const C_CURL_TIMEOUT = 60;
	const C_CURL_CONNECT_TIMEOUT = 60;

	const C_PAYMENT_TYPE_SALE = 'S';
	const C_PAYMENT_TYPE_AUTH = 'A';
	const C_PAYMENT_TYPE_REFUND = 'R';

	const C_SYSTEM_ERROR_NONE = 0;
	const C_SYSTEM_ERROR_UNKNOWN = -10;
	const C_SYSTEM_ERROR_REQUEST = -20;
	const C_SYSTEM_ERROR_REQUEST_DATA = -30;
	const C_SYSTEM_ERROR_RESPONSE = -40;
	const C_SYSTEM_ERROR_RESPONSE_3DS_DATA = -41;
	const C_SYSTEM_ERROR_CERT = -50;
	const C_SYSTEM_ERROR_HTTP = -60;

	/**
	 * Код ответа: удачно
	 */
	const C_RESULT_SUCCESS = 1;

	/**
	 * Код ответа: нужно пройти 3DS
	 */
	const C_RESULT_SUCCESS_3DS = 2;

	/**
	 * Код ответа: ошибка
	 */
	const C_RESULT_FAIL = -1;

	/**
	 * Путь к SSL-сертификатам
	 *
	 * @var string
	 */
	private $sCertPath = '';

	/**
	 * Файл SSL-сертификата
	 *
	 * @var string
	 */
	private $sCertFile = 'cert.crt';

	/**
	 * SSL-ключ
	 *
	 * @var string
	 */
	private $sCertKey = 'cert.key';

	/**
	 * Адрес хоста iPSP
	 *
	 * @var string
	 */
	private $sApiUrl;

	/**
	 * Адрес хоста iPSP для эмуляции работы через браузер
	 *
	 * @var string
	 */
	private $sApiFormUrl;

	/**
	 * ID продукта - выдается в iPSP
	 *
	 * @var int
	 */
	private $iProductId;

	/**
	 * ID продукта - выдается в iPSP
	 *
	 * @var string
	 */
	private $sPassCode;

	/**
	 * ID платежа, генерируется в iPSP
	 *
	 * @var int
	 */
	private $iPaymentId = 0;

	/**
	 * Авторизационный ID, генерируется в iPSP
	 *
	 * @var string
	 */
	private $sAuthIdCode;

	/**
	 * RC
	 *
	 * @var int
	 */
	private $iRc;

	/**
	 * Ссылка для прохождения 3DS
	 *
	 * @var string
	 */
	private $s3dsLink;

	/**
	 * 3DS Payment request
	 *
	 * @var string
	 */
	private $s3dsPaReq;

	/**
	 * 3DS Merchant Digest
	 *
	 * @var string
	 */
	private $s3dsMD;

	/**
	 * Код ошибки
	 *
	 * @var int
	 */
	private $iErrorCode = 0;

	/**
	 * Сообщение ошибки
	 *
	 * @var string
	 */
	private $sErrorMessage = '';

	/**
	 * Ошибки, возвращаемые iPSP или Системой
	 *
	 * @var array
	 */
	private static $aErrors = [
		// iPSP
		100                                    => 'Wrong input data',
		101                                    => 'Card type banned by site settings',
		102                                    => 'Incorrect card number',
		200                                    => 'Security check failed',
		300                                    => '3-D Secure failed',
		400                                    => 'Hash validation failed, hashes mismatch',
		401                                    => 'Voided',
		402                                    => 'Authorization reversed',
		500                                    => 'Internal error',
		501                                    => 'Security violation',
		502                                    => 'Server or merchant settings misconfiguration',
		751                                    => 'Decline reason message: transaction destination cannot be found for routin',
		755                                    => 'Step Details: Decline reason message: card issuer unavailable',
		// System
		self::C_SYSTEM_ERROR_NONE              => '',
		self::C_SYSTEM_ERROR_UNKNOWN           => 'Unknown error',
		self::C_SYSTEM_ERROR_REQUEST           => 'Request error',
		self::C_SYSTEM_ERROR_REQUEST_DATA      => 'Wrong request data',
		self::C_SYSTEM_ERROR_RESPONSE          => 'Response error',
		self::C_SYSTEM_ERROR_RESPONSE_3DS_DATA => 'Incorrect response 3DS data',
		self::C_SYSTEM_ERROR_CERT              => 'Cert error',
		self::C_SYSTEM_ERROR_HTTP              => 'HTTP error',
	];

	/**
	 * Создать с заданным ID продукта и кодовой фразой
	 *
	 * @param int    $iProductId
	 * @param string $sPassCode
	 * @param string $sApiUrl
	 * @param string $sApiFormUrl
	 */
	public function __construct($iProductId, $sPassCode, $sApiUrl, $sApiFormUrl)
	{
		$this->iProductId = $iProductId;
		$this->sPassCode = $sPassCode;
		$this->sApiUrl = $sApiUrl;
		$this->sApiFormUrl = $sApiFormUrl;
	}

	/**
	 * Установить сертификаты, если требуется
	 *
	 * @param string $sCertPath
	 * @param string $sCertFile
	 * @param string $sCertKey
	 */
	public function setCerts($sCertPath, $sCertFile, $sCertKey)
	{
		$this->sCertPath = rtrim($sCertPath, '/') . '/';
		$this->sCertFile = $sCertFile;
		$this->sCertKey = $sCertKey;
	}

	/**
	 * Запрос на инициализацию платежа через iPSP
	 *
	 * @param array $aData [pan, exp_date_m, exp_date_y, cvv, cardholder, amount,
	 *                     currency, desc, ip_address]
	 *
	 * @return int
	 */
	public function authPayment(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		return $this->payment(self::C_PAYMENT_TYPE_AUTH, $aData);
	}

	/**
	 * Запрос на завершение платежа
	 *
	 * @param array $aData [payment_id]
	 *
	 * @return int
	 */
	public function authPaymentCompletion(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'payment_id',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['sales_completion'] = 'true';
		$aRequestData['payment_type'] = self::C_PAYMENT_TYPE_AUTH;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на отмену платежа
	 *
	 * @param array $aData [payment_id]
	 *
	 * @return int
	 */
	public function authPaymentReversal(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'payment_id',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['void'] = 'true';
		$aRequestData['payment_type'] = self::C_PAYMENT_TYPE_AUTH;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на перерасчет
	 *
	 * @param array $aData [payment_id]
	 *
	 * @return int
	 */
	public function authPaymentRefund(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'payment_id',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['payment_type'] = self::C_PAYMENT_TYPE_REFUND;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на проведение платежа через iPSP
	 *
	 * @param array $aData [pan, exp_date_m, exp_date_y, cvv, cardholder, amount,
	 *                     currency, desc, ip_address]
	 *
	 * @return int
	 */
	public function salePayment(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		return $this->payment(self::C_PAYMENT_TYPE_SALE, $aData);
	}

	/**
	 * Запрос на завершение платежа через iPSP
	 *
	 * @param array $aData [payment_id]
	 *
	 * @return int
	 */
	public function saleCompletion(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'payment_id',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['sales_completion'] = 'true';
		$aRequestData['payment_type'] = self::C_PAYMENT_TYPE_AUTH;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на завершение платежа 3DS
	 *
	 * @param array $aData [PaRes, MD]
	 *
	 * @return int
	 */
	public function payment3dsCompletion(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'PaRes',
				'MD',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на проведение первичного периодического платежа (авторизационный)
	 *
	 * @param array $aData [pan, exp_date_m, exp_date_y, cvv, cardholder, amount,
	 *                     currency, desc, ip_address, biller_client_id, perspayee_expiry,
	 *                     recur_freq]
	 *
	 * @return int
	 */
	public function recurringPaymentPrimary(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'pan',
				'exp_date_m',
				'exp_date_y',
				'cvv',
				'cardholder',
				'amount',
				'currency',
				'desc',
				'ip_address',
				'biller_client_id',
				'perspayee_expiry',
				'recur_freq',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['payment_type'] = self::C_PAYMENT_TYPE_AUTH;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на проведение повторного периодического платежа
	 *
	 * @param array $aData [biller_client_id, amount, currency, desc]
	 *
	 * @return int
	 */
	public function recurringPaymentSecondary(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'biller_client_id',
				'amount',
				'currency',
				'desc',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['payment_type'] = self::C_PAYMENT_TYPE_SALE;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Запрос на инициализацию платежа через iPSP (эмуляция работы с формой через браузер)
	 *
	 * @param array $aData [amount, currency, desc, pan, exp_date_m, exp_date_y, cvv,
	 *                     cardholder, amount, currency, desc]
	 *
	 * @return int
	 */
	public function authPaymentForm(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		return $this->paymentForm(self::C_PAYMENT_TYPE_AUTH, $aData);
	}

	/**
	 * Запрос на проведение платежа через iPSP (эмуляция работы с формой через браузер)
	 *
	 * @param array $aData [amount, currency, desc, pan, exp_date_m, exp_date_y, cvv,
	 *                     cardholder, amount, currency, desc]
	 *
	 * @return int
	 */
	public function salePaymentForm(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		return $this->paymentForm(self::C_PAYMENT_TYPE_SALE, $aData);
	}

	/**
	 * Запрос на завершение платежа 3DS (эмуляция работы с формой через браузер)
	 *
	 * @param array $aData [PaRes, MD]
	 *
	 * @return int
	 */
	public function payment3dsCompletionForm(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aRequestData = $this->getPaymentRequestData(
			[
				'PaRes',
				'MD',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		return $this->getResponseHtml($aRequestData);
	}

	/**
	 * Запрос на ребилл через iPSP (эмуляция работы с формой через браузер)
	 *
	 * @param array $aData [payment_id, cvv, amount, currency, desc, ip_address, cf, cf2]
	 *
	 * @return int
	 */
	public function authPaymentRebillForm(array $aData)
	{
		Logger::init()->info($aData, 'Data');

		$aPreRequestData = $this->getPaymentRequestData(
			[
				'payment_id',
				'amount',
				'currency',
				'desc',
				'ip_address',
				'cf',
				'cf2',
			],
			$aData
		);

		if (!$aPreRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные предварительного запроса
		$aPreRequestData['payment_type'] = self::C_PAYMENT_TYPE_AUTH;
		$aPreRequestData['product_id'] = $this->iProductId;
		$aPreRequestData['rebill3d'] = 'true';
		$aPreRequestData['hash'] = $this->getHash($aPreRequestData);

		// код формы данных карты
		$sCardFormHtml = $this->request($this->sApiFormUrl, $aPreRequestData);
		if (!$sCardFormHtml) {
			return self::C_RESULT_FAIL;
		}

		$this->iPaymentId = $this->parsePaymentId($sCardFormHtml);
		if (!$this->iPaymentId) {
			$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

			return self::C_RESULT_FAIL;
		}

		// извлечь из формы идентификатор сессии
		$sSessionId = $this->parseSessionId($sCardFormHtml);
		if (!$sSessionId) {
			$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

			return self::C_RESULT_FAIL;
		}

		$aRequestData = $this->getPaymentRequestData(
			[
				'payment_id',
				'cvv',
				'amount',
				'currency',
				'desc',
				'ip_address',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['iframe'] = 'false';
		$aRequestData['sessid'] = $sSessionId;

		return $this->getResponseHtml($aRequestData);
	}

	/**
	 * Получить данные для прохождения 3DS
	 *
	 * @return array
	 */
	public function get3dsData()
	{
		return [
			'Url'   => $this->s3dsLink,
			'PaReq' => $this->s3dsPaReq,
			'MD'    => $this->s3dsMD,
		];
	}

	/**
	 * Получить ID платежа
	 *
	 * @return string
	 */
	public function getPaymentId()
	{
		return $this->iPaymentId;
	}

	/**
	 * Получить auth_id_code
	 *
	 * @return string
	 */
	public function getAuthIdCode()
	{
		return $this->sAuthIdCode;
	}

	/**
	 * Получить RC
	 *
	 * @return int
	 */
	public function getRc()
	{
		return $this->iRc;
	}

	/**
	 * Получить код ошибки и ее текст: [code, message]
	 *
	 * @return array
	 */
	public function getError()
	{
		return [
			'code'    => $this->iErrorCode,
			'message' => $this->sErrorMessage,
		];
	}

	/**
	 * Платежный запрос к iPSP заданного типа
	 *
	 * @param string $sPaymentType
	 * @param array  $aData
	 *
	 * @return int
	 */
	private function payment($sPaymentType, array $aData)
	{
		$aRequestData = $this->getPaymentRequestData(
			[
				'pan',
				'exp_date_m',
				'exp_date_y',
				'cvv',
				'cardholder',
				'amount',
				'currency',
				'desc',
				'ip_address',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['payment_type'] = $sPaymentType;
		$aRequestData['product_id'] = $this->iProductId;
		$aRequestData['hash'] = $this->getHash($aRequestData);

		return $this->getResponse($aRequestData);
	}

	/**
	 * Платежный запрос к iPSP заданного типа (эмуляция работы с формой через браузер)
	 *
	 * @param string $sPaymentType
	 * @param array  $aData
	 *
	 * @return int
	 */
	private function paymentForm($sPaymentType, array $aData)
	{
		$aPreRequestData = $this->getPaymentRequestData(
			[
				'amount',
				'currency',
				'desc',
			],
			$aData
		);

		if (!$aPreRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные предварительного запроса
		$aPreRequestData['payment_type'] = $sPaymentType;
		$aPreRequestData['product_id'] = $this->iProductId;
		$aPreRequestData['hash'] = $this->getHash($aPreRequestData);

		// код формы данных карты
		$sCardFormHtml = $this->request($this->sApiFormUrl, $aPreRequestData);
		if (!$sCardFormHtml) {
			return self::C_RESULT_FAIL;
		}

		$this->iPaymentId = $this->parsePaymentId($sCardFormHtml);
		if (!$this->iPaymentId) {
			$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

			return self::C_RESULT_FAIL;
		}

		// извлечь из формы идентификатор сессии
		$sSessionId = $this->parseSessionId($sCardFormHtml);
		if (!$sSessionId) {
			$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

			return self::C_RESULT_FAIL;
		}

		// поля hidden_lang, hidden_time отправляются пустыми и не обязательны
		$aRequestData = $this->getPaymentRequestData(
			[
				'pan',
				'exp_date_m',
				'exp_date_y',
				'cvv',
				'cardholder',
				'amount',
				'currency',
				'desc',
			],
			$aData
		);

		if (!$aRequestData) {
			$this->setError(self::C_SYSTEM_ERROR_REQUEST_DATA);

			return self::C_RESULT_FAIL;
		}

		// дополнить данные
		$aRequestData['iframe'] = 'false';
		$aRequestData['sessid'] = $sSessionId;

		return $this->getResponseHtml($aRequestData);
	}

	/**
	 * Генерирует хеш
	 *
	 * @param array $aData
	 *
	 * @return string
	 */
	private function getHash(array $aData)
	{
		ksort($aData);

		return sha1($this->sPassCode . implode('', $aData));
	}

	/**
	 * Получить данные для запроса по списку обязательных полей
	 *
	 * @param array $aRequiredFields
	 * @param array $aData
	 *
	 * @return array|null
	 */
	private function getPaymentRequestData(array $aRequiredFields, array $aData)
	{
		Logger::init()->info($aRequiredFields, 'Required request fields');

		$aRequestData = [];

		foreach ($aRequiredFields as $sField) {
			// пустое значение в существующем обязательном поле тоже валидно
			if (!isset($aData[$sField])) {
				Logger::init()->info($sField, 'Field does not exist');

				return null;
			}

			$aRequestData[$sField] = $aData[$sField];
		}

		return $aRequestData;
	}

	/**
	 * Выполнить запрос, обработать ответ и вернуть код ответа
	 *
	 * @param array $aRequestData
	 *
	 * @return int
	 */
	private function getResponse(array $aRequestData)
	{
		$sResponse = $this->request($this->sApiUrl, $aRequestData);

		if (!$sResponse) {
			return self::C_RESULT_FAIL;
		}

		$aResponse = explode(',', $sResponse);

		return $this->doProcessResponse($aResponse);
	}

	/**
	 * Обрабатывает ответ iPSP
	 *
	 * @param array $aResponse
	 *
	 * @return int
	 */
	private function doProcessResponse(array $aResponse)
	{
		// ошибка ответа - не может быть меньше 2 элементов в ответе
		if (count($aResponse) < 2) {
			$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

			return self::C_RESULT_FAIL;
		}

		$this->iPaymentId = $aResponse[0];

		// может отсутствовать в некоторых случаях
		if (isset($aResponse[2])) {
			$this->sAuthIdCode = $aResponse[2];
		}

		// ошибка
		if (strpos($aResponse[1], ':') && explode(':', $aResponse[1])[0] == 'KO') {
			$this->setError(explode(':', $aResponse[1])[1]);

			return self::C_RESULT_FAIL;
		}

		// нормальное завершение
		if ($aResponse[1] == 'OK') {

			return self::C_RESULT_SUCCESS;
		}

		// требуется 3DS
		if ($aResponse[1] == '3DS') {

			if (count($aResponse) != 5) {
				$this->setError(self::C_SYSTEM_ERROR_RESPONSE_3DS_DATA);

				return self::C_RESULT_FAIL;
			}

			$this->s3dsLink = $aResponse[2];
			$this->s3dsPaReq = $aResponse[3];
			$this->s3dsMD = $aResponse[4];

			return self::C_RESULT_SUCCESS_3DS;
		}

		// все остальные случаи не обрабатываются
		$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

		return self::C_RESULT_FAIL;
	}

	/**
	 * Выполнить запрос, обработать ответ и вернуть код ответа при эмуляции работы с формой через браузер
	 *
	 * @param array $aRequestData
	 *
	 * @return int
	 */
	private function getResponseHtml(array $aRequestData)
	{
		$aResponseHtml = $this->request($this->sApiFormUrl, $aRequestData);

		return $this->doProcessResponseHtml($aResponseHtml);
	}

	/**
	 * Обрабатывает ответ iPSP в формате HTML
	 *
	 * @param string $sResponseHtml
	 *
	 * @return int
	 */
	private function doProcessResponseHtml($sResponseHtml)
	{
		// нет ответа
		if (!$sResponseHtml) {
			$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

			return self::C_RESULT_FAIL;
		}

		$iResult = $this->parseResult($sResponseHtml);

		// дополнительные параметры
		$this->parseAuthCode($sResponseHtml);
		$this->parseRc($sResponseHtml);

		// был распознан ответ
		if ($iResult) {
			// вернуть просто ошибку ответа, т.к. конкретная ошибка в тексте страницы не фигурирует
			if ($iResult == self::C_RESULT_FAIL) {
				$this->setError(self::C_SYSTEM_ERROR_RESPONSE);
			}

			return $iResult;
		}

		// если не был распознан ответ, то, возможно, это данные 3DS
		$a3dsData = $this->parse3dsData($sResponseHtml);

		if ($a3dsData) {
			list($this->s3dsLink, $this->s3dsPaReq, $this->s3dsMD) = $a3dsData;

			return self::C_RESULT_SUCCESS_3DS;
		}

		// все остальные случаи не обрабатываются
		$this->setError(self::C_SYSTEM_ERROR_RESPONSE);

		return self::C_RESULT_FAIL;
	}

	/**
	 * Выполняет запрос к iPSP
	 *
	 * @param string $sUrl
	 * @param array  $aData
	 *
	 * @return array
	 */
	private function request($sUrl, array $aData)
	{
		Logger::init()->info($aData, 'IPSP request data');

		if (!empty($this->sCertPath) &&
			(!file_exists($this->sCertPath . $this->sCertFile) || !file_exists($this->sCertPath . $this->sCertKey))
		) {
			$this->setError(self::C_SYSTEM_ERROR_CERT);

			return [];
		}

		$sData = http_build_query($aData);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $sUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::C_CURL_CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::C_CURL_TIMEOUT);
		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5'
		);
		curl_setopt($ch, CURLOPT_POST, 1);

		// если SSL
		if (!empty($this->sCertPath)) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSLVERSION, 3);
			curl_setopt($ch, CURLOPT_SSLCERT, $this->sCertPath . $this->sCertFile);
			curl_setopt($ch, CURLOPT_SSLKEY, $this->sCertPath . $this->sCertKey);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $sData);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);

		$sResponse = curl_exec($ch);
		$sResultHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		Logger::init()->info(curl_error($ch), 'curl error');

		curl_close($ch);

		Logger::init()->info($sResultHttpCode, 'HTTP code');
		Logger::init()->info($sResponse, 'IPSP response');

		if ($sResultHttpCode !== 200 || !$sResponse) {
			$this->setError(self::C_SYSTEM_ERROR_HTTP, sprintf('HTTP code: %d', $sResultHttpCode));

			return [];
		}

		return $sResponse;
	}

	/**
	 * Задать ошибку и текст ошибки
	 *
	 * @param int    $iErrorCode
	 * @param string $sMessage
	 */
	private function setError($iErrorCode, $sMessage = "")
	{
		// неизвестная ошибка
		if (!array_key_exists($iErrorCode, self::$aErrors)) {
			$iErrorCode = self::C_SYSTEM_ERROR_UNKNOWN;
		}
		$this->iErrorCode = $iErrorCode;
		$this->sErrorMessage = (!$sMessage) ? self::$aErrors[$iErrorCode] : $sMessage;

		Logger::init()->info($this->iErrorCode, 'IPSP error code');
		Logger::init()->info($this->sErrorMessage, 'IPSP error message');
	}

	/**
	 * Получить идентификатор платежа из HTML-кода страницы
	 *
	 * @param string $sHtml
	 *
	 * @return int
	 */
	private function parsePaymentId($sHtml)
	{
		$aMatches = [];
		Logger::init()->debug(print_r($sHtml, true), 'Data for parsePaymentId');
		if (is_array($sHtml)) {
			return 0;
		}
		preg_match("|Payment ID</div>.*<span>([0-9]*)</span>.*Amount|s", $sHtml, $aMatches);

		return (isset($aMatches[1])) ? $aMatches[1] : 0;
	}

	/**
	 * Получить идентификатор результата из HTML-кода страницы
	 *
	 * @param string $sHtml
	 *
	 * @return int|null
	 */
	private function parseResult($sHtml)
	{
		$aMatches = [];
		preg_match("|<div class='text_box'>(.*).<br><br>|s", $sHtml, $aMatches);

		if (isset($aMatches[1])) {
			if (strpos($aMatches[1], 'approved')) {
				return self::C_RESULT_SUCCESS;
			} elseif (strpos($aMatches[1], 'declined')) {
				return self::C_RESULT_FAIL;
			}
		}

		return null;
	}

	/**
	 * Найти auth_id_code, если есть
	 *
	 * @param string $sHtml
	 */
	private function parseAuthCode($sHtml)
	{
		$aMatches = [];
		preg_match('|auth_id_code:"([a-zA-Z0-9]+)"|s', $sHtml, $aMatches);

		if (isset($aMatches[1])) {
			$this->sAuthIdCode = $aMatches[1];
		}
	}

	/**
	 * Найти RC, если есть
	 *
	 * @param string $sHtml
	 */
	private function parseRc($sHtml)
	{
		$aMatches = [];
		preg_match('|RC:"([0-9-]+)"|s', $sHtml, $aMatches);

		if (isset($aMatches[1])) {
			$this->iRc = $aMatches[1];
		}
	}

	/**
	 * Получить идентификатор сессии из HTML-кода страницы
	 *
	 * @param string $sHtml
	 *
	 * @return string
	 */
	private function parseSessionId($sHtml)
	{
		$aMatches = [];
		preg_match("|name='sessid' value='(.{36})'/>|", $sHtml, $aMatches);

		return (isset($aMatches[1])) ? $aMatches[1] : '';
	}

	/**
	 * Получить данные для прохождения 3DS из HTML-кода страницы
	 *
	 * @param string $sHtml
	 *
	 * @return array
	 */
	private function parse3dsData($sHtml)
	{
		$aMatches = [];
		preg_match("|action='(.*)' method='POST'>.*name='PaReq' value='(.*)'><input type='hidden' name='TermUrl'.*name='MD' value='(.*)'>|s", $sHtml, $aMatches);

		// не найдены все элементы
		if (count($aMatches) != 4) {
			return [];
		}

		return [
			$aMatches[1], // Url
			$aMatches[2], // PaReq
			$aMatches[3], // MD
		];
	}
}