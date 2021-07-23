//события при добовление, обнавлении, удаление элементов в инфоблоке залы (halls)

AddEventHandler("iblock", "OnAfterIBlockElementAdd", Array("HallsCatalogs", "OnAfterIBlockElementAdd"));// добавления
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("HallsCatalogs", "OnBeforeIBlockElementUpdate"));// обнавление
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", Array("HallsCatalogs", "OnBeforeIBlockElementDelete"));// удаление
class HallsCatalogs
{	
	private function GetIblockCode($iblockCode,$flag = false)
	{		
		if($flag === true)
		{
			return CIBlockElement::GetByID($iblockCode)->GetNext()["IBLOCK_CODE"];			
		}
		else
		{
			return CIBlock::GetByID($iblockCode)->GetNext()["CODE"];
		}
			
	}	
	// "OnAfterIBlockElementAdd"
	public function OnAfterIBlockElementAdd(&$arFields)
	{
		if (CModule::IncludeModule('iblock'))
		{
			switch (self::GetIblockCode($arFields["IBLOCK_ID"])) 
			{
				case IBLOCK_CODE_HALLS:
					self::OnAfterAddHandler($arFields);					
				break;				
			}			
		}	
   }
	// "OnBeforeIBlockElementUpdate"
	public function OnBeforeIBlockElementUpdate(&$arFields)
	{		
		if (CModule::IncludeModule('iblock'))
		{
			switch (self::GetIblockCode($arFields["IBLOCK_ID"])) 
			{
				case IBLOCK_CODE_HALLS:
					self::OnBeforeUpdateHandler($arFields);					
				break;
			}			
		}
	}
	// "OnBeforeIBlockElementDelete"
	public function OnBeforeIBlockElementDelete($arFields)
	{		
		if (CModule::IncludeModule('iblock')) 
		{    
			switch (self::GetIblockCode($arFields,true)) 
			{
				case IBLOCK_CODE_HALLS:
					self::OnBeforeDeleteHandler($arFields);					
				break;
			}                          
      }
	}
	//==================

	//handler add
	public function OnAfterAddHandler(&$arFields)
	{			
		$dbEl = CIBlockElement::GetList(array(), array("IBLOCK_ID"=>$arFields["IBLOCK_ID"]));
		if ($obEl = $dbEl->GetNextElement()) 
		{
			$props = $obEl->GetProperties();
		}
		$elementId= '';
		foreach ($props as $key => $val) 
		{
			foreach ($arFields["PROPERTY_VALUES"] as $id => $arProp) 
			{
				if ($val["ID"] == $id) 
				{
					if ($key == "DEALER") 
					{
						$elementId = $arProp["n0"]["VALUE"];
						break;
					}
				}
			}
		}
		$PROPERTY_CODE = "BINDING_HALLS"; 			 						
		$res = CIBlockElement::GetList(
			array(), 
			["IBLOCK_CODE"=>IBLOCK_CODE_CATALOG, "=ID"=>$elementId], 
			false, 
			false, 
			["ID", "IBLOCK_ID","PROPERTY_".$PROPERTY_CODE]
		);
		while($ob = $res->GetNextElement())
		{				
			$IBLOCK_ID = $ob->GetFields()["IBLOCK_ID"];				
			$arHalls = $ob->GetProperties()[$PROPERTY_CODE]["VALUE"];				
		}
		$arHalls[] = $arFields["ID"]; 
		CIBlockElement::SetPropertyValues($elementId, $IBLOCK_ID, $arHalls, $PROPERTY_CODE);		
	}
   // handler update
	function OnBeforeUpdateHandler(&$arFields)
	{						
		$props = [];
		$dbEl = CIBlockElement::GetList(array(), array("IBLOCK_ID"=>$arFields["IBLOCK_ID"],"ID" =>$arFields["ID"] ));
		if ($obEl = $dbEl->GetNextElement()) 
		{
			$props = $obEl->GetProperties();					
		}
		$elementId= '';
		foreach ($props as $key => $val) 
		{
			foreach ($arFields["PROPERTY_VALUES"] as $id => $arProp) 
			{
				if ($val["ID"] == $id) 
				{
					if ($key == "DEALER") 
					{							
						foreach($arProp as $el_id)
						{
							$elementId = $el_id["VALUE"];								
						}
						break;																
					}
				}
			}
		}
		$PROPERTY_CODE = "BINDING_HALLS";
		if($elementId != $props["DEALER"]["VALUE"])
		{		
			// create new element				
			$res = CIBlockElement::GetList(
				array(),
				["IBLOCK_CODE"=>IBLOCK_CODE_CATALOG, "=ID"=>$elementId],
				false,
				false,
				["ID", "IBLOCK_ID","PROPERTY_".$PROPERTY_CODE]
			);
			while ($ob = $res->GetNextElement()) 
			{
				$IBLOCK_ID = $ob->GetFields()["IBLOCK_ID"];
				$arHalls = $ob->GetProperties()[$PROPERTY_CODE]["VALUE"];
			}
			$arHalls[] = $arFields["ID"];
			CIBlockElement::SetPropertyValues($elementId, $IBLOCK_ID, $arHalls, $PROPERTY_CODE);
			unset($res,$ob,$arHalls,$IBLOCK_ID);

			// delete old element
			$res = CIBlockElement::GetList(
				array(),
				["IBLOCK_CODE"=>IBLOCK_CODE_CATALOG, "=ID"=>$props["DEALER"]["VALUE"]],
				false,
				false,
				["ID", "IBLOCK_ID","PROPERTY_".$PROPERTY_CODE]
			);
			while ($ob = $res->GetNextElement()) 
			{
				$IBLOCK_ID = $ob->GetFields()["IBLOCK_ID"];
				$arHalls = $ob->GetProperties()[$PROPERTY_CODE]["VALUE"];
			}
			foreach ($arHalls as $k => $v) 
			{
				if ($v != $arFields["ID"]) 
				{
					$arTemp[] = $v;
				}
			}
			$arHalls=$arTemp;				
			CIBlockElement::SetPropertyValues($props["DEALER"]["VALUE"], $IBLOCK_ID, $arHalls, $PROPERTY_CODE);
		}
		else
		{
			$res = CIBlockElement::GetList(
				array(),
				["IBLOCK_CODE"=>IBLOCK_CODE_CATALOG, "=ID"=>$props["DEALER"]["VALUE"]],
				false,
				false,
				["ID", "IBLOCK_ID","PROPERTY_".$PROPERTY_CODE]
			);
			while ($ob = $res->GetNextElement()) 
			{
				$IBLOCK_ID = $ob->GetFields()["IBLOCK_ID"];
				$arHalls = $ob->GetProperties()[$PROPERTY_CODE]["VALUE"];
			}
			if(!in_array($arFields["ID"],$arHalls))
			{
				$arHalls[] = $arFields["ID"];
				CIBlockElement::SetPropertyValues($props["DEALER"]["VALUE"], $IBLOCK_ID, $arHalls, $PROPERTY_CODE);
			} 							
		}						
	}         
   // handler delete
	function OnBeforeDeleteHandler($arFields)
	{     
		$PROPERTY_CODE = "BINDING_HALLS";
		// get the element ID in the "services and performers" infoblock from the "DEALER" property
		$res = CIBlockElement::GetList(
			array(),
			["=ID"=>$arFields],
			false,
			false,
			["ID", "IBLOCK_ID","PROPERTY_DEALER"]
		);
		while ($ob = $res->GetNextElement()) 
		{
			$dealerId = $ob->GetProperties()["DEALER"]["VALUE"];
		}			
		// create an array of IDs of the bound halls	
		$res = CIBlockElement::GetList(
			array(),
			["IBLOCK_CODE"=>IBLOCK_CODE_CATALOG, "=ID"=>$dealerId],
			false,
			false,
			["ID", "IBLOCK_ID","PROPERTY_".$PROPERTY_CODE]
		);
		while ($ob = $res->GetNextElement()) 
		{
			$IBLOCK_ID = $ob->GetFields()["IBLOCK_ID"];
			$arHalls = $ob->GetProperties()[$PROPERTY_CODE]["VALUE"];
		}
		// filter hall IDs based on the remote
		foreach ($arHalls as $k => $v) 
		{
			if ($v != $arFields) 
			{
				$arTemp[] = $v;
			}
		}
		$arHalls=$arTemp;
		// I save a new filtered array of hall IDs in the information block "services and performers"
		CIBlockElement::SetPropertyValues($dealerId, $IBLOCK_ID, $arHalls, $PROPERTY_CODE);
	}
}
