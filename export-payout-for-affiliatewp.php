<?php
/**
 * Plugin Name: Export Payout For AffiliateWp
 * Plugin URI: https://github.com/fazlebarisn/export-payout-for-affiliatewp
 * Description: Adds an export button on the payouts tab for the AffiliateWP plugin to export payouts in Excel, PDF, or CSV.
 * Version: 1.0.0
 * Author: Fazle Bari
 * Author URI: https://github.com/fazlebarisn
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: export-payout-for-affiliatewp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Export_Payout_For_AffiliateWp {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'render_export_modal' ) );
        
        // AJAX handlers for getting payouts data
        add_action( 'wp_ajax_epfaw_get_payouts', array( $this, 'ajax_get_payouts' ) );
    }

    public function enqueue_scripts() {
        // Enqueue only if we are on a page with affiliate area shortcode or if we want to be safe, just enqueue on all pages but we can check if function affwp_is_affiliate_dashboard exists.
        // To be safe and compatible with all themes, we'll enqueue the script and let JS check if the payouts table exists.
        
        wp_enqueue_style(
            'epfaw-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            array(),
            '1.0.0'
        );

        // Include SheetJS for Excel
        wp_enqueue_script( 'xlsx', 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', array(), '0.18.5', true );
        // Include jsPDF for PDF
        wp_enqueue_script( 'jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true );
        // Include jsPDF AutoTable for PDF tables
        wp_enqueue_script( 'jspdf-autotable', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js', array('jspdf'), '3.5.28', true );

        wp_enqueue_script(
            'epfaw-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/script.js',
            array('jquery', 'xlsx', 'jspdf', 'jspdf-autotable'),
            '1.0.0',
            true
        );

        wp_localize_script( 'epfaw-script', 'epfaw_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'epfaw_nonce' ),
        ) );
    }

    public function render_export_modal() {
        // We only need to render the modal if user is logged in
        if ( ! is_user_logged_in() ) {
            return;
        }
        ?>
        <div id="epfaw-modal-overlay" style="display:none;">
            <div id="epfaw-modal">
                <div class="epfaw-modal-header">
                    <h3>Export Payouts</h3>
                    <button id="epfaw-close-modal">&times;</button>
                </div>
                <div class="epfaw-modal-body">
                    <form id="epfaw-export-form">
                        <div class="epfaw-form-group">
                            <label for="epfaw-duration">Duration</label>
                            <select id="epfaw-duration" name="duration">
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        <div id="epfaw-custom-dates" style="display:none;">
                            <div class="epfaw-form-group">
                                <label for="epfaw-date-from">Date From</label>
                                <input type="date" id="epfaw-date-from" name="date_from">
                            </div>
                            <div class="epfaw-form-group">
                                <label for="epfaw-date-to">Date To</label>
                                <input type="date" id="epfaw-date-to" name="date_to">
                            </div>
                        </div>

                        <div class="epfaw-form-group">
                            <label for="epfaw-file-type">File Type</label>
                            <select id="epfaw-file-type" name="file_type">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>

                        <button type="submit" id="epfaw-export-btn" class="button">Export</button>
                        <span id="epfaw-loading" style="display:none;">Exporting...</span>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_get_payouts() {
        check_ajax_referer( 'epfaw_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in' );
        }

        if ( ! function_exists( 'affwp_get_affiliate_id' ) ) {
            wp_send_json_error( 'AffiliateWP not active' );
        }

        $affiliate_id = affwp_get_affiliate_id( get_current_user_id() );
        if ( ! $affiliate_id ) {
            wp_send_json_error( 'User is not an affiliate' );
        }

        $duration   = isset( $_POST['duration'] ) ? sanitize_text_field( wp_unslash( $_POST['duration'] ) ) : 'this_month';
        $date_from  = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
        $date_to    = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';

        $start_date = '';
        $end_date   = '';

        if ( $duration === 'this_month' ) {
            $start_date = gmdate( 'Y-m-01 00:00:00' );
            $end_date   = gmdate( 'Y-m-t 23:59:59' );
        } elseif ( $duration === 'last_month' ) {
            $start_date = gmdate( 'Y-m-01 00:00:00', strtotime( 'first day of last month' ) );
            $end_date   = gmdate( 'Y-m-t 23:59:59', strtotime( 'last day of last month' ) );
        } elseif ( $duration === 'custom' ) {
            if ( $date_from ) {
                $start_date = gmdate( 'Y-m-d 00:00:00', strtotime( $date_from ) );
            }
            if ( $date_to ) {
                $end_date = gmdate( 'Y-m-d 23:59:59', strtotime( $date_to ) );
            }
        }

        // We will query the DB directly to be safe, or use affwp_get_payouts if available
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_wp_payouts';

        // Check if table exists
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            wp_send_json_error( 'Payouts table not found' );
        }

        $query = "SELECT * FROM $table_name WHERE affiliate_id = %d";
        $args = array( $affiliate_id );

        if ( $start_date ) {
            $query .= " AND date >= %s";
            $args[] = $start_date;
        }
        if ( $end_date ) {
            $query .= " AND date <= %s";
            $args[] = $end_date;
        }

        $query .= " ORDER BY date DESC";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $payouts = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );

        // Format dates and amounts for JS
        $formatted_payouts = array();
        foreach ( $payouts as $payout ) {
            $formatted_payouts[] = array(
                'Date'          => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $payout['date'] ) ),
                'Amount'        => html_entity_decode( affwp_currency_filter( affwp_format_amount( $payout['amount'] ) ) ),
                'Payout Method' => isset( $payout['payout_method'] ) ? ucwords( str_replace( '_', ' ', $payout['payout_method'] ) ) : '',
                'Status'        => isset( $payout['status'] ) ? ucwords( str_replace( '_', ' ', $payout['status'] ) ) : '',
            );
        }

        wp_send_json_success( $formatted_payouts );
    }
}

new Export_Payout_For_AffiliateWp();
