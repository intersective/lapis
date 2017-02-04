<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'MockApp' . DS . 'models.php';
class EncryptableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.lapis.requester',
		'plugin.lapis.accessor',
		'plugin.lapis.book',
	);

	public function setUp() {
		$this->Book = new Book();
	}

	public function testNothing() {
		$a = $this->Book->find('all');
		$this->assertTrue(true);
	}
}
