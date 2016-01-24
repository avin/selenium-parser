<?php


use Avin\SeleniumParser\PremiumDiRadio;

class Test extends PHPUnit_Framework_TestCase
{

    public function test_register()
    {
        $client = new PremiumDiRadio(
//            getenv('SELENIUM_DRIVER_HOST'),
//            getenv('DRIVER_BROWSER_NAME'),
//            getenv('DRIVER_BROWSER_USER_AGENT')
        );

        $premiumKey = $client->getPremiumKey();

        $this->assertNotEmpty($premiumKey);
    }

}