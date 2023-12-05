<?php

use Bitrix\Main\Loader;
// use Bitrix\Sale;
use Bitrix\Main\Data\Cache;

class Helpers
{	
    public static $source1 = 'https://jsonplaceholder.typicode.com/';

	public static function fillIblockPosts(){

		$data = self::getPostsFromApi();
		$fill = self::fillPostsFromData($data);

	}

    public static function getPostsFromApi(){

        $url = self::$source1.'posts';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $json = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($json, true);
    }

    public static function fillPostsFromData(array $data){
        
		if (empty($data))
			return false;

        Loader::includeModule('iblock');

		$iblockID = CIBlockTools::GetIBlockId('iblock_news');
		$i = 0;

		foreach($data as $arItem){

			$arSelect = ["ID", "IBLOCK_ID"];
			$arFilter = ["IBLOCK_ID"=> $iblockID, 'NAME' => $arItem['title'], "ACTIVE"=>"Y"];
			$query = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
			if ($resItem = $query->Fetch()) {
				$res = $resItem;
			} else {
				self::addElement($arItem);
			}
			$i++;
		}

        return true;
    }

	public static function addElement($arData){

		$el = new CIBlockElement;
		$iblockID = CIBlockTools::GetIBlockId('iblock_news');

		$arLoadProductArray = Array(
			"IBLOCK_ID"      => $iblockID,
			"NAME"           => $arData['title'],
			"ACTIVE"         => "Y",
			"PREVIEW_TEXT"   => $arData['body'],
		);

		$userId = self::getUserByPropID($arData['userId']);

		$arLoadProductArray['PROPERTY_VALUES']["USER_ID"] = $userId;

		$ID = $el->Add($arLoadProductArray);

		if ($ID){
			$arLoadProductArray['ID'] = $ID;
		} else {
			$arLoadProductArray = [ 'success' => false, 'error' => $el->LAST_ERROR  ];
		}

		return $arLoadProductArray;
	}

	public static function getUserByPropID($propValUserID){

        $filter = [ "UF_POSTS_USER_ID" => $propValUserID ];

		$rsUsers = CUser::GetList( "id", "desc", $filter );	
		while ($user = $rsUsers->Fetch()) {
			$userRealId = $user['ID'];
		}

		if (!$userRealId){
			$user = new CUser;
			$arFields = Array(
				"NAME"              => "test",
				"EMAIL"             => "test_$propValUserID@mail.ru",
				"LOGIN"             => "test_$propValUserID",
				"ACTIVE"            => "Y",
				"GROUP_ID"          => array(5,7),
				"PASSWORD"          => "bitrix123456",
				"CONFIRM_PASSWORD"  => "bitrix123456",
				"UF_POSTS_USER_ID" => $propValUserID
			);
			$ID = $user->Add($arFields);
			if (intval($ID) > 0)
				$userRealId = $ID;
			else
				echo $user->LAST_ERROR;
		}

		return $userRealId;
	}

	public static function OnBeforeIBlockElementUpdateHandler(&$arFields)
	{
		$iblockID = CIBlockTools::GetIBlockId('clothes');

		if ($arFields['IBLOCK_ID'] == $iblockID){

			$arProductItems = CCatalogProductSet::getAllSetsByProduct($arFields['ID'], CCatalogProductSet::TYPE_GROUP);
			
			if (!empty($arProductItems)){
				
				$fileLog = $_SERVER["DOCUMENT_ROOT"].'/local/log_CCatalogProductSet.txt';
				$log = '';

				$mainProductSetsId = key($arProductItems);
				$arProductItems = reset($arProductItems);
				
				if (!$arProductItems['ITEM_ID']){
					$mainProductId = $arFields['ID'];

					$filedsAdd = [
						'ITEM_ID' => $mainProductId,
						'TYPE' => 2,
						'ITEMS' => []
					];					

					if(CCatalogProductSet::add($filedsAdd))						
						$log .= date('Y-m-d H:i:s') . "Добавлен новый набор для ".$mainProductId.PHP_EOL;
					else
						$log .= date('Y-m-d H:i:s') . "Не удалось добавить набор для ".$mainProductId.PHP_EOL;
				} else {
					$mainProductId = $arProductItems['ITEM_ID'];
				}

				$arProductSetsId = [ $mainProductSetsId => $mainProductId];

				foreach($arProductItems['ITEMS'] as $item){
					$arProductSetsId[$item['ID']] = $item['ITEM_ID'];
				}
				// $log .= date('Y-m-d H:i:s') . " mainProductSetsId- ".$mainProductSetsId.PHP_EOL;

				foreach($arProductSetsId as $setId => $product){
					if ($product == $mainProductId)
						continue;

					$tempAr = $arProductSetsId;
					unset($tempAr[$setId]);

					$fileds = [
						"TYPE" => 2,
						// "SET_ID" => $setId,
						"ACTIVE" => "Y",
						// "QUANTITY" => 1,
						"ITEM_ID" => $product,
						"ITEMS" => []
					];

					foreach($tempAr as $t_setId => $item){
						$fileds['ITEMS'][$t_setId] = [
							'ID' => $t_setId,
							"ACTIVE" => "Y",
							"ITEM_ID" => $item,
							"QUANTITY" => 1
						];
					}
					// $log .= date('Y-m-d H:i:s') . " tempAr ".print_r($fileds, true).PHP_EOL;
					// $log .= date('Y-m-d H:i:s') . "arProductSetsId ".print_r($arProductSetsId, true).PHP_EOL;

					if(CCatalogProductSet::update($setId,$fileds))
						$log .= date('Y-m-d H:i:s') . "Набор изменен для ".$product.PHP_EOL;
					else
						$log .= date('Y-m-d H:i:s') . "Набор не изменен для ".$product.PHP_EOL;
				}
				
				
				file_put_contents($fileLog, $log . PHP_EOL, FILE_APPEND);
			}
	
		}			
			
	}
		
}