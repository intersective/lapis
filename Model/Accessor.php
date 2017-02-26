<?php
App::uses('AppModel', 'Model');
App::uses('Lapis', 'Lapis.Lib');

/**
 * Vault accessors
 *
 * `key` field in this table is to be combined with the `vault_private_key` field from Lapis.Requester
 */
class Accessor extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Accessor';
}

