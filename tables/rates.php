<?php

namespace MSergeev\Packages\Counters\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;

class RatesTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_counters_rates';
	}

	public static function getTableTitle ()
	{
		return 'Расценки по тарифам';
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
			new Entity\DateField('DATE',array(
				'required' => true,
				'title' => 'Дата установки значения'
			)),
			new Entity\FloatField('VALUE',array(
				'required' => true,
				'title' => 'Стоимость единицы'
			))
		);
	}
}