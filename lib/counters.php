<?php

namespace MSergeev\Packages\Counters\Lib;

use MSergeev\Core\Entity\Query;
use MSergeev\Packages\Counters\Tables\CountersTable;

class Counters
{
	public static $arCounters = array(
		'ID',
		'CODE',
		'NAME'
	);


	public static function addCounter ($code, $name='')
	{
		$arRes = CountersTable::getList(array(
			'select' => array('ID'),
			'filter' => array('CODE'=>$code)
		));
		if ($arRes)
		{
			//Если счетчик с таким кодом уже есть, не добавляем ничего
			return false;
		}
		else
		{
			//Если счетчика с таким кодом еще нет, добавляем новый
			$query = new Query('insert');
			$query->setInsertParams(
				array('CODE'=>$code,'NAME'=>$name),
				CountersTable::getTableName(),
				CountersTable::getMapArray()
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

	public static function getCounterIDbyCODE ($code)
	{
		$arRes = CountersTable::getList(array(
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

	public static function getCounterInfoById ($counterID)
	{
		$arRes = CountersTable::getList(array(
			'select' => self::$arCounters,
			'filter' => array(
				'ID' => $counterID
			)
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

	public static function getCounterInfoByCode ($counterCode)
	{
		$arRes = CountersTable::getList(array(
			'select' => self::$arCounters,
			'filter' => array(
				'CODE' => $counterCode
			)
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

	public static function getCounterAllInfo ($counterCode)
	{
		$arResult = self::getCounterInfoByCode($counterCode);
		$arResult['TARIFFS'] = Tariffs::getTariffList($arResult['ID']);

		return $arResult;
	}
}