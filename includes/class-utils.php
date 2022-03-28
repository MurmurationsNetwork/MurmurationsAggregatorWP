<?php
/**
 * Utility class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class for utility functions and WP wrappers
 */
class Utils {
	/**
	 * Echo something, using WP escaping functions
	 *
	 * @param string $output the string to print.
	 * @param string $escape_format what WP escaping function to use.
	 * Options are html, url, js, and attr (for attributes of HTML elements).
	 */
	public static function e( string $output, $escape_format = 'html' ) {
		if ( 'html' === $escape_format ) {
			echo esc_html( $output );
		} elseif ( 'url' === $escape_format ) {
			echo esc_url( $output );
		} elseif ( 'attr' === $escape_format ) {
			echo esc_attr( $output );
		} elseif ( 'js' === $escape_format ) {
			echo esc_js( $output );
		}
	}
	/**
	 * Sanitize and return an input value
	 *
	 * @param string $param the name of the parmater to get.
	 * @param string $method GET or POST.
	 * @param string $filter filter to use. See https://www.php.net/manual/en/filter.filters.php.
	 * @return mixed the value of the input parmater if present.
	 */
	public static function input( $param, $method = 'POST', $filter = FILTER_DEFAULT ) {
		if ( 'POST' === $method ) {
			return filter_input( FILTER_POST, $param, $filter );
		} elseif ( 'GET' === $method ) {
			return filter_input( FILTER_GET, $param, $filter );
		}
	}
}
