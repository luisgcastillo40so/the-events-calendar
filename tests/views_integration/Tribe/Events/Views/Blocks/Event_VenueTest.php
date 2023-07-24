<?php

namespace Tribe\Events\Views\Blocks;

use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\Products\WPBrowser\Views\V2\HtmlTestCase;
use Tribe__Events__Editor__Blocks__Event_Venue;

class Event_VenueTest extends HtmlTestCase {
	use MatchesSnapshots;

	protected $block_content;

	public function setUp(): void {
		parent::setUp();

		$block = new Tribe__Events__Editor__Blocks__Event_Venue();

		ob_start();
		echo $block->render();

		$this->block_content = ob_get_clean();
	}

	/**
	 * Test that the block is rendered.
	 */
	public function test_block_is_rendered() {
		$this->assertStringContainsString( 'tribe-block', $this->block_content );
		$this->assertStringContainsString( 'tribe-block__venue', $this->block_content );
		$this->assertStringContainsString( 'tribe-clearfix', $this->block_content );
	}

	/**
	 * Test that the block is rendered with no custom classes.
	 */
	public function test_render_no_custom_classes() {
		$this->assertMatchesSnapshot( $this->block_content );
	}

	/**
	 * Test that the block is rendered with single custom class.
	 */
	public function test_render_with_single_custom_class() {
		$block_with_custom_class = new Tribe__Events__Editor__Blocks__Event_Venue();
		$custom_class_content    = $block_with_custom_class->render( [ 'className' => 'custom-class' ] );

		$this->assertMatchesSnapshot( $custom_class_content );
	}

	/**
	 * Test that the block is rendered with single custom class.
	 */
	public function test_render_with_multiple_custom_classes() {
		$block_with_custom_class = new Tribe__Events__Editor__Blocks__Event_Venue();
		$custom_class_content    = $block_with_custom_class->render( [ 'className' => 'custom-class custom-class-2' ] );

		$this->assertMatchesSnapshot( $custom_class_content );
	}
}