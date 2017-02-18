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

		// Without valid requester
		$book = $this->Book->find('first', array(
			'conditions' => array('Book.id' => 1)
		));
		$this->assertEquals(1, $book['Book']['id']);
		$this->assertEquals('Test book', $book['Book']['title']);
		$this->assertFalse(array_key_exists('author', $result['Book']));

		// With valid requester, but wrong password
		$this->Book->requestAs = array('id' => $family[1]['id'], 'password' => 'INVALID_PASSWORD');
		$book = $this->Book->find('first', array(
			'conditions' => array('Book.id' => 1)
		));
		$this->assertEquals(1, $book['Book']['id']);
		$this->assertEquals('Test book', $book['Book']['title']);
		$this->assertFalse(array_key_exists('author', $result['Book']));

		// Valid requesters
		foreach ($family as $member) {
			$this->Book->requestAs = array('id' => $member['id'], 'password' => $member['password']);
			$book = $this->Book->find('first', array(
				'conditions' => array('Book.id' => 1)
			));

			$this->assertEquals(1, $book['Book']['id']);
			$this->assertEquals('Test book', $book['Book']['title']);
			$this->assertTrue(array_key_exists('author', $book['Book']));
			$this->assertEquals('John Doe', $book['Book']['author']);
			$this->assertEquals(2462, $book['Book']['pages']);
			$this->assertEquals(true, $book['Book']['available']);
		}
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
