<?php

/**
 * TaxJar Nexus
 *
 * @package  WC_Taxjar_Integration
 * @author   TaxJar
 */

if ( ! defined( 'ABSPATH' ) )  {
  exit; // Prevent direct access to script
}

class WC_Taxjar_Nexus {

  public function __construct( $integration ) {
    $this->integration = $integration;
    $this->nexus = $this->get_or_update_cached_nexus();
  }

  public function get_form_settings_field( ) {
    $desc_text = '';

    $desc_text .= '<h3>Nexus Information</h3>';

    if( count($this->nexus) > 0 ) {
      $desc_text .= '<p>Sales tax will be calculated on orders delivered into the following regions: </p>';

      foreach ($this->nexus as $key => $nexus) {
        $desc_text .= '<br>';
        if ( isset( $nexus->region ) && isset ( $nexus->country ) ) {
          $desc_text .= sprintf( "%s, %s", $nexus->region, $nexus->country );
        } else {
          if ( isset ( $nexus->country ) ) {
            $desc_text .= $nexus->country;
          }
        }
      }

      $desc_text .= "<br><br><a href='" . $this->integration->regions_uri . "' target='_blank'>Add or update nexus locations</a>";
    } else {
      $desc_text .= "<p>TaxJar needs your business locations in order to calculate sales tax properly. Please add them <a href='" . $this->integration->regions_uri . "' target='_blank'>here</a>.<p>";
    }

    $desc_text .= "<p><a href='#' class='js-wc-taxjar-sync-nexus-addresses'>Sync Nexus Addresses</a></p>";

    return array(
      'title'             => '',
      'type'              => 'hidden',
      'description'       => $desc_text
    );
  }

  public function has_nexus_check( $country, $state = nil ) {
    foreach ( $this->get_or_update_cached_nexus() as $key => $nexus ) {
      if ( $country == 'US' && isset( $nexus->region_code ) && isset ( $nexus->country_code ) ) {
        if ($country == $nexus->country_code && $state == $nexus->region_code) {
          return true;
        }
      } else {
        if ( isset ( $nexus->country_code ) && $country == $nexus->country_code ) {
          return true;
        }
      }
    }
    return false;
  }

  public function get_or_update_cached_nexus( $force_update = false ) {
    $nexus_list = get_transient( 'wc_taxjar_nexus_list' );

    if ( $force_update || $nexus_list === false || count($nexus_list) == 0) {
    	$nexus_list = $this->get_nexus_from_api();
    	set_transient( 'wc_taxjar_nexus_list', $nexus_list, 1 * DAY_IN_SECONDS);
      $this->integration->_log( ':::: Nexus addresses updated ::::' );
    } else {
      $this->integration->_log( ':::: Using nexus addresses from cache ::::' );
    }
    return $nexus_list;
  }

  private function get_nexus_from_api( ) {
    $url = $this->integration->uri . 'nexus/regions';
    $this->integration->_log( ':::: TaxJar getting nexus list from API ::::' );

    $response = wp_remote_get( $url, array(
      'headers' =>    array(
                        'Authorization' => 'Token token="' . $this->integration->post_or_setting('api_token') .'"',
                        'Content-Type' => 'application/x-www-form-urlencoded'
                      ),
      'user-agent' => $this->integration->ua
    ) );

    if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
      $body = json_decode( $response['body'] );
      return $body->regions;
    }

    return array();
  }

} // WC_Taxjar_Nexus
