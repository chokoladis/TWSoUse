<?

use \Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(null,
	[
		'CIBlockTools' => '/local/classes/CIBlockTools.php',
		'Helpers' => '/local/classes/Helpers.php',
	]
);


AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", ["Helpers", "OnBeforeIBlockElementUpdateHandler"]);
