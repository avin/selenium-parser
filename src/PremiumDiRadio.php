<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Mockery\Exception;

class PremiumDiRadio extends SeleniumParser
{

    /**
     * Получить список доступных городов
     */
    public function getPremiumKey()
    {
        $this->driver->get("http://www.di.fm");

        $email = 'iwantsomeshit' . time() . '@gmail.com';
        $password = 'supersecret';

        //Wait register button
        $signUpButton = false;
        while (!$signUpButton) {
            try {
                $signUpButton = $this->driver->findElement(WebDriverBy::className('signup'));
            } catch (WebDriverException $exception) {
                usleep(200);
            }
        }

        $signUpButton->click();


        //Fill form

        $emailField = $this->driver->findElement(WebDriverBy::id('member_email'));
        while (! $emailField->isDisplayed()){
            usleep(200);
        }

        $emailField->click();
        $this->driver->getKeyboard()->sendKeys($email);

        $this->driver->findElement(WebDriverBy::id('member_password'))->click();
        $this->driver->getKeyboard()->sendKeys($password);

        $this->driver->findElement(WebDriverBy::id('member_password_confirmation'))->click();
        $this->driver->getKeyboard()->sendKeys($password);

        $this->driver->findElement(WebDriverBy::xpath("//button[contains(.,'Create Free Account')]"))->click();


        //Wait user-panel button
        $userButton = false;
        while (!$userButton) {
            try {
                $userButton = $this->driver->findElement(WebDriverBy::className("user-name"));
            } catch (WebDriverException $exception) {
                usleep(200);
            }
        }

        //Activate trial
        $this->driver->get("http://www.di.fm/member/premium/trial/activate");

        //Wait user-panel button
        $userType = false;
        //user-name
        while (!$userType) {
            try {
                $userType = $this->driver->findElement(WebDriverBy::xpath("//span[contains(.,'Premium Member')]"));
            } catch (WebDriverException $exception) {
                usleep(200);
            }
        }

        $this->driver->get("http://www.di.fm/settings");

        //Get key from settings page
        $key = false;
        while (!$key) {
            try {
                $key = $this->driver->findElement(WebDriverBy::className("listen-key"));
            } catch (WebDriverException $exception) {
                usleep(200);
            }
        }


        $keyValue = $key->getText();

        return $keyValue;
    }
}