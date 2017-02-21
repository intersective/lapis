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
		$altFamily = FixtureUtils::initFamily($this->Requester, 2);

		$data = array(
			'title' => 'Test book',
			'author' => 'John Doe',
			'pages' => 2462,
			'available' => true,
		);

		$this->Book->create();
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
		$this->assertFalse(array_key_exists('author', $book['Book']));
		$this->assertTrue(array_key_exists('encrypted', $book['Book']));

		// With valid requester, but wrong password
		$this->Book->requestAs = array('id' => $family[1]['id'], 'password' => 'INVALID_PASSWORD');
		$book = $this->Book->find('first', array(
			'conditions' => array('Book.id' => 1)
		));
		$this->assertEquals(1, $book['Book']['id']);
		$this->assertEquals('Test book', $book['Book']['title']);
		$this->assertFalse(array_key_exists('author', $book['Book']));
		$this->assertTrue(array_key_exists('encrypted', $book['Book']));

		// Valid requesters
		foreach ($family as $member) {
			$this->Book->requestAs = array('id' => $member['id'], 'password' => $member['password']);
			$book = $this->Book->find('first', array(
				'conditions' => array('Book.id' => 1)
			));

			$this->assertEquals(1, $book['Book']['id']);
			$this->assertEquals('Test book', $book['Book']['title']);
			$this->assertTrue(array_key_exists('author', $book['Book']));
			$this->assertFalse(array_key_exists('encrypted', $book['Book']));
			$this->assertEquals('John Doe', $book['Book']['author']);
			$this->assertEquals(2462, $book['Book']['pages']);
			$this->assertEquals(true, $book['Book']['available']);
		}

		// Save in multiple vaults
		$data = array(
			'title' => 'Yet another book',
			'author' => 'Jane Doe',
			'pages' => 80001,
			'available' => false,
		);
		$this->Book->create();
		$this->Book->saveFor = array($family[1]['id'], $altFamily[1]['id']);
		$result = $this->Book->save($data);

		$this->assertNotEquals(false, $result);
		$this->assertEquals(2, $result['Book']['id']);
		$this->assertEquals('Yet another book', $result['Book']['title']);

		$this->assertTrue(array_key_exists('title', $result['Book'])); // non-encrypted
		$this->assertFalse(array_key_exists('author', $result['Book']));
		$this->assertFalse(array_key_exists('pages', $result['Book']));
		$this->assertFalse(array_key_exists('available', $result['Book']));
		$this->assertTrue(array_key_exists('encrypted', $result['Book']));

		// Valid requesters
		foreach ($family as $i => $member) {
			$this->Book->requestAs = array('id' => $member['id'], 'password' => $member['password']);
			$book = $this->Book->find('first', array(
				'conditions' => array('Book.id' => 2)
			));

			$this->assertEquals(2, $book['Book']['id']);
			$this->assertEquals('Yet another book', $book['Book']['title']);

			if ($i <= 1) {
				$this->assertTrue(array_key_exists('author', $book['Book']));
				$this->assertFalse(array_key_exists('encrypted', $book['Book']));
				$this->assertEquals('Jane Doe', $book['Book']['author']);
				$this->assertEquals(80001, $book['Book']['pages']);
				$this->assertEquals(false, $book['Book']['available']);
			} else { // i === 2 does not have access
				$this->assertFalse(array_key_exists('author', $book['Book']));
				$this->assertTrue(array_key_exists('encrypted', $book['Book']));
			}
		}

		foreach ($altFamily as $i => $member) {
			$this->Book->requestAs = array('id' => $member['id'], 'password' => $member['password']);
			$book = $this->Book->find('first', array(
				'conditions' => array('Book.id' => 2)
			));

			$this->assertEquals(2, $book['Book']['id']);
			$this->assertEquals('Yet another book', $book['Book']['title']);
			$this->assertTrue(array_key_exists('author', $book['Book']));
			$this->assertFalse(array_key_exists('encrypted', $book['Book']));
			$this->assertEquals('Jane Doe', $book['Book']['author']);
			$this->assertEquals(80001, $book['Book']['pages']);
			$this->assertEquals(false, $book['Book']['available']);
		}
	}

	public function testSaveEncryptedWithoutSaveForShouldFail() {
		$data = array(
			'title' => 'Test book',
			'author' => 'John Doe',
			'pages' => 2462,
			'available' => true,
		);

		$this->Book->create();
		$this->assertFalse($this->Book->save($data));
	}

	public function testSaveEncryptedIntoVaultlessRequester() {
		$family = FixtureUtils::initFamily($this->Requester, 2);
		$altFamily = FixtureUtils::initFamily($this->Requester, 2);

		$data = array(
			'title' => 'Test book',
			'author' => 'John Doe',
			'pages' => 2462,
			'available' => true,
		);

		// Root does not have vault
		$this->Book->create();
		$this->Book->saveFor = $family[0]['id'];
		$this->assertFalse($this->Book->save($data));

		// 1 of requesters do not have vault, fail all
		$this->Book->create();
		$this->Book->saveFor = array($altFamily[1]['id'], $family[0]['id']);
		$this->assertFalse($this->Book->save($data));

		// Forcefully create vault for root (bad practice)
		$this->assertTrue($this->Requester->createVault($family[0]['id']));

		$this->Book->create();
		$this->Book->saveFor = $family[0]['id']; // Root now has vault
		$result = $this->Book->save($data);
		$this->assertEquals('Test book', $result['Book']['title']);
	}

	public function testAbleToUpdate() {
		$family = FixtureUtils::initFamily($this->Requester, 2);

		$data = array(
			'title' => 'Test book',
			'author' => 'John Doe',
			'pages' => 2462,
			'available' => true,
		);

		$this->Book->create();
		$this->Book->saveFor = $family[1]['id'];
		$result = $this->Book->save($data);
		$this->assertEquals(1, $result['Book']['id']);
		$this->assertTrue(array_key_exists('encrypted', $result['Book']));

		$this->Book->requestAs = $family[0];
		$book = $this->Book->find('first', array(
			'conditions' => array('Book.id' => 1)
		));
		$this->assertEquals('Test book', $book['Book']['title']);
		$this->assertEquals(true, $book['Book']['available']);


		$this->Book->requestAs = $family[1];
		$this->Book->id = 1;
		$result = $this->Book->save(array(
			'title' => 'Updated title',
			'available' => false,
		));
		$this->assertEquals(1, $result['Book']['id']);
		$this->assertEquals('Updated title', $result['Book']['title']);
		$this->assertTrue(array_key_exists('encrypted', $result['Book']));
		$this->assertFalse(array_key_exists('available', $result['Book']));

		$this->Book->requestAs = $family[1];
		$book = $this->Book->find('first', array(
			'conditions' => array('Book.id' => 1)
		));
		$this->assertEquals('Updated title', $book['Book']['title']);
		$this->assertEquals('John Doe', $book['Book']['author']);
		$this->assertEquals(2462, $book['Book']['pages']);
		$this->assertEquals(false, $book['Book']['available']);
	}

	public function testAbleToDelete() {
	}

}
