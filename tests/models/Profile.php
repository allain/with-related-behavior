<?php
class Profile extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'profile';
	}

	public function rules()
	{
		return array(
			array('email','safe'),
		);
	}
}
