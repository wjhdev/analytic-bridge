<?php

// Prevent direct file access
if (!defined('ABSPATH')) {
  exit();
}

// Toggle this to generate fake data.
define('FAKE_API', false);

/** Table defines */
global $wpdb;
define('METRICS_TABLE', $wpdb->prefix . 'bt_analyticsbridge_metrics');
define('PAGES_TABLE', $wpdb->prefix . 'bt_analyticsbridge_pages');

/** Include Google PHP client library. */
require_once '/srv/vendor/autoload.php';

require_once plugin_dir_path(__FILE__) . 'AnalyticBridgeGoogleClient.php';
require_once plugin_dir_path(__FILE__) . 'Analytic_Bridge_Service.php';

/**
 * Registers admin option page and populates with
 * plugin settings.
 */
require_once 'inc/blog-options.php';

require_once plugin_dir_path(__FILE__) . 'classes/AnalyticsDashWidget.php';
require_once plugin_dir_path(__FILE__) . 'classes/AnalyticsBridgePopularPosts.php';
require_once plugin_dir_path(__FILE__) . 'classes/AnalyticBridgeGoogleAnalytics.php';

/**
 * Functions for activating/deactivating the plugin.
 */
require_once 'inc/installation.php';

require_once 'inc/ga-interface.php';
require_once 'inc/mock-ga-interface.php';