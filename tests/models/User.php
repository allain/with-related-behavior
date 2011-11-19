<?php
class User extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'user';
	}

	public function relations()
	{
		return array(
			'group'=>array(self::BELONGS_TO,'Group','group_id'),
            'profile'=>array(self::HAS_ONE,'Profile','id'),
		);
	}

	public function rules()
	{
		return array(
			array('name','required'),
		);
	}

    public function behaviors()
	{
		return array(
			'withRelated'=>'ext.WithRelatedBehavior',
		);
	}
}