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
}