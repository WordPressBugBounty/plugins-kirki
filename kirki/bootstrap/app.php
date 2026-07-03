<?php

defined('ABSPATH') || exit;

use Kirki\Framework\Application;

return Application::configure(KIRKI_PLUGIN_PATH)
    ->use_routing(KIRKI_PLUGIN_PATH . '/routes/api.php')
    ->use_prefix(KIRKI_PREFIX)
    ->use_app_mode(KIRKI_ENV_MODE)
    ->boot();