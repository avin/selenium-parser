<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\Exception\StaleElementReferenceException;

abstract class SeleniumParser {

    protected $driver;

    /**
     * Constructor. Подготовка драйвера Selenium
     */
    public function __construct(
        $seleniumDriverHost = "http://localhost:8910",
        $driverBrowserName = 'phantomjs',
        $driverBrowserUserAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:25.0) Gecko/20100101 Firefox/25.0'
    )
    {
        $this->seleniumDriverHost = $seleniumDriverHost;

        $capabilities = array(
            WebDriverCapabilityType::BROWSER_NAME => $driverBrowserName,
            'phantomjs.page.settings.userAgent' => $driverBrowserUserAgent,
        );
        $this->driver = RemoteWebDriver::create($seleniumDriverHost, $capabilities, 5000);

        $window = new WebDriverDimension(1280, 1024);
        $this->driver->manage()->window()->setSize($window);
    }

    /**
     * Destructor. Закрытие сессии selenium.
     */
    public function __destruct()
    {
        $this->driver->quit();
    }


    /**
     * Дождаться когда элемент перестанет существовать на странице
     * @param $element
     */
    protected function waitUntilElementDetach($element){
        $this->driver->wait(5, 200)->until(
            function() use ($element){
                try {
                    $element->isDisplayed();
                    return false;
                } catch (StaleElementReferenceException  $e) {
                    return true;
                }
            }
        );
    }

}