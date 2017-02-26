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
		'vault_private_key' => array('type' => 'text', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id')
		),
		'tableParameters' => array()
	);

	public $lapis_accessors = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary'),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime'),
		'vault_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'requester_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'key' => array('type' => 'text', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'lapis_vaults_vault_id' => array('column' => 'vault_id'),
			'lapis_vaults_requester_id' => array('column' => 'requester_id')
		),
		'tableParameters' => array()
	);

	public $lapis_documents = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary'),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime'),
		'model_id' => array('type' => 'string', 'null' => false, 'length' => 40),
		'vault_id' => array('type' => 'string', 'length' => 36, 'null' => true, 'default' => null),
		'key' => array('type' => 'text', 'null' => false),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'lapis_documents_model_id' => array('column' => 'model_id'),
			'lapis_documents_vault_id' => array('column' => 'vault_id')
		),
		'tableParameters' => array()
	);

}
