<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Mockery\Exception;

class Parser2GIS extends SeleniumParser
{

    /**
     * Получить список доступных городов
     */
    public function getCities()
    {
        $this->driver->get("http://2gis.ru/cities");

        //Особенность 2гис - элементы рендерят два или более раза подряд, ждем пока не зарендерятся
        sleep(1);

        $cityList = $this->driver->findElement(WebDriverBy::className('dashboard__cityselect'));
        $cityElements = $cityList->findElements(WebDriverBy::className('citySelect__groupItemLink'));

        $results = [];

        //Обрабатыаем ссылки на города
        foreach ($cityElements as $cityElement) {
            $dataId = $cityElement->getAttribute('data-id');
            $cityLink = $cityElement->findElement(WebDriverBy::className('citySelect__groupItemLinkAnchor'));
            $cityHref = $cityLink->getAttribute("href");
            $cityTitle = $cityLink->getText();

            $results[] = [
                'name' => $cityTitle,
                'href' => $cityHref,
                'id' => $dataId,
            ];
        }

        return $results;
    }

    /**
     * Получить список рубрик
     * @param string $cityHref Ссылка на страницу города
     * @return array
     */
    public function getRubrics($cityHref)
    {
        $this->driver->get("{$cityHref}/rubrics/");

        //Особенность 2гис - элементы рендерят два раза подряд, ждем пока не зарендерят второй раз
        sleep(1);

        //Можно ждать так, но ререндера может быть больше двух
        //$rubricTempItem = $this->driver->findElement(WebDriverBy::className('rubricsList__listItem'));
        //$this->waitUntilElementDetach($rubricTempItem);

        $rubricItems = $this->driver->findElements(WebDriverBy::className('rubricsList__listItem'));

        $rubricResults = [];
        foreach ($rubricItems as $rubricItem) {
            $rubricId = $rubricItem->getAttribute('data-id');
            $rubricName = $rubricItem->getAttribute('data-name');

            //Нажимаем рубрику
            $rubricItem->click();

            //Ждем появления списка суб-рубрик
            $this->driver->wait(5, 500)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('_subRubrics'))
            );

            $subRubrics = $this->driver->findElement(WebDriverBy::className('_subRubrics'));
            $subRubricItems = $subRubrics->findElements(WebDriverBy::className('rubricsList__listItem'));

            //Парсим субрубрики
            $subRubricResults = [];
            foreach ($subRubricItems as $subRubricItem) {
                $subRubricId = $subRubricItem->getAttribute('data-id');
                $subRubricName = $subRubricItem->getAttribute('data-name');
                $subRubricResults[] = [
                    'id' => $subRubricId,
                    'name' => $subRubricName,
                ];
            }

            //Закрываем список суб рубрик
            $this->driver->getKeyboard()
                ->sendKeys(array(
                    WebDriverKeys::ESCAPE,
                ));

            //ождем закрытия
            $this->waitUntilElementDetach($subRubrics);

            $rubricResults[] = [
                'id' => $rubricId,
                'name' => $rubricName,
                'subRubrics' => $subRubricResults
            ];
        }

        return $rubricResults;
    }

    protected function extractDataFromFirmCard($firmCard){
        $organisation = [];

        //Название организации
        $organisation['name'] = $firmCard->findElement(WebDriverBy::className('firmCard__name'))->getText();

        //Адрес организации
        $orgAddressElement = $firmCard->findElements(WebDriverBy::className('firmCard__address'));
        if(isset($orgAddressElement[0])){
            $organisation['address'] = [
                'title' =>  $orgAddressElement[0]->getText(),
                'lat' => $orgAddressElement[0]->getAttribute('data-lat'),
                'lng' => $orgAddressElement[0]->getAttribute('data-lon'),
            ];
        }

        //Расписание организации
        $orgScheduleElement = $firmCard->findElements(WebDriverBy::className('schedule'));
        if (isset($orgScheduleElement[0])){
            $scheduleStatus = $orgScheduleElement[0]->findElement(WebDriverBy::className('schedule__status'))->getText();
            if (trim($scheduleStatus) == 'Круглосуточно'){
                $organisation['schedule'] = '24/7';
            } else {
                $scheduleTable = $orgScheduleElement[0]->findElement(WebDriverBy::className('schedule__table'));
                $scheduleResults = [];
                if ($scheduleTable){
                    $scheduleDays = $scheduleTable->findElement(WebDriverBy::className('_work'))->findElements(WebDriverBy::className('schedule__td'));
                    foreach ($scheduleDays as $key => $scheduleDay) {
                        $scheduleResults[$key] = [];

                        $timeElements = $scheduleDay->findElements(WebDriverBy::className('schedule__tableTime'));
                        foreach ($timeElements as $timeElement) {
                            $time = $timeElement->getAttribute("innerHTML");
                            $scheduleResults[$key][] = $time;
                        }

                    }
                }

                $organisation['schedule'] = $scheduleResults;
            }
        }

        //Телефоны организации
        $organisation['phones'] = [];
        $orgTelElement = $firmCard->findElements(WebDriverBy::className('contact__phonesVisible'));
        if (isset($orgTelElement[0])){
            $telElements = $orgTelElement[0]->findElements(WebDriverBy::className('contact__phonesItemLink'));

            foreach ($telElements as $telElement) {
                $organisation['phones'][] = str_replace("tel:", "", $telElement->getAttribute('href'));
            }
        }

        //Веб-сайт
        $organisation['sites'] = [];
        $orgSiteElement = $firmCard->findElements(WebDriverBy::className('contact__websites'));
        if (isset($orgSiteElement[0])){
            $linkElements = $orgSiteElement[0]->findElements(WebDriverBy::className('contact__linkText'));
            foreach ($linkElements as $linkElement) {
                $organisation['sites'][] = $linkElement->getAttribute('title');
            }
        }

        return $organisation;
    }

    /**
     * Получить список организаций в категории
     * @param string $cityHref Ссылка на страницу города
     * @param string $rubricName Название рубрики
     * @param string $rubricId Id рубрики
     * @return array
     */
    public function getOrganisationsByRubric($cityHref, $rubricName, $rubricId)
    {
        $rubricName = urlencode($rubricName);
        $this->driver->get("{$cityHref}/search/{$rubricName}/rubricId/$rubricId");

        $results = [];

        //Особенность 2гис - элементы рендерят вначале несколько раз, ждем пока не зарендерятся
        sleep(1);

        $organisationList = $this->driver->findElements(WebDriverBy::className('mixedResults'));

        if (isset($organisationList[0])){
            $organisations = $organisationList[0]->findElements(WebDriverBy::className('miniCard'));

            foreach ($organisations as $organisation) {
                $organisation->click();

                //Ждем появления карточки организации
                $this->driver->wait(5, 500)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('firmCard'))
                );

                $firmCard = $this->driver->findElements(WebDriverBy::className('firmCard'));
                if (isset($firmCard[0])){
                    $results[] = $this->extractDataFromFirmCard($firmCard[0]);
                }
            }
        } else {
            //Если списка организаций нет - проверяем наличие карточки организации
            $firmCard = $this->driver->findElements(WebDriverBy::className('firmCard'));

            if (isset($firmCard[0])){
                $results[] = $this->extractDataFromFirmCard($firmCard[0]);
            }
        }

        return $results;
    }
}