<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

if (!function_exists('optimize_config')) {
    /**
     * Test-only compatibility shim for installations using a shared vendor path.
     *
     * @param mixed $default
     * @return mixed
     */
    function optimize_config(string $key, $default = null)
    {
        if ($key === 'SHARED_VENDOR_PATH') {
            return getenv('DATA_LOCALE_PARSER_TEST_VENDOR_PATH') ?: $default;
        }

        return $default;
    }
}
