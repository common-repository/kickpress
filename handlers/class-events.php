<?php

add_action( 'pre_get_posts', array( 'kickpress_events_handler', 'get_posts' ) );

class kickpress_events_handler extends kickpress_api_handler {
	public static function get_posts( $query ) {
		global $kickpress_api;

		if ( is_admin() && $query == $GLOBALS['wp_the_query'] ) {
			$post_type = $kickpress_api->params['post_type'];
			$meta_type = $kickpress_api->params['meta_type'];

			if ( 'events' == $meta_type ) {
				add_filter( "manage_{$post_type}_posts_columns",
					array( __CLASS__, 'posts_columns' ) );

				add_filter( "manage_edit-{$post_type}_sortable_columns",
					array( __CLASS__, 'sortable_columns' ) );

				add_action( "manage_{$post_type}_posts_custom_column",
					array( __CLASS__, 'custom_column' ), 10, 2 );

				if ( "edit-{$post_type}" == get_current_screen()->id ) {
					if ( 'start_time' == $_REQUEST['orderby'] ) {
						$query->set( 'orderby', 'meta_value' );
						$query->set( 'meta_key', '_start_time' );
					} elseif ( 'end_time' == $_REQUEST['orderby'] ) {
						$query->set( 'orderby', 'meta_value' );
						$query->set( 'meta_key', '_end_time' );
					}
				}
			}
		}
	}

	public static function posts_columns( $posts_columns ) {
		return array_merge( array(
			'cb'         => null,
			'title'      => null,
			'start_time' => 'Start Time',
			'end_time'   => 'End Time'
		), $posts_columns );
	}

	public static function sortable_columns( $sortable_columns ) {
		$sortable_columns['start_time'] = 'start_time';
		$sortable_columns['end_time']   = 'end_time';

		return $sortable_columns;
	}

	public static function custom_column( $column_name, $post_id ) {
		if($column_name === 'start_time' || $column_name === 'end_time'){
			$date = strtotime( get_post_meta( $post_id, "_{$column_name}", true ) );
			echo date( 'F jS, Y', $date ) . '<br>' . date( 'g:i A', $date );
		}
	}

	public function __construct( $api ) {
		parent::__construct( $api );

		$api->params['hierarchical'] = true;
	}

	public function get_custom_fields() {
		$custom_fields = array(
			'start_time' => array(
				'name'       => '_start_time',
				'caption'    => 'Start Time',
				'type'       => 'datetime',
				'exportable' => true,
				'filterable' => true,
				'searchable' => true,
				'sortable'   => true
			),
			'end_time'   => array(
				'name'       => '_end_time',
				'caption'    => 'End Time',
				'type'       => 'datetime',
				'exportable' => true,
				'filterable' => true,
				'searchable' => true,
				'sortable'   => true
			),
			'is_all_day' => array(
				'name'       => '_is_all_day',
				'caption'    => 'All-Day Event',
				'type'       => 'radio',
				'default'    => 'no',
				'options'    => array(
					'yes' => 'Yes',
					'no'  => 'No'
				)
			),
			'repeating'  => array(
				'caption' => 'Repeating Event',
				'type'    => 'title'
			),
			'do_repeat'  => array(
				'name'    => '_do_repeat',
				'caption' => 'This Event Repeats',
				'type'    => 'select',
				'default' => 'none',
				'options' => array(
					'none'  => 'Never',
					'day_of_month'         => 'Monthly',
					'day_of_month_in_year' => 'Monthly, by Year',
					'day_of_week'          => 'Weekly',
					'day_of_week_in_month' => 'Weekly, by Month'
				)
			),
			'rep_time'   => array(
				'name'    => '_rep_time',
				'caption' => 'End Repeat Time',
				'type'    => 'date'
			)
		);

		return $custom_fields;
	}

	public function update_meta_fields( $post, $post_data, $form_data ) {
		return $post_data;
		if ( 'no' == $post_data['_is_all_day'] ) {
			foreach ( $_REQUEST['hour_data'] as $post_type => $posts ) {
				foreach ( $posts as $post_id => $times ) {
					foreach ( $times as $key => $hour ) {
						$minute = $_REQUEST['minute_data'][$post_type][$post_id][$key];

						$time = strtotime( $post_data[$key] );
						$time = mktime( $hour, $minute, 0,
							date('m', $time),
							date('d', $time),
							date('Y', $time)
						);

						$post_data[$key] = date( 'Y-m-d H:i:s', $time );

						update_post_meta( $post_id, $key, $post_data[$key] );
					}
				}
			}
		}

		$post_id = $post->ID;

		if ( 0 == $post->post_parent ) {
			global $wpdb;

			$sql = "DELETE $wpdb->posts, $wpdb->postmeta "
				 . "FROM $wpdb->posts LEFT JOIN $wpdb->postmeta "
				 . "ON $wpdb->posts.ID = $wpdb->postmeta.post_id "
				 . "WHERE $wpdb->posts.post_parent = $post_id";

			$wpdb->query( $sql );

			if ( 'none' == $post_data['_do_repeat'] ) {
				delete_post_meta( $post_id, '_rep_rule' );
			} else {
				$post_data['_rep_rule'] = $_REQUEST['repeat_data'];
				$post_data['_rep_rule']['start_time'] = date( 'Y-m-d', strtotime( $post_data['_start_time'] ) );
				$post_data['_rep_rule']['end_time']   = date( 'Y-m-d', strtotime( $post_data['_rep_time']   ) );

				update_post_meta( $post_id, '_rep_rule', $post_data['_rep_rule'] );

				$post = wp_get_single_post( $post_id, ARRAY_A );
				$post['post_parent'] = $post['ID'];

				$post_name = $post['post_name'];

				$meta = $post_data;

				unset( $post['ID'], $meta['_do_repeat'], $meta['_rep_time'], $meta['_rep_rule'] );

				$dates = $this->get_dates( $post_id );
				$count = 0;

				foreach ( $dates as $date ) {
					$post['post_name'] = sprintf( '%s-%d', $post_name, ++$count );

					$meta['_start_time'] = date( 'Y-m-d H:i:s', $date['start_time'] );
					$meta['_end_time']   = date( 'Y-m-d H:i:s', $date['end_time'] );

					if ( $new_post_id = wp_insert_post( $post ) ) {

						foreach ( $meta as $meta_key => $meta_value ) {
							add_post_meta( $new_post_id, $meta_key, $meta_value );
						}
					}
				}
			}
		}

		return $post_data;
	}

	public function form_footer() {
		$post_id = $this->_api->params['post_id'] = get_the_ID();
		$post = get_post( $post_id );

		$html = sprintf( '
			<style type="text/css">
				ul.month_of_year,
				ul.week_of_month,
				ul.day_of_month,
				ul.day_of_week {
					display: none;
					overflow: hidden;
					width: 100%%;
				}

				ul.week_of_month li,
				ul.day_of_month li {
					float: left;
					width: 4em;
				}

				ul.month_of_year li,
				ul.day_of_week li {
					float: left;
					width: 8em;
				}
			</style>'
		);

		$html .= sprintf( '
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					if ("none" == $("select[name=\'data[%2$s][%1$d][_do_repeat]\']").val()) {
						$("#%2$s_%1$d_rep_time").parents("tr").hide();
					}

					$("input[name=\'data[%2$s][%1$d][_is_all_day]\']").click(function() {
						var start_hour   = $("select[name=\'hour_data[%2$s][%1$d][_start_time]\']");
						var start_minute = $("select[name=\'minute_data[%2$s][%1$d][_start_time]\']");
						var end_hour   = $("select[name=\'hour_data[%2$s][%1$d][_end_time]\']");
						var end_minute = $("select[name=\'minute_data[%2$s][%1$d][_end_time]\']");

						if ("yes" == $(this).val()) {
							start_hour.hide();
							start_minute.hide();
							end_hour.hide();
							end_minute.hide();
						} else {
							start_hour.show();
							start_minute.show();
							end_hour.show();
							end_minute.show();
						}
					});

					$("select[name=\'data[%2$s][%1$d][_do_repeat]\']").change(function() {
						if ("none" == $(this).val()) {
							$("#%2$s_%1$d_rep_time").parents("tr").hide();
						} else {
							$("#%2$s_%1$d_rep_time").parents("tr").show();
						}

						$("ul.month_of_year").hide();
						$("ul.day_of_month").hide();
						$("ul.week_of_month").hide();
						$("ul.day_of_week").hide();

						switch ( $(this).val() ) {
							case "day_of_month_in_year":
								$("ul.month_of_year").show();
							case "day_of_month":
								$("ul.day_of_month").show();
								break;
							case "day_of_week_in_month":
								$("ul.week_of_month").show();
							case "day_of_week":
								$("ul.day_of_week").show();
								break;
						}
					});
				});
			</script>',
			$this->_api->params['post_id'],
			$this->_api->params['post_type']
		);

		if ( 0 == $post->post_parent ) {
			$repeat = get_post_meta( $post_id, '_do_repeat', true );

			$is_day_of_month = stripos( $repeat, 'day_of_month' ) !== false;
			$is_in_year      = stripos( $repeat, 'in_year' )      !== false;
			$is_day_of_week  = stripos( $repeat, 'day_of_week' )  !== false;
			$is_in_month     = stripos( $repeat, 'in_month' )     !== false;

			$rule = get_post_meta( $post_id, '_rep_rule', true );

			$refts = strtotime( "Last Sunday" );

			for ( $i = 1; $i <= 31; $i++ ) {
				$is_checked = @in_array( $i, array_keys( $rule['day_of_month'] ) );

				$days_of_month[] = sprintf( '
					<li>
						<input id="day_of_month_%1$d" type="checkbox"
							name="repeat_data[day_of_month][%1$d]"
							value="%1$d"%2$s>
						<label for="day_of_month_%1$d">%1$d</label>
					</li>',
					$i,
					$is_checked ? ' checked="checked"' : ''
				);
			}

			for ( $i = 1; $i <= 12; $i++ ) {
				$is_checked = @in_array( $i, array_keys( $rule['month_of_year'] ) );

				$months_of_year[] = sprintf( '
					<li>
						<input id="month_of_year_%1$d" type="checkbox"
							name="repeat_data[month_of_year][%1$d]"
							value="%1$d"%3$s>
						<label for="month_of_year_%1$d">%2$s</label>
					</li>',
					$i,
					gmdate( 'F', gmmktime( 0, 0, 0, $i, 1 ) ),
					$is_checked ? ' checked="checked"' : ''
				);
			}

			for ( $i = 0; $i < 7; $i++ ) {
				$is_checked = @in_array( $i, array_keys( $rule['day_of_week'] ) );

				$ts = gmmktime(
					gmdate( 'H', $refts ),
					gmdate( 'i', $refts ),
					gmdate( 's', $refts ),
					gmdate( 'm', $refts ),
					gmdate( 'd', $refts ) + $i,
					gmdate( 'Y', $refts )
				);

				$days_of_week[] = sprintf( '
					<li>
						<input id="day_of_week_%1$d" type="checkbox"
							name="repeat_data[day_of_week][%1$d]"
							value="%1$d"%3$s>
						<label for="day_of_week_%1$d">%2$s</label>
					</li>',
					$i,
					gmdate( 'l', $ts ),
					$is_checked ? ' checked="checked"' : ''
				);
			}

			for ( $i = 1; $i <= 5; $i++ ) {
				$is_checked = @in_array( $i, @array_keys( $rule['week_of_month'] ) );

				$weeks_of_month[] = sprintf( '
					<li>
						<input id="week_of_month_%1$d" type="checkbox"
							name="repeat_data[week_of_month][%1$d]"
							value="%1$d"%2$s>
						<label for="week_of_month_%1$d">%1$d</label>
					</li>',
					$i,
					$is_checked ? ' checked="checked"' : ''
				);
			}

			$html .= sprintf( '
				<ul class="day_of_month" style="%5$s">%1$s</ul>
				<ul class="month_of_year" style="%6$s">%2$s</ul>
				<ul class="day_of_week" style="%7$s">%3$s</ul>
				<ul class="week_of_month" style="%8$s">%4$s</ul>',
				implode( PHP_EOL, $days_of_month ),
				implode( PHP_EOL, $months_of_year ),
				implode( PHP_EOL, $days_of_week ),
				implode( PHP_EOL, $weeks_of_month ),
				$is_day_of_month ? 'display: block;' : '',
				$is_in_year      ? 'display: block;' : '',
				$is_day_of_week  ? 'display: block;' : '',
				$is_in_month     ? 'display: block;' : ''
			);

			//foreach ( $this->get_dates( $post_id ) as $date )
			//	$html .= '<pre style="margin: 0px;">' . date( DATE_RSS, $date ) . '</pre>';
		}

		return $html;
	}

	public function get_dates( $post_id ) {
		$repeat_rules = array_reverse( get_post_meta( $post_id, '_rep_rule', false ) );

		if ( ! empty( $repeat_rules ) ) {
			$ranges = array();

			// build cumulative ranges
			foreach ( $repeat_rules as $index => $rule ) {
				if ( 0 == $index )
					$ranges[$index] = new kickpress_date_range();
				else
					$ranges[$index] = new kickpress_date_range( $ranges[$index - 1]->segs );

				$ranges[$index]->add_segment( $rule['start_time'], $rule['end_time'] );
				$ranges[$index]->minimize();
			}

			// remove range overlaps
			for ( $i = count( $ranges ) - 1; $i > 0; $i-- ) {
				$ranges[$i]->sub_range( $ranges[$i - 1] );
			}

			$dates = array();

			$stime = strtotime( get_post_meta( $post_id, '_start_time', true ) ) % 86400;
			$etime = strtotime( get_post_meta( $post_id, '_end_time',   true ) ) % 86400;

			foreach ( $ranges as $index => $range ) {
				$rules = $repeat_rules[$index];

				unset( $rules['start_time'], $rules['end_time'] );

				foreach ( $range->segs as $seg ) {
					$date = $seg->min;

					while ( $date <= $seg->max ) {
						$valid_date = true;

						foreach ( $rules as $rule => $values ) {
							if ( ! $valid_date ) continue;

							$valid_rule = true;

							switch ( $rule ) {
								case 'month_of_year':
									$value = (int) date( 'm', $date ) - 1;
									break;
								case 'week_of_month':
									$value = (int) ceil( date( 'd', $date ) / 7 );
									break;
								case 'day_of_month':
									$value = (int) date( 'd', $date );
									break;
								case 'day_of_week':
									$value = (int) date( 'w', $date );
									break;
								default:
									$valid_rule = false;
									break;
							}

							if ( $valid_rule )
								$valid_date = in_array( $value, $values );
						}

						if ( $valid_date ) $dates[] = array(
							'start_time' => $date + $stime,
							'end_time'   => $date + $etime,
						);

						$date = gmmktime(
							gmdate( 'H', $date ),
							gmdate( 'i', $date ),
							gmdate( 's', $date ),
							gmdate( 'm', $date ),
							gmdate( 'd', $date ) + 1,
							gmdate( 'Y', $date )
						);
					}
				}
			}

			sort( $dates );

			return $dates;
		}

		return array( 0 => strtotime( get_post_meta( $post_id, '_rep_rule', true ) ) );
	}
}

class kickpress_range {
	var $segs = array();
	var $min  = null;
	var $max  = null;

	function __construct( $segs = array() ) {
		foreach ( $segs as $seg ) {
			$seg = array_values( (array) $seg );
			$this->add_segment( $seg[0], $seg[1] );
		}
	}

	function sub_range( $range ) {
		foreach ( $range->segs as $seg )
			$this->sub_segment( $seg->min, $seg->max );
	}

	function add_segment( $min, $max ) {
		$this->segs[] = (object) array( 'min' => $min, 'max' => $max );

		$this->min = is_null( $this->min ) ? $min : min( $min, $this->min );
		$this->max = is_null( $this->max ) ? $max : max( $max, $this->max );
	}

	function sub_segment( $min, $max ) {
		$this->minimize();

		foreach ( $this->segs as $index => $seg ) {
			if ( $min <= $seg->min && $max >= $seg->max ) {
				unset( $this->segs[$index] );
				continue;
			}

			if ( $min >= $seg->min && $min <= $seg->max ) {
				if ( $max < $seg->max )
					$this->add_segment( $this->increment( $max ), $seg->max );

				if ( $min > $seg->min )
					$seg->max = $this->decrement( $min );
			}

			if ( $max >= $seg->min && $max <= $seg->max ) {
				if ( $min > $seg->min )
					$this->add_segment( $seg->min, $this->decrement( $min ) );

				if ( $max < $seg->max )
					$seg->min = $this->increment( $max );
			}
		}

		$this->minimize();
		$this->min = $this->segs[0]->min;
		$this->max = $this->segs[count( $this->segs ) - 1]->max;
	}

	function contains( $val ) {
		foreach ( $this->segs as $seg ) {
			if ( $val >= $seg->min && $val <= $seg->max )
				return true;
		}

		return false;
	}

	function compare( $seg1, $seg2 ) {
		return $seg1->min - $seg2->min;
	}

	function overlap( $seg1, $seg2 ) {
		$min = max( $seg1->min, $seg2->min );
		$max = min( $seg1->max, $seg2->max );

		return $min <= $max + 1;
	}

	function minimize() {
		usort( $this->segs, array( $this, 'compare' ) );

		$j = 0;
		$n = count( $this->segs );

		for ( $i = 1; $i < $n; $i++ ) {
			$segi = $this->segs[$i];
			$segj = $this->segs[$j];

			if ( $this->overlap( $segi, $segj ) ) {
				$this->segs[$i] = (object) array(
					'min' => min( $segi->min, $segj->min ),
					'max' => max( $segi->max, $segj->max )
				);

				unset( $this->segs[$j] );
			}

			$j = $i;
		}

		$this->segs = array_values( $this->segs );
	}

	function increment( $val ) {
		return ++$val;
	}

	function decrement( $val ) {
		return --$val;
	}
}

class kickpress_date_range extends kickpress_range {
	function add_segment( $min, $max ) {
		$min = is_int( $min ) ? $min : strtotime( $min );
		$max = is_int( $max ) ? $max : strtotime( $max );

		$this->segs[] = (object) array( 'min' => $min, 'max' => $max );

		$this->min = is_null( $this->min ) ? $min : min( $min, $this->min );
		$this->max = is_null( $this->max ) ? $max : max( $max, $this->max );
	}

	function sub_segment( $min, $max ) {
		$min = is_int( $min ) ? $min : strtotime( $min );
		$max = is_int( $max ) ? $max : strtotime( $max );

		parent::sub_segment( $min, $max );
	}

	function contains( $val ) {
		$val = is_int( $val ) ? $val : strtotime( $val );

		return parent::contains( $val );
	}

	function compare( $seg1, $seg2 ) {
		$min1 = $this->get_int( $seg1->min );
		$min2 = $this->get_int( $seg2->min );

		return $min1 - $min2;
	}

	function overlap( $seg1, $seg2 ) {
		$min = max( $this->get_int( $seg1->min ), $this->get_int( $seg2->min ) );
		$max = min( $this->get_int( $seg1->max ), $this->get_int( $seg2->max ) );

		return $min <= $max + 1;
	}

	function increment( $val ) {
		$time = is_int( $val ) ? $val : strtotime( $val );
		return mktime( 0, 0, 0, date( 'm', $time ), date( 'd', $time ) + 1, date( 'Y', $time ) );
	}

	function decrement( $val ) {
		$time = is_int( $val ) ? $val : strtotime( $val );
		return mktime( 0, 0, 0, date( 'm', $time ), date( 'd', $time ) - 1, date( 'Y', $time ) );
	}

	function get_int( $time ) {
		return date( 'z', $time ) + (int) floor( date( 'Y', $time ) * 365.25 );
	}
}
