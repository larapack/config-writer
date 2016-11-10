<?php
namespace Larapack\ConfigWriter\Tests;

use Illuminate\Support\Arr;
use Illuminate\Container\Container;
use Larapack\ConfigWriter\Repository;
use PHPUnit_Framework_TestCase;

class ReadmeExampleTest extends PHPUnit_Framework_TestCase
{


    public function readmeExampleTestProvider()
    {
        return [
            [
                'app', // repository
                'debug', // set property
                false, // default value
                'url', // check this property
                'http://localhost/', // check for this value
                true, // expect set property value after check matches
                false, // expect set property value after check fails
            ],
        ];
    }

    /**
    * @dataProvider readmeExampleTestProvider
    */
    public function testReadmeExample(
        $repository,
        $setProperty,
        $setValue,
        $checkProperty,
        $checkValue,
        $expectIfCheckMatches,
        $expectIfCheckFails
    ) {
        $config = new Repository($repository);
        $config->set($setProperty, $setValue);
        $expectThis = $expectIfCheckFails;
        if ($config->get($checkProperty, $checkValue)) {
            $expectThis = $expectIfCheckMatches;
            $config->set($setProperty, $expectIfCheckMatches);
        }

        $config->save();

        $config = new Repository($repository);
        $this->expectSame(
            $config->get($setProperty),
            $expectThis
        );
    }
}
