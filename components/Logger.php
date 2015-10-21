<?php

/**
 * Class Logger
 */
class Logger
{
	/**
	 * Отладочная информация. Для подробного логгирования некоторых событий системы
	 */
	const C_DEBUG = 0;

	/**
	 * Информация. Для обычных событий в системе, требующих логгирования
	 */
	const C_INFO = 10;

	/**
	 * Уведомления. Для обычных событий в системе, требующих логгирования с повышенным приоритетом
	 */
	const C_NOTICE = 20;

	/**
	 * Предупреждения. Например, при пойманных в месте выполнения исключениях
	 */
	const C_WARNING = 30;

	/**
	 * Ошибки. Например, при не пойманных в месте выполнения исключениях
	 */
	const C_ERROR = 40;

	/**
	 * @var array
	 */
	public static $aLogLevels = array(
		self::C_DEBUG   => 'Debug',
		self::C_INFO    => 'Info',
		self::C_NOTICE  => 'Notice',
		self::C_WARNING => 'Warning',
		self::C_ERROR   => 'Error',
	);

	private static $oInstance;

	/**
	 * Удаляемая часть пути из трассировки
	 *
	 * @var string
	 */
	private $sDeletedPath;

	/**
	 * @return Logger
	 */
	public static function init()
	{
		if (self::$oInstance === null) {
			self::$oInstance = new self;
		}

		return self::$oInstance;
	}

	private function __clone()
	{
	}

	function __construct()
	{
		$this->sDeletedPath = substr($_SERVER['DOCUMENT_ROOT'], 0, strrpos($_SERVER['DOCUMENT_ROOT'], '/') + 1);
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
	}

	/**
	 * Отладочные данные
	 *
	 * @param mixed  $mData
	 * @param string $sMessage
	 */
	public function debug($mData, $sMessage = '')
	{

		$this->log($sMessage, $mData, self::C_DEBUG);
	}

	/**
	 * Информация
	 *
	 * @param mixed  $mData
	 * @param string $sMessage
	 */
	public function info($mData, $sMessage = '')
	{

		$this->log($sMessage, $mData, self::C_INFO);
	}

	/**
	 * Приоритетная информация с уведомлением
	 *
	 * @param mixed  $mData
	 * @param string $sMessage
	 */
	public function notice($mData, $sMessage = '')
	{
		$this->log($sMessage, $mData, self::C_NOTICE);
	}

	/**
	 * Предупреждения
	 *
	 * @param mixed  $mData
	 * @param string $sMessage
	 */
	public function warning($mData, $sMessage = '')
	{
		$this->log($sMessage, $mData, self::C_WARNING);
	}

	/**
	 * Ошибки
	 *
	 * @param mixed  $mData
	 * @param string $sMessage
	 */
	public function error($mData, $sMessage = '')
	{
		$this->log($sMessage, $mData, self::C_ERROR);
	}

	/**
	 * Логгирование
	 *
	 * @param string $sMessage
	 * @param mixed  $mData
	 * @param int    $iLogLevel
	 */
	private function log($sMessage, $mData, $iLogLevel)
	{
		if (Config::init()->get('logger_off') || $iLogLevel < Config::init()->get('log_level')) {
			return;
		}

		// трассировка позволяет не передавать множество лишних аргументов при логгировании
		$aBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

		$sClass = isset($aBacktrace[2]['class']) ? $aBacktrace[2]['class'] : 'undefined';
		$sFunction = isset($aBacktrace[2]['function']) ? $aBacktrace[2]['function'] : 'undefined';
		$sLine = isset($aBacktrace[1]['line']) ? $aBacktrace[1]['line'] : 0;
		$sFile = str_replace($this->sDeletedPath, '', $aBacktrace[1]['file']);

		$oStatement = $this->oDb->prepare(
			'INSERT INTO `logs`
			(`level`, `class`, `function`, `file`, `line`, `message`, `data`, `microtime`, `dt_create`)
			VALUES
			(:level, :class, :function, :file, :line, :message, :data, :microtime, :dt_create)'
		);
		$data = $this->transformData($mData);
		$mctime = microtime(true);
		$dt = date('Y-m-d H:i:s', time());
		$oStatement->bindParam(':level', $iLogLevel, PDO::PARAM_INT);
		$oStatement->bindParam(':class', $sClass, PDO::PARAM_STR);
		$oStatement->bindParam(':function', $sFunction, PDO::PARAM_STR);
		$oStatement->bindParam(':file', $sFile, PDO::PARAM_STR);
		$oStatement->bindParam(':line', $sLine, PDO::PARAM_INT);
		$oStatement->bindParam(':message', $sMessage, PDO::PARAM_STR);
		$oStatement->bindParam(':data', $data, PDO::PARAM_STR);
		$oStatement->bindParam(':microtime', $mctime, PDO::PARAM_STR);
		$oStatement->bindParam(':dt_create', $dt, PDO::PARAM_STR);
		$oStatement->execute();
	}

	/**
	 * Преобразует данные для логгирования.
	 * Скрывает пароли, объекты исключений преобразуются в массив
	 *
	 * @param mixed $mData
	 *
	 * @return array
	 */
	private function transformData($mData)
	{
		if ($mData instanceof Exception) {
			$aData['code'] = $mData->getCode();
			$aData['message'] = $mData->getMessage();
			$aData['thrown_in'] = sprintf(
				'%s:%s',
				str_replace($this->sDeletedPath, '', $mData->getFile()),
				$mData->getLine()
			);
			$aData['trace'] = $mData->getTrace();
			$mData = $aData;
		}

		// данные, которые не должны логгироваться
		$aLogOffKeys = [
			'password',
			'pass',
			'passwd',
			'card_number',
			'account_number', // card number
			'pan', // card number
			'cvv',
			'cvc',
			'cvn',
		];
		if (is_array($mData)) {
			array_walk_recursive($mData, function (&$value, $key) use ($aLogOffKeys) {
				if ($key && in_array($key, $aLogOffKeys)) {
					$value = '****';
				}
			});
		}

		return json_encode($mData, JSON_UNESCAPED_UNICODE);
	}
}