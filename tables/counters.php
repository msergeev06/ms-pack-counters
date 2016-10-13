<?php

namespace MSergeev\Packages\Counters\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;

class CountersTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_counters_counters';
	}

	public static function getTableTitle ()
	{
		return 'Счетчики';
	}

	public static function getTableLinks ()
	{
		return array(
			'ID' => array(
				'ms_counters_tariffs' => 'COUNTER_ID'
			)
		);
	}

	public static function getMap ()
	{
		return array(
			new Entity\IntegerField('ID',array(
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID счетчика'
			)),
			new Entity\StringField('CODE',array(
				'required' => true,
				'unique' => true,
				'title' => 'Код счетчика'
			)),
			new Entity\StringField('NAME',array(
				'title' => 'Имя счетчика'
			))
		);
	}

}