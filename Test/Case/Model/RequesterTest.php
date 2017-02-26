<?php
require_once(dirname(dirname(dirname(__FILE__))) . DS . 'Fixture' . DS . 'utils.php');
class RequesterTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.lapis.requester',
		'plugin.lapis.accessor',
	);

	public function setUp() {
		$this->Requester = ClassRegistry::init('Lapis.Requester');
	}

	public function testGenerate() {
		$password = 'Passphrase to encrypt private key';
		$this->assertTrue($this->Requester->generate($password));
		$id = $this->Requester->getLastInsertID();

		$requester = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $id)
		));

		$this->assertNull($requester['Requester']['parent_id']);
		$this->assertContains('BEGIN PUBLIC KEY', $requester['Requester']['ident_public_key']);
		$this->assertNotEmpty($requester['Requester']['ident_private_key']);
		$this->assertNotContains('BEGIN PRIVATE KEY', $requester['Requester']['ident_private_key']);

		// Root should not have vault by default
		$this->assertNull($requester['Requester']['vault_public_key']);

		// No password
		$this->assertTrue($this->Requester->generate(null, array(
			'parent' => $id,
			'keysize' => 1024
		)));
		$secondID = $this->Requester->getLastInsertID();
		$this->assertNotEquals($id, $secondID);

		$requester = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $secondID)
		));
		$this->assertEquals($id, $requester['Requester']['parent_id']);
		$this->assertContains('BEGIN PUBLIC KEY', $requester['Requester']['ident_public_key']);
		$this->assertContains('BEGIN PRIVATE KEY', $requester['Requester']['ident_private_key']);

		// Not root should have a vault by default
		$this->assertNotEmpty($requester['Requester']['vault_public_key']);
	}

	public function testCreateVault() {
		$password = sha1(rand());
		$this->assertTrue($this->Requester->generate($password), array(
			'hasVault' => false
		));
		$id = $this->Requester->getLastInsertID();
		$requester = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $id)
		));
		$this->assertNull($requester['Requester']['vault_public_key']);
		$this->Requester->createVault($id);

		$requester = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $id)
		));
		$this->assertNotEmpty($requester['Requester']['vault_public_key']);
	}

	public function testGetAncestors() {
		$family = FixtureUtils::initFamily($this->Requester, 3);

		$root = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $family[0])
		));
		$child = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $family[1])
		));
		$grandchild = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $family[2])
		));

		$this->assertNull($root['Requester']['parent_id']);
		$this->assertEquals($root['Requester']['id'], $child['Requester']['parent_id']);
		$this->assertEquals($child['Requester']['id'], $grandchild['Requester']['parent_id']);

		$ancestors = $this->Requester->getAncestors($grandchild['Requester']['id']);
		$this->assertEquals(array(
			$grandchild['Requester']['id'] => $grandchild['Requester']['ident_public_key'],
			$child['Requester']['id'] => $child['Requester']['ident_public_key'],
			$root['Requester']['id'] => $root['Requester']['ident_public_key']
		), $ancestors);

		// Exclude self
		$ancestors = $this->Requester->getAncestors($grandchild['Requester']['id'], false);
		$this->assertEquals(array(
			$child['Requester']['id'] => $child['Requester']['ident_public_key'],
			$root['Requester']['id'] => $root['Requester']['ident_public_key']
		), $ancestors);
	}
}
