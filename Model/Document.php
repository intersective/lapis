<?php
/**
 * Document keys encrypted with vault's public key
 */
App::uses('AppModel', 'Model');

class Document extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Document';
}
