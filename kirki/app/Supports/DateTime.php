<?php

namespace Kirki\App\Supports;

class DateTime
{
    public static function relative_date($value)
    {
        $current = current_time('timestamp');

        switch ($value) {
            case 'last_24_hrs':
                return gmdate('Y-m-d H:i:s', strtotime('-24 hours', $current));
            case 'last_7_days':
                return gmdate('Y-m-d H:i:s', strtotime('-7 days', $current));
            case 'last_30_days':
                return gmdate('Y-m-d H:i:s', strtotime('-30 days', $current));
        }

        return null;
    }
}