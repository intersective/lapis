<?php
class LapisSchema extends CakeSchema {

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
	}

	public $lapis_requesters = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary'),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime'),
		'parent_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'ident_public_key' => array('type' => 'text', 'null' => false),
		'ident_private_key' => array('type' => 'text', 'null' => true, 'default' => null),
		'vault_public_key' => array('type' => 'text', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id')
		),
		'tableParameters' => array()
	);

	public $lapis_vaults = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary'),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime'),
		'owner_requester_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'accessor_requester_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'vault_private_key' => array('type' => 'text', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'lapis_vaults_owner_requester_id' => array('column' => 'owner_requester_id'),
			'lapis_vaults_accessor_requester_id' => array('column' => 'accessor_requester_id')
		),
		'tableParameters' => array()
	);

	public $lapis_objects = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary'),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime'),
		'owner_requester_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'key' => array('type' => 'text', 'null' => false),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'lapis_objects_owner_requester_id' => array('column' => 'owner_requester_id')
		),
		'tableParameters' => array()
	);

}
