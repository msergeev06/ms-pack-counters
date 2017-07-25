<?php

namespace MSergeev\Packages\Counters\Lib;

use MSergeev\Core\Entity\Query;
use MSergeev\Core\Lib\DateHelper;
use MSergeev\Core\Lib\SqlHelper;
use MSergeev\Core\Lib\Tools;
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

		//Проверяем правильность указания float значения
		$value = Tools::validateFloatVal($value);

		//Получаем расход за час
		//$newValue = $value - $sumValue - $nowRate['TARIFF_ID_START_VALUE'];
		if ($value < 0) $value = 0;

		//Добавляем часовые значения
		$arInsert = array(
			'TARIFF_ID' => $tariffID,
			'HOUR' => $hour,
			'DAY' => $day,
			'MONTH' => $month,
			'YEAR' => $year,
			'DATE' => $day.'.'.$hour.'.'.$year,
			'VALUE' => $value
		);
		//Проверяем, есть ли данные значения в базе
		$arRes = ValuesHourlyTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'TARIFF_ID' => $arInsert['TARIFF_ID'],
				'HOUR' => $arInsert['HOUR'],
				'DAY' => $arInsert['DAY'],
				'MONTH' => $arInsert['MONTH'],
				'YEAR' => $arInsert['YEAR'],
				'DATE' => $arInsert['DATE']
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

	public static function getCurrentCosts ($tariffCode)
	{
		$tariffID = Tariffs::getTariffIDbyCODE($tariffCode);

		return self::getCurrentValuesByTariffID($tariffID, 'SUM_COST');
	}

	private static function getCurrentValuesByTariffID ($tariffID)
	{
		$arRes = ValuesHourlyTable::getList(array(
			'select' => array('VALUE'),
			'filter' => array('TARIFF_ID'=>$tariffID),
			'limit' => 1
		));
		if ($arRes && isset($arRes[0]))
		{
			$arRes = $arRes[0];
		}
		if (isset($arRes['VALUE']))
		{
			return $arRes['VALUE'];
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
		$sqlHelper = new SqlHelper(ValuesHourlyTable::getTableName());
		//$valuesHourlyTableName = ValuesHourlyTable::getTableName();
		$sql = "SELECT\n\t"
			.$sqlHelper->getMaxFunction("VALUE","VALUE");
		if ($type=='hourly')
		{
			$sql .= " ,\n\t".$sqlHelper->wrapFieldQuotes("HOUR");
		}
		if ($type=='hourly' || $type=='daily')
		{
			$sql .= " ,\n\t".$sqlHelper->wrapFieldQuotes("DAY");
		}
		if ($type=='hourly' || $type=='daily' || $type=='monthly')
		{
			$sql .= " ,\n\t".$sqlHelper->wrapFieldQuotes("MONTH");
		}
		$sql .= " ,\n\t".$sqlHelper->wrapFieldQuotes("YEAR");
		$sql .= " FROM\n\t"
			.$sqlHelper->wrapTableQuotes()
			."\nWHERE\n\t"
			.$sqlHelper->wrapFieldQuotes("TARIFF_ID")." = ".intval($tariffID);

		if ($type=='hourly')
		{
			$sql .= " AND\n\t"
				.$sqlHelper->wrapFieldQuotes("DAY")." = ".intval($arDate[0]);
		}
		if ($type=='hourly' || $type=='daily')
		{
			$sql .= " AND\n\t"
				.$sqlHelper->wrapFieldQuotes("MONTH")." =".intval($arDate[1]);
		}
		if ($type=='hourly' || $type=='daily' || $type=='monthly')
		{
			$sql .= " AND\n\t"
				.$sqlHelper->wrapFieldQuotes("YEAR")." =".intval($arDate[2]);
		}
		if ($type=='hourly')
		{
			$sql .= "\nGROUP BY\n\t".$sqlHelper->wrapFieldQuotes("HOUR")." ";
		}
		elseif ($type=='daily')
		{
			$sql .= "\nGROUP BY\n\t".$sqlHelper->wrapFieldQuotes("DAY")." ";
		}
		elseif ($type=='monthly')
		{
			$sql .= "\nGROUP BY\n\t".$sqlHelper->wrapFieldQuotes("MONTH")." ";
		}
		elseif ($type=='yearly')
		{
			$sql .= "\nGROUP BY\n\t".$sqlHelper->wrapFieldQuotes("YEAR")." ";
		}
		msEchoVar($sql);

		$query->setQueryBuildParts($sql);
		$res = $query->exec();
		if ($res->getResult())
		{
			$arResult = array();
			$i=0;
			while($ar_res = $res->fetch())
			{

				$arResult[$i] = array(
					'VALUE' => round($ar_res['VALUE'],2),
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
			msDebug($arResult);

			return $arResult;
		}
		else
		{
			return false;
		}
	}
}