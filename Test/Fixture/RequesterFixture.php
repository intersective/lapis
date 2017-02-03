<?php
class RequesterFixture extends CakeTestFixture {
	public $table = 'lapis_requesters';

	/**
	 * Load from schema.php (DRY)
	 */
	public function init() {
		$schemaPath = dirname(dirname(dirname(__FILE__))) . DS . 'Config' . DS . 'Schema' . DS . 'schema.php';
		$LapisSchema = require($schemaPath);
		$schema = new LapisSchema;
		$this->fields = $schema->tables[$this->table];
		parent::init();
	}
}
