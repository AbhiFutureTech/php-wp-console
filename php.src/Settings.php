<?php


namespace PHP_WP_Console;

defined( 'ABSPATH' ) or exit;


class Settings {


	/** @var array settings options */
	private static $options = [];


	
	public static function get_settings_key() {

		return str_replace( '-', '_', Plugin::ID );
	}


	
	public static function get_settings() {

		if ( empty( self::$options ) ) {

			self::$options = wp_parse_args(
				(array) get_option( self::get_settings_key(), [] ),
				[
					'ip'       => '',
					'password' => '',
					'register' => false,
					'short'    => false,
					'ssl'      => false,
					'stack'    => false,
				]
			);
		}

		return self::$options;
	}


	public static function get_allowed_ip_masks() {

		$allowed_ips = self::get_settings()['ip'];

		return ! empty( $allowed_ips['ip'] ) ? explode( ',', $allowed_ips['ip'] ) : [];
	}


	
	public static function get_eval_terminal_password() {

		return self::get_settings()['password'];
	}


	public static function has_eval_terminal_password() {

		$password = self::get_eval_terminal_password();

		return is_string( $password ) && '' !== trim( $password );
	}


	
	public static function should_register_pc_class() {

		return ! empty( self::get_settings()['register'] );
	}


	
	public static function should_use_ssl_only() {

		return ! empty( self::get_settings()['ssl'] );
	}

	public static function should_show_call_stack() {

		return ! empty( self::get_settings()['stack'] );
	}



	public static function should_use_short_path_names() {

		return ! empty( self::get_settings()['short'] );
	}


}
