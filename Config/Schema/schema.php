<?php
class LapisSchema extends CakeSchema {

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
	}

	public $lapis_keys = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
		'parent_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'public_key' => array('type' => 'text', 'null' => false),
		'private_key' => array('type' => 'text', 'null' => true, 'default' => null),
		'ext_id_type' => array('type' => 'string', 'null' => true, 'default' => null),
		'ext_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => true),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'lapis_keys_ext' => array('unique' => true, 'column' => array('ext_id_type', 'ext_id'))
		),
		'tableParameters' => array()
	);

}
