<?php

namespace MSergeev\Packages\Counters\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;

class TariffsTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_counters_tariffs';
	}

	public static function getTableTitle ()
	{
		return 'Тарифы';
	}

	public static function getTableLinks ()
	{
		return array(
			'ID' => array(
				'ms_counters_rates' => 'TARIFF_ID',
				'ms_counters_values_hourly' => 'TARIFF_ID',
				'ms_counters_values_daily' => 'TARIFF_ID',
				'ms_counters_values_monthly' => 'TARIFF_ID',
				'ms_counters_values_yearly' => 'TARIFF_ID'
			)
		);
	}

	public static function getMap ()
	{
		return array(
			new Entity\IntegerField('ID',array(
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID тарифа'
			)),
			new Entity\IntegerField('COUNTER_ID',array(
				'required' => true,
				'link' => 'ms_counters_counters.ID',
				'title' => 'ID счетчика'
			)),
			new Entity\StringField('CODE',array(
				'required' => true,
				'unique' => true,
				'title' => 'Код тарифа'
			)),
			new Entity\FloatField('START_VALUE',array(
				'required' => true,
				'default_value' => 0,
				'title' => 'Стартовое значение показаний счетчика'
			))
		);
	}
}