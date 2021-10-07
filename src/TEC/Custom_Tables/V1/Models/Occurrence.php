<?php
/**
 * The Event validation and format schema.
 *
 * @since   TBD
 *
 * @package TEC\Custom_Tables\V1\Models
 */

namespace TEC\Custom_Tables\V1\Models;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Generator;
use TEC\Custom_Tables\V1\Events\Occurrences\Occurrences_Generator;
use TEC\Custom_Tables\V1\Models\Formatters\Date_Formatter;
use TEC\Custom_Tables\V1\Models\Formatters\Integer_Key_Formatter;
use TEC\Custom_Tables\V1\Models\Formatters\Numeric_Formatter;
use TEC\Custom_Tables\V1\Models\Formatters\Text_Formatter;
use TEC\Custom_Tables\V1\Models\Validators\Duration;
use TEC\Custom_Tables\V1\Models\Validators\End_Date;
use TEC\Custom_Tables\V1\Models\Validators\End_Date_UTC;
use TEC\Custom_Tables\V1\Models\Validators\Integer_Key;
use TEC\Custom_Tables\V1\Models\Validators\Positive_Integer;
use TEC\Custom_Tables\V1\Models\Validators\Start_Date;
use TEC\Custom_Tables\V1\Models\Validators\Start_Date_UTC;
use TEC\Custom_Tables\V1\Models\Validators\String_Validation;
use TEC\Custom_Tables\V1\Models\Validators\Valid_Date;
use TEC\Custom_Tables\V1\Models\Validators\Valid_Event;
use TEC\Custom_Tables\V1\Tables\Occurrences;
use Tribe__Date_Utils as Dates;
use Tribe__Timezones as Timezones;

/**
 * Class Occurrence
 *
 * @since   TBD
 *
 * @package TEC\Custom_Tables\V1\Models
 *
 * @property Event  event
 * @property int    occurrence_id
 * @property int    event_id
 * @property int    post_id
 * @property string start_date
 * @property string start_date_utc
 * @property string end_date
 * @property string end_date_utc
 * @property int    duration
 * @property string hash
 * @property string updated_at
 * @property bool   has_recurrence (ECP only)
 */
class Occurrence extends Model {
	/**
	 * {@inheritdoc }
	 */
	protected $validations = [
		'occurrence_id'  => Integer_Key::class,
		'event_id'       => Positive_Integer::class,
		'post_id'        => Valid_Event::class,
		'start_date'     => Start_Date::class,
		'end_date'       => End_Date::class,
		'start_date_utc' => Start_Date_UTC::class,
		'end_date_utc'   => End_Date_UTC::class,
		'duration'       => Duration::class,
		'hash'           => String_Validation::class,
		'updated_at'     => Valid_Date::class,
	];

	/**
	 * {@inheritdoc }
	 */
	protected $formatters = [
		'occurrence_id'  => Integer_Key_Formatter::class,
		'event_id'       => Numeric_Formatter::class,
		'post_id'        => Numeric_Formatter::class,
		'start_date'     => Date_Formatter::class,
		'end_date'       => Date_Formatter::class,
		'start_date_utc' => Date_Formatter::class,
		'end_date_utc'   => Date_Formatter::class,
		'duration'       => Numeric_Formatter::class,
		'hash'           => Text_Formatter::class,
		'updated_at'     => Date_Formatter::class,
	];

	/**
	 * {@inheritdoc}
	 */
	protected $table = Occurrences::TABLE_NAME;

	/**
	 * {@inheritdoc}
	 */
	protected $primary_key = 'occurrence_id';

	/**
	 * {@inheritdoc}
	 *
	 * @since TBD
	 *
	 * @var string[] hashed_keys
	 */
	protected $hashed_keys = [
		'event_id',
		'post_id',
		'start_date',
		'end_date',
		'start_date_utc',
		'end_date_utc',
		'duration',
	];

	/**
	 * Method to save the occurrences from an event.
	 *
	 * @since TBD
	 *
	 * @param mixed $args,... The arguments that should be used to generate and save the Occurrences.
	 *
	 * @return void The method has the side-effect of generating and saving Occurrences for the Event.
	 *
	 * @throws Exception If there's an issue in the format or coherency of the additional data.
	 */
	public function save_occurrences( ...$args ) {
		$insertions = $this->get_occurrences( ...$args );

		if ( ! count( $insertions ) ) {
			return;
		}

		self::insert( $insertions );
	}

	/**
	 * Remove occurrences that are no longer relevant or that are legacy at this point.
	 *
	 * @since TBD
	 *
	 * @param  DateTimeInterface  $now  The point in time in which the occurrences can be considered archived.
	 *
	 * @return bool The result of the operation
	 */
	public function purge_recurrences( DateTimeInterface $now ) {
		$event = $this->event;
		$done    = self::where( 'post_id', $event->post_id )
		               ->where( 'event_id', $event->event_id )
		               ->where( 'updated_at', '<', $now->format( Dates::DBDATETIMEFORMAT ) )
		               ->delete();

		$this->align_event_meta( $event );

		return $done;
	}

	/**
	 * Cast the value of the event ID to an integer if present, null otherwise when reading the `event_id` property.
	 *
	 * @since TBD
	 *
	 * @param $value
	 *
	 * @return int|null
	 */
	public function get_event_id_attribute( $value ) {
		return $value ? (int) $value : null;
	}

	/**
	 * Cast the value of the property `post_id` if present to an integer.
	 *
	 * @since TBD
	 *
	 * @param $value
	 *
	 * @return int|null
	 */
	public function get_post_id_attribute( $value ) {
		return $value ? (int) $value : null;
	}

	/**
	 * Dynamic accessor to the occurrence ID attribute.
	 *
	 * @since TBD
	 *
	 * @param $value
	 *
	 * @return int|null
	 */
	public function get_occurrence_id_attribute( $value ) {
		return $value ? (int) $value : null;
	}

	/**
	 * If the occurrence was generated using a recurrence rule.
	 *
	 * @since TBD
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function get_has_recurrence_attribute( $value ) {
		return (bool) (int) $value;
	}

	/**
	 * Returns the Occurrence model instance, if any , that starts first between all the Occurrences.
	 *
	 * @since TBD
	 *
	 * @return Model|null Either the Model for the Occurrence entry that starts first, or `null`
	 *                    to indicate there are no Occurrences.
	 */
	public static function earliest() {
		$column = Timezones::is_mode( 'site' ) ? 'start_date_utc' : 'start_date';

		return self::order_by( $column )->first();
	}

	/**
	 * Returns the Occurrence mode, if any , that ends last between all the Occurrences.
	 *
	 * @since TBD
	 *
	 * @return Model|null Either the Model for the Occurrence entry that ends last, or `null`
	 *                    to indicate there are no Occurrences.
	 */
	public static function latest() {
		$column = Timezones::is_mode( 'site' ) ? 'end_date_utc' : 'end_date';

		return static::order_by( $column, 'DESC' )->first();
	}

	/**
	 * Returns whether an Occurrence is the last Occurrence in context of the Recurring Event
	 * it belongs to, or not.
	 *
	 * @since TBD
	 *
	 * @param  int|Occurrence  $occurrence  Either an Occurrence `occurrence_id` or an instance of the
	 *                                      Occurrence Model.
	 *
	 * @return bool Whether an Occurrence is the first occurrence in context of the Recurring Event
	 *              it belongs to, or not.
	 */
	public static function is_last( $occurrence ) {
		$occurrence = $occurrence instanceof self
			? $occurrence
			: static::find( $occurrence, 'occurrence_id' );

		if ( ! $occurrence instanceof self ) {
			return false;
		}

		$last = self::where( 'event_id', '=', $occurrence->event_id )
		            ->order_by( 'start_date', 'DESC' )
		            ->first();

		return $last instanceof self
		       && $last->occurrence_id === $occurrence->occurrence_id;
	}

	/**
	 * Returns whether an Occurrence is the first Occurrence in context of the Recurring Event
	 * it belongs to, or not.
	 *
	 * @since TBD
	 *
	 * @param  int|Occurrence  $occurrence  Either an Occurrence `occurrence_id` or an instance of the
	 *                                      Occurrence Model.
	 *
	 * @return bool Whether an Occurrence is the first occurrence in context of the Recurring Event
	 *              it belongs to, or not.
	 */
	public static function is_first( $occurrence ) {
		$occurrence = $occurrence instanceof self
			? $occurrence
			: static::find( $occurrence, 'occurrence_id' );

		if ( ! $occurrence instanceof self ) {
			return false;
		}

		$first = self::where( 'event_id', '=', $occurrence->event_id )
		             ->order_by( 'start_date', 'ASC' )
		             ->first();

		return $first instanceof self
		       && $first->occurrence_id === $occurrence->occurrence_id;
	}

	/**
	 * Aligns the Event dates, both in the meta and events custom table, with
	 * the first and last event Occurrences.
	 *
	 * @since TBD
	 *
	 * @param Event|null $event A reference to the Event Model instance to update.
	 *
	 * @return bool Whether the updates were applicable and correctly applied or not.
	 */
	private function align_event_meta( Event $event = null ) {
		if ( null === $event ) {
			return false;
		}

		$post_id = $event->post_id;

		/** @var Occurrence $first */
		$first = self::where( 'post_id', '=', $post_id )
		             ->order_by( 'start_date', 'ASC' )
		             ->first();

		$last  = self::where( 'post_id', '=', $post_id )
		             ->order_by( 'start_date', 'DESC' )
		             ->first();

		if ( ! ( $first instanceof self && $last instanceof self ) ) {
			return false;
		}

		if ( $event->is_infinite() ) {
			update_post_meta( $post_id, '_EventStartDate', $event->start_date );
			update_post_meta( $post_id, '_EventStartDateUTC', $event->start_date_utc );
		} else {
			update_post_meta( $post_id, '_EventStartDate', $first->start_date );
			update_post_meta( $post_id, '_EventStartDateUTC', $first->start_date_utc );
		}

		update_post_meta( $post_id, '_EventEndDate', $first->end_date );
		update_post_meta( $post_id, '_EventEndDateUTC', $first->end_date_utc );
		update_post_meta( $post_id, '_EventDuration', $first->duration );

		$data = [
			'post_id'        => $post_id,
			'start_date'     => $first->start_date,
			'start_date_utc' => $first->start_date_utc,
		];

		// Only update the data if the event is not infinite.
		if ( ! $event->is_infinite() ) {
			$data['end_date']       = $last->end_date;
			$data['end_date_utc']   = $last->end_date_utc;
		}

		return Event::upsert( [ 'post_id' ], $data );
	}

	/**
	 * Calculates and returns the set of Occurrences that would be generated for the Event.
	 *
	 * This method is used internally by the `save_occurrences` method to calculate what should
	 * be inserted in the database for an Event.
	 *
	 * @since TBD
	 *
	 * @param mixed $args,...       The set of arguments that should be used to generate the
	 *                              Occurrences.
	 *
	 * @return array The set of insertions that should be performed for the Event and the
	 *               provided data.
	 *
	 * @throws Exception
	 */
	private function get_occurrences( ...$args ) {
		/**
		 * Filters the Generator that will provide the Occurrences insertions
		 * for the Event.
		 *
		 * @since TBD
		 *
		 * @param Generator<Occurrence>|null $generator    A reference to the Generator that will produce
		 *                                                 the Occurrences for the data.
		 * @param mixed                      $args,...     The set of arguments to build the Generator for.
		 * @param Event $event                             A reference to the Event object Occurrences should be
		 *                                                 generated for.
		 */
		$generator = apply_filters( 'tec_custom_tables_v1_occurrences_generator', null, $this->event, ...$args );

		if ( ! $generator instanceof Generator ) {
			// If no generator was provided, then use the default one.
			$occurrences_generator = tribe()->make( Occurrences_Generator::class );
			$generator             = $occurrences_generator->generate_from_event( $this->event );
		}

		$insertions = [];
		$utc        = new DateTimeZone( 'UTC' );
		foreach ( $generator as $result ) {
			$occurrence = self::where( 'hash', $result->generate_hash() )->first();
			if ( $occurrence instanceof self ) {
				$result->occurrence_id = $occurrence->occurrence_id;
				$result->updated_at    = new DateTime( 'now', $utc );
				$result->update();
				continue;
			}
			$insertions[] = $result->toArray();
		}

		$this->align_event_meta( $this->event );

		return $insertions;
	}

	/**
	 * Finds the Occurrence model instance, if any, for a real post ID, a provisional post ID,
	 * or an Occurrence ID.
	 *
	 * @param int $id The ID to return an Occurrence instance for. Either a real Event Post ID,
	 *                a provisional Occurrence ID.
	 *
	 * @return Occurrence|null A reference to the matching Occurrence instance, or `null` if
	 *                         no Occurrence instance could be matched to the ID.
	 */
	public static function find_by_post_id( $id ) {
		if ( empty( $id ) ) {
			return null;
		}

		$provisional_post = tribe( Provisional_Post::class );

		if ( $provisional_post->is_provisional_post_id( $id ) ) {
			return static::find(
				$provisional_post->normalize_provisional_post_id( $id ),
				'occurrence_id'
			);
		}

		return static::find( $id, 'post_id' );
	}
}