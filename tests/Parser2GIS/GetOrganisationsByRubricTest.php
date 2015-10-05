<?php

use Avin\SeleniumParser\Parser2GIS;

class GetOrganisationsByRubricTest extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $parser = new Parser2GIS(
            getenv('SELENIUM_DRIVER_HOST'),
            getenv('DRIVER_BROWSER_NAME'),
            getenv('DRIVER_BROWSER_USER_AGENT')
        );

        $cityHref = 'http://2gis.ru/yaroslavl';
        $rubricName = 'Пожарная охрана';
        $rubricId = '3940769933033615';
        $organisations = $parser->getOrganisationsByRubric($cityHref, $rubricName, $rubricId);

        $this->assertGreaterThan(5, sizeof($organisations));

        //Test solo category organisation
        $cityHref = 'http://2gis.ru/yaroslavl';
        $rubricName = 'Справочно-информационные услуги';
        $rubricId = '3940769933033623';

        $organisations = $parser->getOrganisationsByRubric($cityHref, $rubricName, $rubricId);

        $this->assertEquals(1, sizeof($organisations));
    }

}