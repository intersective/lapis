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


	/**
	 * Set up sample requesters
	 *
	 * @param object $Requester Requester model
	 * @param integer $generation Number of generation of requesters. Default 3
	 * @return array Requester ID and respective passwords to unlock ident_private_key
	 */
	public static function initFamily($Requester, $generation = 3) {
		$family = array();
		for ($g = 0; $g < $generation; ++$g) {
			$password = rand();
			$parent = null;
			if ($g > 0) {
				$parent = $family[$g - 1]['id'];
			}
			$Requester->generate($password, array('parent' => $parent));

			$family[$g] = array(
				'id' => $Requester->getLastInsertID(),
				'password' => $password,
			);
		}

		return $family;
	}
}
