<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Venue > Address.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/emails/template-parts/body/event/venue/address.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 * @var WP_Post $venue The venue post object.
 *
 * @see tribe_get_event() For the format of the event object.
 */

 if ( empty( $venue ) ) {
	return;
}

$separator            = '<br />';
$append_after_address = array_filter( array_map( 'trim', [ $venue->state_province, $venue->state, $venue->province ] ) );

?>
<table role="presentation" class="tec-tickets__email-table-content-event-venue-address-table">
	<tr>
		<td class="tec-tickets__email-table-content-event-venue-address-pin-container" valign="top" align="center">
			<img
				class="tec-tickets__email-table-content-event-venue-address-pin"
				width="20"
				height="28"
				src="<?php echo esc_url( tribe_resource_url( 'icons/map-pin.svg', false, null, Tribe__Events__Main::instance() ) ); ?>"
			/>
		</td>
		<td class="tec-tickets__email-table-content-event-venue-address-container">
		<?php
			echo esc_html( $venue->address );

			echo '<br />';

			if ( ! empty( $venue->city ) ) :
				echo esc_html( $venue->city );
				if ( $append_after_address ) :
					echo $separator;
				endif;
			endif;

			if ( $append_after_address ) :
				echo esc_html( reset( $append_after_address ) );
			endif;

			if ( ! empty( $venue->country ) ):
				echo $separator . esc_html( $venue->country );
			endif;

			if ( ! empty( $venue->directions_link ) ) :
				?>
				<br />
				<a href="<?php echo esc_url( $venue->directions_link ); ?>">
					<?php echo esc_html_x( 'Get Directions', 'Link on the Ticket Email', 'the-events-calendar' ); ?>
				</a>
			<?php
			endif;
			?>
		</td>
	</tr>
</table>
