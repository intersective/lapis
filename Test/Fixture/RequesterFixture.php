<?php
class RequesterFixture extends CakeTestFixture {
	public $table = 'lapis_requesters';

	/**
	 * Load fields from schema.php (DRY)
	 */
	public function init() {
		require_once(dirname(__FILE__) . DS . 'utils.php');
		$this->fields = FixtureUtils::getFieldsFromSchema($this->table);
		parent::init();
	}
}
