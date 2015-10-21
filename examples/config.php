<?php

return [

    // отключение сервиса
	'service_off'        => false,

    // отключение логгера
	'logger_off'         => false,

    // уровень логгирования: от 0 (все и DEBUG) до 40 (только ERROR)
	'log_level'          => 10,

    // адрес сервиса
	'bridge_url'         => '',

    // время жизни токена
    'token_lifetime'     => 900,

    // минимальное количество дней между периодическими платежами по умолчанию
	'recur_freq_default' => 7,

    // настройки IPSP
	'ipsp'               => [
		'api_url'      => '',
		'api_form_url' => '',
		'product_id'   => '',
		'pass_code'    => '',
	],

    // настройки базы данных
	'mysql'              => [
		'host'     => '',
		'database' => '',
		'user'     => '',
		'password' => '',
	],
];
