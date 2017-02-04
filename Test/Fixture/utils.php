<?php
class FixtureUtils {
	/**
	 * Load from schema.php (DRY)
	 */
	public static function getFieldsFromSchema($table) {
		$schemaPath = dirname(dirname(dirname(__FILE__))) . DS . 'Config' . DS . 'Schema' . DS . 'schema.php';

		require_once($schemaPath);
		$schema = new LapisSchema;
		return $schema->tables[$table];
	}
}
