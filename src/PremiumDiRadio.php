<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Mockery\Exception;

class PremiumDiRadio extends SeleniumParser
{

    /**
     * Получить список доступных городов
     */
    public function getPremiumKey()
    {
        $this->driver->get("http://www.di.fm");

        $email = 'iwantsomeshit'.time().'@gmail.com';
        $password = 'supersecret';

        //Ждем появления кнопки регистрации
        $signUpButton = false;
        while(! $signUpButton){
            $signUpButton = $this->driver->findElement(WebDriverBy::className('signup'));
            usleep(200);
        }

        $signUpButton->click();

        usleep(200);

        $this->driver->findElement(WebDriverBy::id('member_email'))->click();
        $this->driver->getKeyboard()->sendKeys($email);

        $this->driver->findElement(WebDriverBy::id('member_password'))->click();
        $this->driver->getKeyboard()->sendKeys($password);

        $this->driver->findElement(WebDriverBy::id('member_password_confirmation'))->click();
        $this->driver->getKeyboard()->sendKeys($password);

        $this->driver->findElement(WebDriverBy::xpath("//button[contains(.,'Create Free Account')]"))->click();


        $userButton = false;
        //user-name
        while(! $userButton){
            $userButton = $this->driver->findElement(WebDriverBy::className("user-name"));
            usleep(200);
        }

        $this->driver->get("http://www.di.fm/member/premium/trial/activate");

        $userType = false;
        //user-name
        while(! $userType){
            $userType = $this->driver->findElement(WebDriverBy::xpath("//span[contains(.,'Premium Member')]"));
            usleep(200);
        }

        $this->driver->get("http://www.di.fm/settings");

        $key = false;
        while(! $key){
            $key = $this->driver->findElement(WebDriverBy::className("listen-key"));
            usleep(200);
        }


        $keyValue = $key->getText();

        return $keyValue;
    }
}