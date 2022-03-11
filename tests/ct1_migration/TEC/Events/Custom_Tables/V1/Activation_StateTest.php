<?php

namespace TEC\Events\Custom_Tables\V1;

use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events\Custom_Tables\V1\Tables\Events as EventsSchema;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences as OccurrencesSchema;
use TEC\Events\Custom_Tables\V1\Tables\Provider;
use Tribe\Events\Test\Traits\CT1\CT1_Fixtures;

class Activation_StateTest extends \CT1_Migration_Test_Case {
	use CT1_Fixtures;

	/**
	 * @after each test make sure the custom tables will be there for the following ones.
	 */
	public function recreate_custom_tables() {
		$events_updated = ( new EventsSchema )->update();
		if ( ! $events_updated ) {
			throw new \RuntimeException( 'Failed to create Events custom table.' );
		}
		$occurrences_updated = ( new OccurrencesSchema() )->update();
		if ( ! $occurrences_updated ) {
			throw new \RuntimeException( 'Failed to create Events custom table.' );
		}
	}

	/**
	 * Should be in the correct state for a migration to begin.
	 *
	 * @test
	 */
	public function should_be_ready_to_migrate_with_events() {
		global $wpdb;
		$this->given_a_non_migrated_single_event();
		// Reset state.
		$this->given_a_reset_activation();

		// Activate.
		Activation::init();

		// Validate expected state.
		$state = tribe( State::class );
		$this->assertNotEquals( State::PHASE_MIGRATION_COMPLETE, $state->get_phase() );
		$q      = 'show tables';
		$tables = $wpdb->get_col( $q );
		$this->assertContains( OccurrencesSchema::table_name( true ), $tables );
		$this->assertContains( EventsSchema::table_name( true ), $tables );
	}

	/**
	 * Should skip to a completed migration state when a fresh install.
	 *
	 * @test
	 */
	public function should_be_ready_to_migrate_with_no_events() {
		global $wpdb;
		// Given a normal activation, with no events.
		// Activate.
		Activation::init();

		// Validate expected state.
		$state = tribe( State::class );
		$this->assertEquals( State::PHASE_MIGRATION_COMPLETE, $state->get_phase() );
		$q      = 'show tables';
		$tables = $wpdb->get_col( $q );
		$this->assertContains( OccurrencesSchema::table_name( true ), $tables );
		$this->assertContains( EventsSchema::table_name( true ), $tables );
	}
}