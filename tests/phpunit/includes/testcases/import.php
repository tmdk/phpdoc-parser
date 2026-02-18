<?php

/**
 * A parent test case class for the data export tests.
 */

namespace WP_Parser\Tests;

use WP_Parser\Importer;

/**
 * Parent test case for data export tests.
 */
class Import_UnitTestCase extends Export_UnitTestCase {

	/**
	 * The importer instace used in the tests.
	 *
	 * @var Importer
	 */
	protected Importer $importer;

	/**
	 * Set up before the tests.
	 */
	public function set_up(): void {

		parent::set_up();

		$this->importer = new Importer;
		$this->importer->import( [ $this->export_data ] );
	}
}
