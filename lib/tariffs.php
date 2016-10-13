<?php

namespace MSergeev\Packages\Counters\Lib;

use MSergeev\Core\Entity\Query;
use MSergeev\Packages\Counters\Tables\TariffsTable;

class Tariffs
{
	public static $arTariffs = array(
		'ID',
		'COUNTER_ID',
		'COUNTER_ID.CODE',
		'COUNTER_ID.NAME',
		'CODE',
		'START_VALUE'
	);


	public static function addTariff ($code, $counterID, $startValue=0)
	{
		$arRes = TariffsTable::getList(array(
			'select' => array('ID'),
			'filter' => array('CODE'=>$code)
		));
		if ($arRes)
		{
			//Если тариф с таким кодом уже существует, ничего не добаляем
			return false;
		}
		else
		{
			//Если не существует, добавляем
			$query = new Query('insert');
			$query->setInsertParams(
				array(
					'CODE' => $code,
					'COUNTER_ID' => $counterID,
					'START_VALUE' => $startValue
				),
				TariffsTable::getTableName(),
				TariffsTable::getMapArray()
			);
			$res = $query->exec();
			if ($res->getResult())
			{
				return $res->getInsertId();
			}
			else
			{
				return false;
			}
		}
	}

	public static function getTariffIDbyCODE ($code)
	{
		$arRes = TariffsTable::getList(array(
			'select' => array('ID'),
			'filter' => array('CODE'=>$code)
		));
		if ($arRes)
		{
			return $arRes[0]['ID'];
		}
		else
		{
			return false;
		}
	}

	public static function getTariffInfoById ($tariffID)
	{
		$arRes = TariffsTable::getList(array(
			'select' => self::$arTariffs,
			'filter' => array('ID'=>$tariffID)
		));
		if ($arRes)
		{
			return $arRes[0];
		}
		else
		{
			return false;
		}
	}

	public static function getTariffInfoByCode ($tariffCode)
	{
		$arRes = TariffsTable::getList(array(
			'select' => self::$arTariffs,
			'filter' => array('CODE'=>$tariffCode)
		));
		if ($arRes)
		{
			return $arRes[0];
		}
		else
		{
			return false;
		}
	}

	public static function getTariffList ($counterID=null)
	{
		$arList = array();
		if (!is_null($counterID))
		{
			$arList['filter'] = array('COUNTER_ID'=>$counterID);
		}
		$arRes = TariffsTable::getList($arList);
		if ($arRes)
		{
			for ($i=0; $i<count($arRes); $i++)
			{
				$arRes[$i]['VALUE'] = round(Values::getCurrentValues($arRes[$i]['CODE']),2);
				$arRes[$i]['REAL_VALUE'] = round(($arRes[$i]['VALUE'] - $arRes[$i]['START_VALUE']),2);
			}
			return $arRes;
		}
		else
		{
			return false;
		}
	}
}