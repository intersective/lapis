<?php
App::uses('AppModel', 'Model');

class Book extends AppModel {
	public $name = 'Book';
	public $useDbConfig = 'test';
	public $actsAs = array('Lapis.SecDoc');

	/**
	 * Types can be either inherit, number, string or boolean
	 */
	public $documentSchema = array(
		'author' => 'string',
		'pages' => 'number',
		'available' => 'boolean'
	);

	// public $documentSchema = array('author', 'pages', 'available');
}
