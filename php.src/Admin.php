<?php

namespace PHP_WP_Console;

defined( 'ABSPATH' ) or exit;


class Admin {



	public function __construct() {

		self::add_plugin_page_row_action_links();
		self::output_admin_notices();

		// init settings page
		if ( ! defined( 'DOING_AJAX' ) ) {
			new Admin\SettingsPage();
		}
	}


	private static function output_admin_notices() {

		// display admin notice and abort if no password has been set
		add_action( 'all_admin_notices', static function() {

			if ( ! Settings::has_eval_terminal_password() ) :

				?>
				<div class="notice notice-warning">
					<p>
						<?php printf(
							/* translators: Placeholders: %1$s - WP PHP Console name, %2$s - opening HTML <a> link tag; %3$s closing HTML </a> link tag */
							__( '%1$s: Please remember to %2$sset a password%3$s if you want to enable the terminal.', 'php-wp-console' ),
							'<strong>' . Plugin::NAME . '</strong>',
							'<a href="' . esc_url( admin_url( 'options-general.php?page=php-wp-console' ) ) .'">',
							'</a>'
						); ?>
					</p>
				</div>
				<?php

			endif;

		}, -1000 );
	}

atic function add_plugin_page_row_action_links() {

		add_filter( 'plugin_action_links_php-wp-console/php-wp-console.php', static function( $actions ) {

			return array_merge( [
				'<a href="' . esc_url( admin_url( 'options-general.php?page=' . str_replace( '-', '_', Plugin::ID ) ) ) . '">' . esc_html__( 'Settings', 'wp-php-console' ) . '</a>',
				'<a href="' . esc_url( Plugin::get_php_wp_console_repository_url() ) . '">' . esc_html__( 'GitHub', 'php-wp-console' ) . '</a>',
				'<a href="' . esc_url( Plugin::get_support_page_url() ) . '">' . esc_html__( 'Support', 'php-wp-console' ) . '</a>',
				'<a href="' . esc_url( Plugin::get_reviews_page_url() ) . '">' . esc_html__( 'Review', 'php-wp-console' ) . '</a>',
			], $actions );

		} );
	}


}
