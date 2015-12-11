# config-writer
Save changes to the configuration file in script.

## Installing

Install using Composer `composer require larapack/config-writer 1.*`.

## Using it's facade

You can use our facade `Larapack\ConfigWriter\Facade` to ad the `write`-method to the default `Config`-facade.

To do this you must open your config file `config/app.php` and replace `'Config' => 'Illuminate\Support\Facades\Config::class',` under `providers`-section with our facade `Larapack\ConfigWriter\Facade::class`.
Then it will look like `'Config' => 'Larapack\ConfigWriter\Facade::class',`.

Once this is done you can use `Config::write($configFile, $changes)`, example changing your application url can be done by `Config::write('app', ['url' => 'http://your-site.com'])`.

## Using the repository

You can also use the repository `Larapack\ConfigWriter\Repository` which works a little like a model.

Example:
```
$config = new Larapack\ConfigWriter\Repository('app'); // loading the config from config/app.php

$config->set('debug', false); // set the config you wish

if ($config->get('url') == 'http://localhost') // you can even get config from this
{
	$config->set('debug', true);
}

$config->save(); // save those settings to the config file once done editing
```

If you do this a lot I recommend adding the alias `'ConfigWriter' => Larapack\ConfigWriter\Repository::class` under the `alias`-section in the config file `config/app.php`.
