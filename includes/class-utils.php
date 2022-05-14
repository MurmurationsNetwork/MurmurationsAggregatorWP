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
	public static function e( $output, $escape_format = 'html' ) {
		if ( 'html' === $escape_format ) {
			echo wp_kses_post( $output );
		} elseif ( 'no-html' === $escape_format ) {
			echo esc_html( $output ); // This function has a very confusing name.
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
		/*
		Nonce check for incoming data has already been performed before this is called,
		so we need to ignore PHPCS's opinions about this.
		*/
	  // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( 'POST' === $method && isset( $_POST[ $param ] ) ) {
			if ( is_array( $_POST[ $param ] ) ) {
				return filter_input( INPUT_POST, $param, $filter, FILTER_REQUIRE_ARRAY );
			} else {
				return filter_input( INPUT_POST, $param, $filter );
			}
		} elseif ( 'GET' === $method && isset( $_GET[ $param ] ) ) {
			if ( is_array( $_GET[ $param ] ) ) {
				return filter_input( INPUT_GET, $param, $filter, FILTER_REQUIRE_ARRAY );
			} else {
				return filter_input( INPUT_GET, $param, $filter );
			}
		} else {
			return false;
		}
		// phpcs:enable
	}

	/**
	 * Get the label for an enum value
	 * 
	 * @param  array  $enums array of enum values.
	 * @param  array  $labels array of enum labels.
	 * @param  mixed $value Enum value to match
	 * @return mixed Enum label if found, otherwise input value.
	 */
	public static function enum_label( array $enums, array $labels, $value ) {
		$labels = self::enum_labels( $enums, $labels );
		if ( $labels ) {
			if ( isset( $labels[ $value ] ) ) {
				return $labels[ $value ];
			} else {
				error( "Enum label not found ", "notice" );
				return $value;
			}
		} else {
			error( "Enum values and labels don't match", "notice" );
			return $value;
		}
	}
	public static function enum_labels( array $enums, array $labels ) {
		if ( count( $enums ) === count( $labels ) ) {
			return array_combine( $enums, $labels );
		} else {
			return false;
		}
	}
}
