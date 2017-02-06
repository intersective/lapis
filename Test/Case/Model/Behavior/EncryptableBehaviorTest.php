<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'MockApp' . DS . 'models.php';
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'Fixture' . DS . 'utils.php');
class EncryptableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.lapis.accessor',
		'plugin.lapis.document',
		'plugin.lapis.requester',
		'plugin.lapis.book',
	);

	public function setUp() {
		$this->Book = new Book();
		$this->Requester = ClassRegistry::init('Lapis.Requester');
	}


	public function testAbleToSaveAndRetrieve() {
		$family = FixtureUtils::initFamily($this->Requester, 3);

		$data = array(
			'title' => 'Test book',
			'author' => 'John Doe',
			'pages' => 2462,
			'available' => true,
		);

		$this->Book->saveFor = $family[2]['id'];
		$result = $this->Book->save($data);

		$this->assertNotEquals(false, $result);
		$this->assertEquals(1, $result['Book']['id']);
		$this->assertEquals('Test book', $result['Book']['title']);

		$this->assertTrue(array_key_exists('title', $result['Book'])); // non-encrypted
		$this->assertFalse(array_key_exists('author', $result['Book']));
		$this->assertFalse(array_key_exists('pages', $result['Book']));
		$this->assertFalse(array_key_exists('available', $result['Book']));
		$this->assertTrue(array_key_exists('encrypted', $result['Book']));
	}

	public function testSaveEncryptedWithoutSaveFor() {
	}

	public function testAbleToQuery() {
	}

	public function testAbleToUpdate() {
	}

	public function testAbleToDelete() {
	}

}
