<?php
class BookFixture extends CakeTestFixture {
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
		'title' => array('type' => 'string', 'null' => true, 'default' => null),
		'encrypted' => array('type' => 'text', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'index_books_on_title' => array('unique' => false, 'column' => 'title')
		),
		'tableParameters' => array(),
	);
}
