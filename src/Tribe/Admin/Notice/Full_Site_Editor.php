<?php
namespace Tribe\Events\Admin\Notice;

use Tribe__Admin__Helpers as Helpers;
/**
 * Class Full_Site_Editor
 *
 * @since 5.12.4
 *
 */
class Full_Site_Editor {
	/**
	 * Register the notices related to Full Site Editor.
	 *
	 * @since 5.12.4
	 */
	public function hook() {
		tribe_notice(
			'full-site-editor-incompatibility',
			[ $this, 'incompatibility_display' ],
			[
				'type'     => 'error',
				'dismiss'  => 1,
				'priority' => - 1,
				'wrap'     => 'p',
			],
			[ $this, 'incompatibility_should_display' ]
		);
	}

	/**
	 * Whether the FSE Widgets notice should display.
	 *
	 * @since 5.12.4
	 *
	 * @return boolean
	 */
	public function incompatibility_should_display() {
		global $current_screen;

		$screens = Helpers::get_admin_screens_list();

		// If not a valid screen, don't display.
		if ( empty( $current_screen->id ) || ! in_array( $current_screen->id, $screens, true ) ) {
			return false;
		}

		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * HTML for the FSE Widgets compatibility.
	 *
	 * @see   https://evnt.is/wp5-7
	 *
	 * @since 5.12.4
	 *
	 * @return string
	 */
	public function incompatibility_display() {
		$html     = esc_html__( 'The Events Calendar is not yet supported for themes using the Full Site Editor.', 'the-events-calendar' );
		$html .= ' <a target="_blank" href="https://evnt.is/fse-compatibility">' . esc_html__( 'Read more.', 'the-events-calendar' ) . '</a>';

		/**
		 * Allows the modification of the notice for FSE widgets incompatibility.
		 *
		 * @since 5.12.4
		 */
		return apply_filters( 'tec_events_admin_notice_full_site_editor_widget_html', $html, $this );
	}
}
