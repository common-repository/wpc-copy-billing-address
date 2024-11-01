<?php
/**
 * Plugin Name: WPC Copy Billing Address for WooCommerce
 * Plugin URI: https://wpclever.net/
 * Description: Help buyers copy the whole billing address to the shipping address with one click.
 * Version: 1.1.7
 * Author: WPClever
 * Author URI: https://wpclever.net
 * Text Domain: wpc-copy-billing-address
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * Requires at least: 4.0
 * Tested up to: 6.6
 * WC requires at least: 3.0
 * WC tested up to: 9.1
 */

defined( 'ABSPATH' ) || exit;

! defined( 'WPCCB_VERSION' ) && define( 'WPCCB_VERSION', '1.1.7' );
! defined( 'WPCCB_LITE' ) && define( 'WPCCB_LITE', __FILE__ );
! defined( 'WPCCB_FILE' ) && define( 'WPCCB_FILE', __FILE__ );
! defined( 'WPCCB_URI' ) && define( 'WPCCB_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCCB_REVIEWS' ) && define( 'WPCCB_REVIEWS', 'https://wordpress.org/support/plugin/wpc-copy-billing-address/reviews/?filter=5' );
! defined( 'WPCCB_CHANGELOG' ) && define( 'WPCCB_CHANGELOG', 'https://wordpress.org/plugins/wpc-copy-billing-address/#developers' );
! defined( 'WPCCB_DISCUSSION' ) && define( 'WPCCB_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-copy-billing-address/' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCCB_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpccb_init' ) ) {
	add_action( 'plugins_loaded', 'wpccb_init', 11 );

	function wpccb_init() {
		// load text-domain
		load_plugin_textdomain( 'wpc-copy-billing-address', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpccb_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'WPCleverWpccb' ) ) {
			class WPCleverWpccb {
				protected static $settings = [];
				protected static $instance = null;

				public static function instance() {
					if ( is_null( self::$instance ) ) {
						self::$instance = new self();
					}

					return self::$instance;
				}

				function __construct() {
					self::$settings = (array) get_option( 'wpccb_settings', [] );

					add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 99 );
					add_action( 'woocommerce_before_checkout_shipping_form', [ $this, 'add_button' ], 99 );
					add_action( 'admin_init', [ $this, 'register_settings' ] );
					add_action( 'admin_menu', [ $this, 'admin_menu' ] );
					add_filter( 'plugin_action_links', [ $this, 'action_links' ], 99, 2 );
					add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 99, 2 );
				}

				public static function get_settings() {
					return apply_filters( 'wpccb_get_settings', self::$settings );
				}

				public static function get_setting( $name, $default = false ) {
					if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
						$setting = self::$settings[ $name ];
					} else {
						$setting = get_option( 'wpccb_' . $name, $default );
					}

					return apply_filters( 'wpccb_get_setting', $setting, $name, $default );
				}

				function enqueue_scripts() {
					$confirm_message = self::get_setting( 'confirm_message' );

					if ( empty( $confirm_message ) ) {
						$confirm_message = esc_html__( 'All shipping information will be copied from existing billing details, do you wish to continue?', 'wpc-copy-billing-address' );
					}

					wp_enqueue_style( 'wpccb-frontend', WPCCB_URI . 'assets/css/frontend.css', false, WPCCB_VERSION );
					wp_enqueue_script( 'wpccb-frontend', WPCCB_URI . 'assets/js/frontend.js', [ 'jquery' ], WPCCB_VERSION, true );
					wp_localize_script( 'wpccb-frontend', 'wpccb_vars', [
							'confirm'         => self::get_setting( 'confirm', 'no' ),
							'confirm_message' => esc_html( $confirm_message ),
						]
					);
				}

				function add_button() {
					$button_text = self::get_setting( 'button_text' );

					if ( empty( $button_text ) ) {
						$button_text = esc_html__( 'Copy billing address', 'wpc-copy-billing-address' );
					}

					echo apply_filters( 'wpccb_button', '<div class="wpccb"><a class="wpccb_copy" href="javascript:void(0);">☷ ' . esc_html( apply_filters( 'wpccb_button_text', $button_text ) ) . '</a></div>' );
				}

				function register_settings() {
					register_setting( 'wpccb_settings', 'wpccb_settings' );
				}

				function admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Copy Billing Address', 'wpc-copy-billing-address' ), esc_html__( 'Copy Billing Address', 'wpc-copy-billing-address' ), 'manage_options', 'wpclever-wpccb', [
						$this,
						'admin_menu_content'
					] );
				}

				function admin_menu_content() {
					$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Copy Billing Address', 'wpc-copy-billing-address' ) . ' ' . esc_html( WPCCB_VERSION ); ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-copy-billing-address' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WPCCB_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'wpc-copy-billing-address' ); ?></a> |
                                <a href="<?php echo esc_url( WPCCB_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'wpc-copy-billing-address' ); ?></a> |
                                <a href="<?php echo esc_url( WPCCB_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'wpc-copy-billing-address' ); ?></a>
                            </p>
                        </div>
						<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Settings updated.', 'wpc-copy-billing-address' ); ?></p>
                            </div>
						<?php } ?>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpccb&tab=settings' ) ); ?>" class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Settings', 'wpc-copy-billing-address' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'wpc-copy-billing-address' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'settings' ) {
								$button_text     = self::get_setting( 'button_text' );
								$confirm         = self::get_setting( 'confirm', 'no' );
								$confirm_message = self::get_setting( 'confirm_message' );
								?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th><?php esc_html_e( 'General', 'wpc-copy-billing-address' ); ?></th>
                                            <td><?php esc_html_e( 'General settings.', 'wpc-copy-billing-address' ); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Button text', 'wpc-copy-billing-address' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="text" class="regular-text" name="wpccb_settings[button_text]" value="<?php echo esc_attr( $button_text ); ?>" placeholder="<?php esc_html_e( 'Copy billing address', 'wpc-copy-billing-address' ); ?>"/>
                                                </label>
                                                <span class="description"><?php esc_html_e( 'Leave blank to use the default text or its equivalent translation in multiple languages.', 'wpc-copy-billing-address' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Confirmation', 'wpc-copy-billing-address' ); ?></th>
                                            <td>
                                                <label> <select name="wpccb_settings[confirm]">
                                                        <option value="yes" <?php selected( $confirm, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-copy-billing-address' ); ?></option>
                                                        <option value="no" <?php selected( $confirm, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-copy-billing-address' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Enable confirmation alert before changing.', 'wpc-copy-billing-address' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Confirmation message', 'wpc-copy-billing-address' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="text" class="large-text" name="wpccb_settings[confirm_message]" value="<?php echo esc_attr( $confirm_message ); ?>" placeholder="<?php esc_html_e( 'All shipping information will be copied from existing billing details, do you wish to continue?', 'wpc-copy-billing-address' ); ?>"/>
                                                </label>
                                                <span class="description"><?php esc_html_e( 'Leave blank to use the default text or its equivalent translation in multiple languages.', 'wpc-copy-billing-address' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'wpccb_settings' ); ?><?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } ?>
                        </div><!-- /.wpclever_settings_page_content -->
                        <div class="wpclever_settings_page_suggestion">
                            <div class="wpclever_settings_page_suggestion_label">
                                <span class="dashicons dashicons-yes-alt"></span> Suggestion
                            </div>
                            <div class="wpclever_settings_page_suggestion_content">
                                <div>
                                    To display custom engaging real-time messages on any wished positions, please install
                                    <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart Messages</a> plugin. It's free!
                                </div>
                                <div>
                                    Wanna save your precious time working on variations? Try our brand-new free plugin
                                    <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC Variation Bulk Editor</a> and
                                    <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC Variation Duplicator</a>.
                                </div>
                            </div>
                        </div>
                    </div>
					<?php
				}

				function action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpccb&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-copy-billing-address' ) . '</a>';
						array_unshift( $links, $settings );
					}

					return (array) $links;
				}

				function row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = [
							'support' => '<a href="' . esc_url( WPCCB_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-copy-billing-address' ) . '</a>',
						];

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}
			}

			return WPCleverWpccb::instance();
		}

		return null;
	}
}

if ( ! function_exists( 'wpccb_notice_wc' ) ) {
	function wpccb_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Copy Billing Address</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
