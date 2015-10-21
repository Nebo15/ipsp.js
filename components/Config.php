<?php

/**
 * Class Config
 */
class Config
{
	const ENVIRONMENT_LOCAL = 'local';
	const ENVIRONMENT_PRODUCTION = 'production';

	public static $sCurrentEnvironment = self::ENVIRONMENT_PRODUCTION;

	/**
	 * Текущий массив настроек
	 *
	 * @var array
	 */
	private $aCurrentConfig = [];

	/**
	 * @var Config
	 */
	private static $oInstance;

	/**
	 * @return Config
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

	private function __construct()
	{
	}

	/**
	 * Задать текущий конфиг
	 *
	 * @param array $aCurrentConfig
	 */
	public function setCurrentConfig(array $aCurrentConfig)
	{
		$this->aCurrentConfig = $aCurrentConfig;
	}

	/**
	 * Получить значение из текущей конфигурации
	 *
	 * @param string $sKey
	 *
	 * @return mixed
	 */
	public function get($sKey)
	{
		$aParsedKey = $this->parseKey($sKey);

		return $this->searchKeyInConfig($this->aCurrentConfig, $aParsedKey);
	}

	/**
	 * Разобрать значение ключа конфига
	 *
	 * @param string $sKey
	 *
	 * @return array
	 */
	private function parseKey($sKey)
	{
		return explode('.', $sKey);
	}

	/**
	 * Найти значение в конфиге по ключу, если задано
	 *
	 * @param array $aConfig
	 * @param array $aKeys
	 *
	 * @return mixed
	 */
	private function searchKeyInConfig(array $aConfig, array $aKeys)
	{
		foreach ($aKeys as $sKey) {
			if (!isset($aConfig[$sKey])) {
				return null;
			}
			$aConfig = $aConfig[$sKey];
		}

		return $aConfig;
	}
}