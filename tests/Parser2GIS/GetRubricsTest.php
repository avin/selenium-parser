<?php

use Avin\SeleniumParser\Parser2GIS;

class GetRubricsTest extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $parser = new Parser2GIS(
            getenv('SELENIUM_DRIVER_HOST'),
            getenv('DRIVER_BROWSER_NAME'),
            getenv('DRIVER_BROWSER_USER_AGENT')
        );

        $rubrics = $parser->getRubrics('http://2gis.ru/yaroslavl');

        $this->assertGreaterThan(10, sizeof($rubrics));

        $this->assertNotEmpty($rubrics[0]['id']);
        $this->assertNotEmpty($rubrics[0]['name']);
        $this->assertNotEmpty($rubrics[0]['subRubrics']);

        $this->assertGreaterThan(1, sizeof($rubrics[0]['subRubrics']));
    }

}