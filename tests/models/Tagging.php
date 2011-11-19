<?php
/**
 * Created by JetBrains PhpStorm.
 * User: alalonde
 * Date: 11/19/11
 * Time: 2:50 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Tagging extends CActiveRecord {
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'article_tag';
	}

	public function relations()
	{
		/*return array(
			'article'=>array(self::HAS_ONE,'Article','article_id'),
            'tag'=>array(self::HAS_ONE,'Tag','tag_id'),
		);*/
	}
}
