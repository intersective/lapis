<?php
class LapisSchema extends CakeSchema {

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
	}

	public $lapis_keys = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'parent_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'parent_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'public_key' => array('type' => 'text', 'null' => false),
		'private_key' => array('type' => 'text', 'null' => true, 'default' => null),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => true),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id')
		),
		'tableParameters' => array()
	);

	public $lapis_documents = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'key_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11),
		'document_model' => array('type' => 'string', 'null' => false, 'default' => null),
		'document_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11),
		'document_key' => array('type' => 'text', 'null' => false),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'lapis_document_user_key_id' => array('unique' => true, 'column' => array('document_model', 'document_id', 'key_id')),
		),
		'tableParameters' => array()
	);

}
