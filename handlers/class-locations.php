<?php

class kickpress_locations_handler extends kickpress_api_handler {
	public function __construct( $api ) {
		parent::__construct( $api );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		if ( ! empty( $this->_api->params['apicode'] ) ) {
			wp_deregister_script( 'maps' );
			wp_register_script( 'maps', 'http://maps.googleapis.com/maps/api/js?sensor=false&key=' . $this->_api->params['apicode']);
			wp_enqueue_script( 'maps' );
		}
	}

	public function get_post_type_options() {
		$post_type_options = array(
			'apicode' => array(
				'caption' => 'Google Maps API Code',
				'name'    => '_apicode',
				'type'    => 'text',
				'class'   => 'large-text',
				'default' => '',
				'notes'   => 'Go to <a href="http://code.google.com/apis/maps/signup.html" target="_blank">http://code.google.com/apis/maps/signup.html</a> to get a new API key.'
			)
		);

		return $post_type_options;
	}

	public function get_custom_fields() {
		$custom_fields = array(
			'address' => array(
				'name'    => '_address',
				'caption' => 'Street Address',
				'type'    => 'text'
			),
			'address2' => array(
				'name'    => '_address2',
				'caption' => 'Street Address, Line #2',
				'type'    => 'text'
			),
			'city' => array(
				'name'    => '_city',
				'caption' => 'City',
				'type'    => 'text'
			),
			'state' => array(
				'name'    => '_state',
				'caption' => 'State/Province',
				'type'    => 'text'
			),
			'zipcode' => array(
				'name'    => '_zipcode',
				'caption' => 'Zip/Postal Code',
				'type'    => 'text'
			),
			'country' => array(
				'name'    => '_country',
				'caption' => 'Country',
				'type'    => 'countries',
				'list'    => 'countries'
			),
			'latitude' => array(
				'name'     => '_latitude',
				'type'     => 'static_display',
				'add_type' => 'none'
			),
			'longitude' => array(
				'name'     => '_longitude',
				'type'     => 'static_display',
				'add_type' => 'none'
			),
			'timezone' => array(
				'name'     => '_timezone',
				'caption'  => 'Time Zone',
				'type'     => 'static_display',
				'required' => true,
				'add_type' => 'none'
			)
		);

		return $custom_fields;
	}

	public function update_meta_fields( $post, $post_data, $form_data ) {
		$address_parts = array();

		if ( $post_data['_address']  != '' ) $address_parts[] = $post_data['_address'];
		if ( $post_data['_address2'] != '' ) $address_parts[] = $post_data['_address2'];
		if ( $post_data['_city']     != '' ) $address_parts[] = $post_data['_city'];
		if ( $post_data['_state']    != '' ) $address_parts[] = $post_data['_state'];
		if ( $post_data['_zipcode']  != '' ) $address_parts[] = $post_data['_zipcode'];
		if ( ! is_array( $post_data['_country'] ) ) // ! in_array( $post_data['_country'], '', 'all' ) )
			$address_parts[] = $post_data['_country'];

		$address = implode( ' ', $address_parts );

		if ( $location = $this->location_lookup( $address ) ) {
			$post_data['_latitude']  = $location['latitude'];
			$post_data['_longitude'] = $location['longitude'];
			$post_data['_timezone']  = $location['timezone'];

			if ( method_exists( $this->_api, 'update_location' ) )
				$post_data = $this->_api->update_location( $post, $location, $post_data );
		}

		return $post_data;
	}

	public function location_lookup( $address ) {
		global $kickpress;

		$request = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=" . urlencode( $address );

		$response = $this->do_curl( $request );

		$json = json_decode( $response );

		if ( $json->status == 'OK' ) {
			$result = $json->results[0];

			$longitude = $result->geometry->location->lng;
			$latitude = $result->geometry->location->lat;
			$address = $result->formatted_address;

			return array(
				'longitude' => $longitude,
				'latitude'  => $latitude,
				'timezone'  => '',
				'address'   => $address
			);
		} else {
			return false;
		}
	}

	public function radius_lookup( $latitude, $longitude ) {
		global $wpdb;

		$sql = "SELECT posts.*,
			locations_latitude.meta_value AS latitude,
			locations_longitude.meta_value AS longitude,
			locations_service_radius.meta_value AS service_radius,
			(
				3959 * acos(
					sin(radians('$latitude')) * sin(radians(locations_latitude.meta_value)) +
					cos(radians('$latitude')) * cos(radians(locations_latitude.meta_value)) *
					cos(radians('$longitude') - radians(locations_longitude.meta_value))
				)
			) AS distance
		FROM {$wpdb->posts} posts
			INNER JOIN {$wpdb->postmeta} locations_latitude
				ON posts.ID = locations_latitude.post_id
				AND locations_latitude.meta_key = '_latitude'
			INNER JOIN {$wpdb->postmeta} locations_longitude
				ON posts.ID = locations_longitude.post_id
				AND locations_longitude.meta_key = '_longitude'
			INNER JOIN {$wpdb->postmeta} locations_service_radius
				ON posts.ID = locations_service_radius.post_id
				AND locations_service_radius.meta_key = '_service_radius'
		WHERE posts.post_type = 'radio-stations'
			AND posts.post_status = 'publish'
		HAVING distance <= service_radius
		ORDER BY distance ASC";

		$results = $wpdb->get_results($sql);

		return $results;
	}

	public function add_points_to_map( $addresses, $show_message = false, $current_location = array(), $zoom_level = null ) {
		$api = kickpress_init_api( get_post_type() );

		if ( ! empty( $current_location ) ) {
			$current_marker = sprintf( '
				var MyPosition = new google.maps.LatLng(%1$s, %2$s);

				var MyMarker = new google.maps.Marker({
					map: map,
					icon: "http://maps.google.com/intl/en_us/mapfiles/ms/micons/green.png",
					position: MyPosition
				});

				var MyMessage = "%3$s";

				bounds.extend(MyPosition);',
				$current_location['latitude'],
				$current_location['longitude'],
				$current_location['full_address']
			);
		} else {
			$current_marker = '';
		}

		$markers = array();

		$address_list  = array();

		foreach ( $addresses as $key => $value ) {
			$meta = $this->_api->get_custom_field_values( $value->ID );

			$title   = $this->get_point_title( $value );
			$descrip = $this->get_point_description( $value );

			if ( ! empty ( $meta['latitude'] ) && ! empty( $meta['longitude'] ) ) {
				$markers[] = sprintf( '
					createMarker(%1$s, %2$s, %3$s, "%4$s", "%5$s");',
					$meta['latitude'],
					$meta['longitude'],
					$key,
					esc_attr( $title ),
					str_replace( array( "\r\n", "\n", "\r" ), '<br>', esc_attr( $descrip ) )
				);
			}
		}

		if ( is_null( $zoom_level ) )
			$zoom_level = '(map.getBoundsZoomLevel(bounds) - 1)';
		else
			$zoom_level = intval( $zoom_level );

		$html .= sprintf( '
			<script type="text/javascript">
				var baseAnchor;
				var baseWindow;
				var bounds;
				var map;

				jQuery(document).ready(function () {
					baseAnchor = new google.maps.Point(0, 0);

					bounds = new google.maps.LatLngBounds();

					%1$s
					%2$s

					map.fitBounds(bounds);
				});
			</script>
			<div id="map_canvas" style="width: %3$spx; height: %4$spx;"></div>',
			$current_marker,
			implode( '', $markers ),
			$this->_api->params['map_width'],
			$this->_api->params['map_height'],
			$zoom_level
		);

		return $html;
	}

	public function get_point_title ( $post ) {
		return $post->post_title;
	}

	public function get_point_description( $post ) {
		$values = $this->_api->get_custom_field_values( $post->ID );

		$address = $values['address'];

		if ( ! empty( $values['address2'] ) ) $address .= PHP_EOL . $values['address2'];
		if ( ! empty( $values['city'] ) ) $address .= PHP_EOL . $values['city'];
		if ( ! empty( $values['state'] ) ) $address .= ', ' . $values['state'];
		if ( ! empty( $values['zipcode'] ) ) $address .= ' ' . $values['zipcode'];

		return $address;
	}
}

?>