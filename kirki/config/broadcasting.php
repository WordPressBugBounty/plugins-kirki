<?php
/**
 * Collaboration broadcasting configuration.
 *
 * Credentials are managed through the Settings → Collaboration UI and
 * stored in the KIRKI_WP_ADMIN_COMMON_DATA WordPress option.
 *
 * @package kirki
 */

defined('ABSPATH') || exit;

$common_data = get_option(
    defined('KIRKI_WP_ADMIN_COMMON_DATA') ? KIRKI_WP_ADMIN_COMMON_DATA : 'kirki_wp_admin_common_data',
    []
);

$pusher = isset($common_data['pusher_credentials']) ? $common_data['pusher_credentials'] : [];
$driver = isset($common_data['broadcasting_driver']) ? $common_data['broadcasting_driver'] : 'eventstream';

// If the stored driver is pusher but credentials are missing, fall back.
if ('pusher' === $driver && empty($pusher['app_key'])) {
    $driver = 'eventstream';
}

return [

    /*
     * Driver used to deliver collaboration events to the browser.
     *
     * "pusher"      - any server speaking the Pusher protocol
     *                 (Pusher Channels, Laravel Reverb, soketi, ...).
     * "eventstream" - the built in PHP event stream fallback.
     */
    'default' => $driver,

    'connections' => [

        'pusher' => [
            'driver'  => 'pusher',
            'app_id'  => isset($pusher['app_id'])  ? $pusher['app_id']  : '',
            'key'     => isset($pusher['app_key'])  ? $pusher['app_key']  : '',
            'secret'  => isset($pusher['app_secret']) ? $pusher['app_secret'] : '',
            'cluster' => isset($pusher['cluster'])  ? $pusher['cluster']  : 'mt1',

            /*
             * Leave the host empty to use the Pusher cloud for the cluster
             * above. Set it to reach a self-hosted server instead.
             */
            'host'   => '',
            'port'   => 443,
            'scheme' => 'https',
        ],

        'eventstream' => [
            'driver' => 'eventstream',
        ],

    ],

];
