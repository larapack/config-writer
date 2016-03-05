<?php

namespace Larapack\ConfigWriter;

use Illuminate\Support\Facades\Config;

class Facade extends Config
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public static function write($config, array $newValues = [], $validate = true)
    {
        $config = new Repository($config);

        foreach ($newValues as $key => $value) {
            $config->set($key, $value);
        }

        $config->save(null, null, $validate);
    }
}
