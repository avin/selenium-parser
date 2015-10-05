<?php

use Avin\SeleniumParser\Parser2GIS;

class GetCitiesTest extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $parser = new Parser2GIS(
            getenv('SELENIUM_DRIVER_HOST'),
            getenv('DRIVER_BROWSER_NAME'),
            getenv('DRIVER_BROWSER_USER_AGENT')
        );

        $cities = $parser->getCities();

        $this->assertGreaterThan(90, sizeof($cities));

        $this->assertNotEmpty($cities[0]['name']);
        $this->assertNotEmpty($cities[0]['href']);
        $this->assertNotEmpty($cities[0]['id']);
    }

}