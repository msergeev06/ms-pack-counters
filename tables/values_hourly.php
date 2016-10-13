<?php

namespace MSergeev\Packages\Counters\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;

class ValuesHourlyTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_counters_values_hourly';
	}

	public static function getTableTitle ()
	{
		return 'Ежечасные значения счетчиков по тарифам';
	}

	public static function getMap ()
	{
		return array(
			new Entity\IntegerField('ID',array(
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID записи'
			)),
			new Entity\IntegerField('TARIFF_ID',array(
				'required' => true,
				'link' => 'ms_counters_tariffs.ID',
				'title' => 'ID тарифа'
			)),
			new Entity\IntegerField('HOUR',array(
				'required' => true,
				'title' => 'Час снятия показаний'
			)),
			new Entity\IntegerField('DAY',array(
				'required' => true,
				'title' => 'День получения показаний'
			)),
			new Entity\IntegerField('MONTH',array(
				'required' => true,
				'title' => 'Месяц получения показаний'
			)),
			new Entity\IntegerField('YEAR',array(
				'required' => true,
				'title' => 'Год получения показаний'
			)),
			new Entity\FloatField('VALUE',array(
				'required' => true,
				'title' => 'Сколько насчитали за час'
			)),
			new Entity\FloatField('COST',array(
				'required' => true,
				'title' => 'Стоимость за час'
			))
		);
	}
}