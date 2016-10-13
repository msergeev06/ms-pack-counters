<?php

namespace MSergeev\Packages\Counters\Lib;

use MSergeev\Core\Entity\Query;
use MSergeev\Packages\Counters\Tables\RatesTable;

class Rates
{
	public static $arRates = array(
		'ID',
		'TARIFF_ID',
		'TARIFF_ID.COUNTER_ID',
		'TARIFF_ID.COUNTER_ID.CODE',
		'TARIFF_ID.COUNTER_ID.NAME',
		'TARIFF_ID.CODE',
		'TARIFF_ID.START_VALUE',
		'DATE',
		'VALUE'
	);

	public static function addNewRate ($tariffID, $value=0, $date=null)
	{
		if (is_null($date))
		{
			$date = date('d.m.Y');
		}

		$query = new Query('insert');
		$query->setInsertParams(
			array(
				'TARIFF_ID' => $tariffID,
				'DATE' => $date,
				'VALUE' => $value
			),
			RatesTable::getTableName(),
			RatesTable::getMapArray()
		);
		$res = $query->exec();
		if ($res->getResult())
		{
			self::updateData ($date);
			return $res->getInsertId();
		}
		else
		{
			return false;
		}
	}

	public static function getLastRate ($tariffID)
	{
		$arRes = RatesTable::getList(array(
			'select' => self::$arRates,
			'filter' => array('TARIFF_ID'=>$tariffID),
			'order' => array('DATE'=>'DESC'),
			'limit' => 1
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

	public static function getRateList ($tariffID=0, $dateTo = null, $dateFrom = null)
	{
		$arFilter = array();
		if ($tariffID>0)
		{
			$arFilter['TARIFF_ID']=$tariffID;
		}
		if (!is_null($dateTo) && is_null($dateFrom))
		{
			if ($mask = maskValue($dateTo))
			{
				$arFilter[$mask['mask'].'DATE'] = $mask['value'];
			}
			else
			{
				$arFilter['DATE'] = $dateTo;
			}
		}
		elseif (!is_null($dateTo) && !is_null($dateFrom))
		{
			if ($mask = maskValue($dateFrom))
			{
				$arFilter[$mask['mask'].'DATE'] = $mask['value'];
			}
			else
			{
				$arFilter['=DATE'] = $dateFrom;
			}
			if ($mask = maskValue($dateTo))
			{
				$arFilter[$mask['mask'].'DATE'] = $mask['value'];
			}
			else
			{
				$arFilter['=DATE'] = $dateTo;
			}
		}
		$arRes = RatesTable::getList(array(
			'select' => self::$arRates,
			'filter' => $arFilter
		));
		if ($arRes)
		{
			return $arRes;
		}
		else
		{
			return false;
		}
	}

	protected static function updateData ($date)
	{
		//После добавления новой цены - проверяем не нужно ли пересчитать значения в базе
	}
}