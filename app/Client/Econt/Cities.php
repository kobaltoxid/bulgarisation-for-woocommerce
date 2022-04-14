<?php
namespace Woo_BG\Client\Econt;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Cities {
	const CITIES_ENDPOINT = 'Nomenclatures/NomenclaturesService.getCities.json';

	private $cities = [];
	private $container;

	public function __construct( $container ) {
		$this->container = $container;
	}

	protected function load_cities( $country_code = 'BG' ) {
		if ( ! is_dir( $this->container[ Client::ECONT ]::CACHE_FOLDER ) ) {
			wp_mkdir_p( $this->container[ Client::ECONT ]::CACHE_FOLDER );
		}

		$cities_file = $this->container[ Client::ECONT ]::CACHE_FOLDER . 'cities-' . $country_code . '.json';
		$cities = '';

		if ( file_exists( $cities_file ) ) {
			$cities = file_get_contents( $cities_file );
		}

		if ( !$cities ) {
			$api_call = $this->container[ Client::ECONT ]->api_call( self::CITIES_ENDPOINT, array( 'countryCode' => $country_code ) );

			if ( is_array( $api_call ) ) {
				if ( $this->container[ Client::ECONT ]::validate_access( $api_call ) ) {
					if ( !empty( $api_call['cities'] ) ) {
						$cities = json_encode( $api_call );
						
						file_put_contents( $cities_file, $cities );
					}
				}
			}
		}

		$cities = json_decode( $cities, 1 );

		$this->set_cities( $cities, $country_code );

		return $cities;
	}

	public function get_formatted_cities( $country_code = 'BG' ) {
		$cities = $this->container[ Client::ECONT_CITIES ]->get_cities( $country_code );
		$formatted = [];

		foreach ( $cities['cities'] as $city ) {
			$label = $city['name'];

			if ( $city['regionName'] && $city['regionName'] !== $city['name'] ) {
				$label = $city['regionName'] . " - " . $label;
			}

			if ( $city['name'] && $city['regionName'] ) {
				$formatted[ 'cityID-' . $city['id'] ] = array(
					'id' => 'cityID-' . $city['id'],
					'label' => $label,
				);
			}
		}

		uasort( $formatted, function( $a, $b ) {
			return strcmp( $a["label"], $b["label"] );
		} );

		return $formatted;
	}

	//Getters
	public function get_cities( $country_code = 'BG' ) {
		if ( empty( $this->cities[ $country_code ] ) ) {
			$this->load_cities( $country_code );
		}

		return $this->cities[ $country_code ];
	}

	public function get_cities_by_region( $region, $country_code = 'BG' ) {
		$cities = array_filter( $this->get_cities( $country_code )['cities'], function( $city ) use ( $region ) {
			if ( 
				mb_strtolower( $city['regionName'] ) === mb_strtolower( $region ) || 
				mb_strtolower( $city['regionNameEn'] ) === mb_strtolower( $region )
			) {
				return true;
			}
		} );

		return array_values( $cities );
	}

	public function get_state_name( $state_code, $country_code = 'BG' ) {
		$countries = new \WC_Countries();
		$states = $countries->get_states( $country_code );

		return $states[ $state_code ];
	}

	//Setters
	private function set_cities( $cities, $country_code = 'BG' ) {
		$this->cities[ $country_code ] = $cities;
	}
}
