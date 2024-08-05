<?php


namespace php_wp_console;

use PhpConsole;

defined( 'ABSPATH' ) or exit;


class Plugin {


	/** @var string plugin version */
	CONST VERSION = '1.6.0';

	/** @var string plugin ID */
	CONST ID = 'php_wp_console';

	/** @var string plugin name */
	CONST NAME = ' PHP WP Console';


	/** @var PhpConsole\Connector instance */
	public $connector;


	
	public function __construct() {

		@error_reporting( E_ALL );

		foreach ( [ 'WP_DEBUG',	'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY', ] as $wp_debug_constant ) {
			if ( ! defined( $wp_debug_constant ) ) {
				define ( $wp_debug_constant, true );
			}
		}

		// handle translations
		add_action( 'plugins_loaded', static function() {
			load_plugin_textdomain(
				'php_wp_console',
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);
		} );

		if ( class_exists( 'PhpConsole\Connector' ) ) {
			// connect to PHP Console
			add_action( 'init',      [ $this, 'connect' ], -1000 );
			// delay further PHP Console initialisation to have more context during Remote PHP execution
			add_action( 'wp_loaded', [ $this, 'init' ], -1000 );
		}

		// load admin
		if ( is_admin() ) {
			new Admin();
		}
	}


	
	public function connect() {

		// workaround for avoiding headers already sent warnings
		@error_reporting( E_ALL & ~E_WARNING );

		if ( ! @session_id() ) {
			@session_start();
		}

		$connected = true;

		if ( ! $this->connector instanceof PhpConsole\Connector ) {
			try {
				$this->connector = PhpConsole\Connector::getInstance();
			} catch ( \Exception $e ) {
				$connected = false;
			}
		}

		// restore error reporting
		@error_reporting( E_ALL );

		// apply PHP Console options
		if ( $connected ) {
			$this->apply_options();
		}
	}


	private function apply_options() {

		// bail out if not connected yet to PHP Console
		if ( ! $this->connector instanceof PhpConsole\Connector ) {
			return;
		}

		// apply 'register' option to PHP Console...
		if ( Settings::should_register_pc_class() && ! class_exists( 'PC', false ) ) {
			// ...only if PC not registered yet
			try {
				PhpConsole\Helper::register();
			} catch( \Exception $e ) {
				$this->print_notice_exception( $e );
			}
		}

		// apply 'stack' option to PHP Console
		if ( Settings::should_show_call_stack() ) {
			$this->connector->getDebugDispatcher()->detectTraceAndSource = true;
		}

		// apply 'short' option to PHP Console
		if ( Settings::should_use_short_path_names() ) {
			try {
				$this->connector->setSourcesBasePath( $_SERVER['DOCUMENT_ROOT'] );
			} catch ( \Exception $e ) {
				$this->print_notice_exception( $e );
			}
		}
	}


	
	public function init() {

		// bail if no password is set to connect with PHP Console
		if ( ! Settings::has_eval_terminal_password() ) {
			return;
		}

		// selectively remove slashes added by WordPress as expected by PHP Console
		if ( array_key_exists( PhpConsole\Connector::POST_VAR_NAME, $_POST ) ) {
			$_POST[ PhpConsole\Connector::POST_VAR_NAME ] = stripslashes_deep( $_POST[ PhpConsole\Connector::POST_VAR_NAME ] );
		}

		// get PHP Console instance if wasn't set yet
		if ( ! $this->connector instanceof PhpConsole\Connector ) {

			// workaround for avoiding headers already sent warnings
			@error_reporting( E_ALL & ~E_WARNING );

			try {
				$this->connector = PhpConsole\Connector::getInstance();
				$connected       = true;
			} catch ( \Exception $e ) {
				$connected       = false;
			}

			// restore error reporting
			@error_reporting( E_ALL );

			if ( ! $connected ) {
				return;
			}
		}

		// set PHP Console password
		try {
			$this->connector->setPassword( Settings::get_eval_terminal_password() );
		} catch ( \Exception $e ) {
			$this->print_notice_exception( $e );
		}

		// get PHP Console handler instance
		$handler = PhpConsole\Handler::getInstance();

		if ( true !== PhpConsole\Handler::getInstance()->isStarted() ) {
			try {
				$handler->start();
			} catch( \Exception $e ) {
				$this->print_notice_exception( $e );
				return;
			}
		}

		// enable SSL-only mode
		if ( Settings::should_use_ssl_only() ) {
			$this->connector->enableSslOnlyMode();
		}

		// restrict IP addresses
		$allowedIpMasks = Settings::get_allowed_ip_masks();

		if ( count( $allowedIpMasks ) > 0 ) {
			$this->connector->setAllowedIpMasks( $allowedIpMasks );
		}

		$evalProvider = $this->connector->getEvalDispatcher()->getEvalProvider();

		try {
			$evalProvider->addSharedVar( 'uri', $_SERVER['REQUEST_URI'] );
		} catch ( \Exception $e ) {
			$this->print_notice_exception( $e );
		}

		try {
			$evalProvider->addSharedVarReference( 'post', $_POST );
		} catch ( \Exception $e ) {
			$this->print_notice_exception( $e );
		}

		$openBaseDirs = [ ABSPATH, get_template_directory() ];

		try {
			$evalProvider->addSharedVarReference( 'dirs', $openBaseDirs );
		} catch ( \Exception $e ) {
			$this->print_notice_exception( $e );
		}

		$evalProvider->setOpenBaseDirs( $openBaseDirs );

		try {
			$this->connector->startEvalRequestsListener();
		} catch ( \Exception $e ) {
			$this->print_notice_exception( $e );
		}
	}


	private function print_notice_exception( \Exception $e ) {

		add_action( 'admin_notices', static function() use ( $e ) {
			?>
			<div class="error">
				<p><?php printf( '%1$s: %2$s', self::NAME, $e->getMessage() ); ?></p>
			</div>
			<?php
		} );
	}



	public static function get_plugin_path() {

		return untrailingslashit( dirname( __DIR__ ) );
	}



	public static function get_plugin_vendor_path() {

		return self::get_plugin_path() . '/vendor';
	}


	public static function get_plugin_page_url() {

		return 'https://wordpress.org/support/plugin/wp-php-console/';
	}



	public static function get_reviews_page_url() {

		return 'https://wordpress.org/support/plugin/wp-php-console/reviews/';
	}


	public static function get_support_page_url() {

		return 'https://wordpress.org/support/plugin/wp-php-console/';
	}


	public static function get_wp_php_console_repository_url() {

		return 'https://github.com/AbhiFutureTech/php-wp-console';
	}

}
