<?php

namespace MSergeev\Packages\Counters\Lib;

use MSergeev\Core\Entity\Query;
use MSergeev\Core\Lib\DateHelper;
use MSergeev\Core\Lib\SqlHelper;
use MSergeev\Packages\Counters\Tables\ValuesHourlyTable;

class Values
{
	public static function addValue ($tariffCode, $value, $arDateTime=null)
	{
		return self::addValueByTariffCode($tariffCode,$value, $arDateTime);
	}

	protected static function addValueByTariffCode ($tariffCode, $value, $arDateTime=null)
	{
		if ($tariffID = Tariffs::getTariffIDbyCODE($tariffCode))
		{
			return self::addValueByTariffID($tariffID,$value, $arDateTime);
		}
		else
		{
			return false;
		}
	}

	public static function addValueByTariffID ($tariffID, $value, $arDateTime=null)
	{
		if (is_null($arDateTime))
		{
			$day = intval(date('d'));
			$month = intval(date('m'));
			$year = intval(date('Y'));
			$hour = date('G');
		}
		else
		{
			if (isset($arDateTime['DATE']))
			{
				list($day,$month,$year) = explode('.',$arDateTime['DATE']);
			}
			else
			{
				if (isset($arDateTime['DAY']))
				{
					$day = intval($arDateTime['DAY']);
				}
				else
				{
					$day = intval(date('d'));
				}
				if (isset($arDateTime['MONTH']))
				{
					$month = intval($arDateTime['MONTH']);
				}
				else
				{
					$month = intval(date('m'));
				}
				if (isset($arDateTime['YEAR']))
				{
					$year = intval($arDateTime['YEAR']);
				}
				else
				{
					$year = intval(date('Y'));
				}
			}

			if (isset($arDateTime['HOUR']))
			{
				$hour = intval($arDateTime['HOUR']);
			}
			else
			{
				$hour = intval(date('G'));
			}
		}
		$valuesHourlyTableName = ValuesHourlyTable::getTableName();

		//Получаем предыдущее показание счетчика по данному тарифу, сложив все показания за часы
		$sumValue = self::getCurrentValuesByTariffID($tariffID);

		//Получаем текущую тарифную ставку
		$nowRate = Rates::getLastRate($tariffID);

		//Получаем расход за час
		$newValue = $value - $sumValue - $nowRate['TARIFF_ID_START_VALUE'];
		if ($newValue<0) $newValue = 0;

		//Добавляем часовые значения
		$arInsert = array(
			'TARIFF_ID' => $tariffID,
			'HOUR' => $hour,
			'DAY' => $day,
			'MONTH' => $month,
			'YEAR' => $year,
			'VALUE' => $newValue,
			'COST' => ($newValue * $nowRate['VALUE'])
		);
		//Проверяем, есть ли данные значения в базе
		$arRes = ValuesHourlyTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'TARIFF_ID' => $arInsert['TARIFF_ID'],
				'HOUR' => $arInsert['HOUR'],
				'DAY' => $arInsert['DAY'],
				'MONTH' => $arInsert['MONTH'],
				'YEAR' => $arInsert['YEAR']
			)
		));

		if ($arRes)
		{
			//Update
			$query = new Query('update');
			$query->setUpdateParams(
				$arInsert,
				$arRes[0]['ID'],
				$valuesHourlyTableName,
				ValuesHourlyTable::getMapArray()
			);
			$res = $query->exec();
			if ($res->getResult())
			{
				return $res->getAffectedRows();
			}
			else
			{
				return false;
			}
		}
		else
		{
			//Insert
			$query = new Query('insert');
			$query->setInsertParams(
				$arInsert,
				$valuesHourlyTableName,
				ValuesHourlyTable::getMapArray()
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

	public static function getCurrentValues ($tariffCode)
	{
		$tariffID = Tariffs::getTariffIDbyCODE($tariffCode);

		return self::getCurrentValuesByTariffID($tariffID);
	}

	private static function getCurrentValuesByTariffID ($tariffID)
	{
		$sqlHelper = new SqlHelper();

		$valuesHourlyTableName = ValuesHourlyTable::getTableName();
		$query = new Query('select');
		$sql = "SELECT ".$sqlHelper->getSumFunction("VALUE")." FROM "
			.$sqlHelper->wrapQuotes($valuesHourlyTableName)." WHERE "
			.$sqlHelper->wrapQuotes($valuesHourlyTableName)."."
			.$sqlHelper->wrapQuotes('TARIFF_ID')." = ".$tariffID;
		$query->setQueryBuildParts($sql);
		$res = $query->exec();
		$arRes = $res->fetch();
		if (isset($arRes['SUM_VALUE']))
		{
			return $arRes['SUM_VALUE'];
		}
		else
		{
			return 0;
		}
	}

	public static function getHourlyValues ($tariffCode, $date=null)
	{
		$tariffID = Tariffs::getTariffIDbyCODE($tariffCode);

		return self::getValues($tariffID,$date,'hourly');
	}

	public static function getDailyValues ($tariffCode, $date=null)
	{
		$tariffID = Tariffs::getTariffIDbyCODE($tariffCode);

		return self::getValues($tariffID,$date,'daily');
	}

	public static function getMonthlyValues ($tariffCode, $date=null)
	{
		$tariffID = Tariffs::getTariffIDbyCODE($tariffCode);

		return self::getValues($tariffID,$date,'monthly');
	}

	public static function getYearlyValues ($tariffCode, $date=null)
	{
		$tariffID = Tariffs::getTariffIDbyCODE($tariffCode);

		return self::getValues($tariffID,$date,'yearly');
	}

	private static function getValues ($tariffID, $date=null, $type='hourly')
	{
		if (is_null($date))
		{
			$arDate = explode('.',date('d.m.Y'));
		}
		else
		{
			if ($date = DateHelper::validateDate($date))
			{
				$arDate = explode('.',$date);
			}
			else
			{
				$arDate = explode('.',date('d.m.Y'));
			}
		}

		$query = new Query('select');
		$sqlHelper = new SqlHelper();
		$valuesHourlyTableName = ValuesHourlyTable::getTableName();
		$sql = "SELECT ".$sqlHelper->getSumFunction("VALUE")." , ".$sqlHelper->getSumFunction("COST");
		if ($type=='hourly')
		{
			$sql .= " , ".$sqlHelper->wrapQuotes("HOUR");
		}
		if ($type=='hourly' || $type=='daily')
		{
			$sql .= " , ".$sqlHelper->wrapQuotes("DAY");
		}
		if ($type=='hourly' || $type=='daily' || $type=='monthly')
		{
			$sql .= " , ".$sqlHelper->wrapQuotes("MONTH");
		}
		$sql .= " , ".$sqlHelper->wrapQuotes("YEAR");
		$sql .= " FROM ".$sqlHelper->wrapQuotes($valuesHourlyTableName)." WHERE "
			.$sqlHelper->wrapQuotes($valuesHourlyTableName)."."
			.$sqlHelper->wrapQuotes("TARIFF_ID")." =".intval($tariffID);

		if ($type=='hourly')
		{
			$sql .= " AND ".$sqlHelper->wrapQuotes($valuesHourlyTableName)."."
				.$sqlHelper->wrapQuotes("DAY")." =".intval($arDate[0]);
		}
		if ($type=='hourly' || $type=='daily')
		{
			$sql .= " AND ".$sqlHelper->wrapQuotes($valuesHourlyTableName)."."
				.$sqlHelper->wrapQuotes("MONTH")." =".intval($arDate[1]);
		}
		if ($type=='hourly' || $type=='daily' || $type=='monthly')
		{
			$sql .= " AND ".$sqlHelper->wrapQuotes($valuesHourlyTableName)."."
				.$sqlHelper->wrapQuotes("YEAR")." =".intval($arDate[2]);
		}
		if ($type=='hourly')
		{
			$sql .= " GROUP BY ".$sqlHelper->wrapQuotes("HOUR")." ";
		}
		elseif ($type=='daily')
		{
			$sql .= " GROUP BY ".$sqlHelper->wrapQuotes("DAY")." ";
		}
		elseif ($type=='monthly')
		{
			$sql .= " GROUP BY ".$sqlHelper->wrapQuotes("MONTH")." ";
		}
		elseif ($type=='yearly')
		{
			$sql .= " GROUP BY ".$sqlHelper->wrapQuotes("YEAR")." ";
		}

		$query->setQueryBuildParts($sql);
		$res = $query->exec();
		if ($res->getResult())
		{
			$arResult = array();
			$i=0;
			while($ar_res = $res->fetch())
			{
				$arResult[$i] = array(
					'SUM_VALUE' => round($ar_res['SUM_VALUE'],2),
					'SUM_COST' => round($ar_res['SUM_COST'],2),
					'YEAR' => intval($ar_res['YEAR'])
				);
				if (isset($ar_res['HOUR']))
				{
					$arResult[$i]['HOUR'] = intval($ar_res['HOUR']);
				}
				if (isset($ar_res['DAY']))
				{
					$arResult[$i]['DAY'] = intval($ar_res['DAY']);
				}
				if (isset($ar_res['MONTH']))
				{
					$arResult[$i]['MONTH'] = intval($ar_res['MONTH']);
				}
				$i++;
			}

			return $arResult;
		}
		else
		{
			return false;
		}
	}
}