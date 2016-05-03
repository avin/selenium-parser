<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Mockery\Exception;

class ParserDGS_1100_16 extends SeleniumParser
{
    protected $host;
    protected $password;

    /**
     * Очистить мак-строку от спецсимволов
     * @param $macString
     * @return string
     */
    protected function cleanMacString($macString)
    {
        return strtolower(preg_replace('/[^\d\w]/u', '', $macString));
    }

    /**
     * Настраиваем параметры подключения
     */
    public function setup($host, $password)
    {
        $this->host = $host;
        $this->password = $password;
    }

    /**
     * Авторизация
     */
    public function doAuth()
    {
        //Просто переходим по урлу с паролем в качестве GET-параметра
        $this->driver->get("http://{$this->host}/");

        $frame = $this->driver->findElement(WebDriverBy::tagName('frame'));;
        $this->driver->switchTo()->frame($frame);

        //Работаем только с данной формой, т.к есть другие с такимиже именами полей
        //$form = $this->driver->findElement(WebDriverBy::name('formlog'));

        $this->driver->findElement(WebDriverBy::cssSelector('.flatL#pass'))->sendKeys($this->password);

        $loginButton = false;

        //Для разных версий прошивки кнопка может отличаться

        try {
            $loginButton = $this->driver->findElement(WebDriverBy::cssSelector('input[onclick="login();"]'));
        } catch (\Exception $e){}

        try {
            $loginButton = $this->driver->findElement(WebDriverBy::cssSelector('input[onclick="submit();"]'));
        } catch (\Exception $e){}

        $loginButton->click();
        
        //Всегда работаем с данной сессией, поэтому дожидаемся прогузки страницы для дальнейшей работы с ней
        sleep(5);
    }

    public function getDeviceInfo()
    {
        $this->driver->switchTo()->defaultContent();

        //Нажимаем ссылку "домажней страницы" в левом фрейме меню
        $leftMenuFrame = $this->driver->findElement(WebDriverBy::name('leftMenu'));
        $this->driver->switchTo()->frame($leftMenuFrame);
        $this->driver->switchTo()->frame('treeConfig');

        $this->driver->findElement(WebDriverBy::partialLinkText("DGS-1100"))->click();

        //Возвращаемся на верхний уровень страницы (выходим из фреймов)
        $this->driver->switchTo()->defaultContent();

        //Ждем когда страница прогрузится
        sleep(2);

        //Работаем с фреймом mf0
        $this->driver->switchTo()->frame('mf0');

        //Собираем строки из таблицы tabContent
        $vlanTableRows = $this->driver->findElement(WebDriverBy::id('tabContent'))->findElements(WebDriverBy::tagName('tr'));

        $result = [];

        foreach ($vlanTableRows as $vlanTableRow) {

            //Выбираем из строки ячейки
            $tds = [];
            foreach ($vlanTableRow->findElements(WebDriverBy::tagName('td')) as $td) {

                $tds[] = $td->getText();
            }

            //Разбираем данные
            foreach ($tds as $tdIndex => $td) {

                switch ($td) {
                    case 'Serial Number':
                        $result['serialNumber'] = $tds[$tdIndex + 1];
                        break;
                    case 'Firmware Version':
                        $result['firmwareVersion'] = $tds[$tdIndex + 1];
                        break;
                    case 'System Location':
                        $result['systemLocation'] = $tds[$tdIndex + 1];
                        break;
                    case 'System Name':
                        $result['systemName'] = $tds[$tdIndex + 1];
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * Получить маки с портов
     */
    public function getMacs()
    {
        $this->driver->switchTo()->defaultContent();

        //Нажимаем ссылку "802.1Q VLAN" в левом фрейме меню
        $leftMenuFrame = $this->driver->findElement(WebDriverBy::name('leftMenu'));
        $this->driver->switchTo()->frame($leftMenuFrame);
        $this->driver->switchTo()->frame('treeConfig');

        $this->driver->findElement(WebDriverBy::cssSelector('a[title="Security"]'))->click();
        sleep(1);

        $this->driver->findElement(WebDriverBy::cssSelector('a[title="MAC Address Table"]'))->click();
        sleep(1);

        $this->driver->findElement(WebDriverBy::cssSelector('a[title="Dynamic Forwarding Table"]'))->click();
        sleep(1);

        //Возвращаемся на верхний уровень страницы (выходим из фреймов)
        $this->driver->switchTo()->defaultContent();

        $results = [];

        //Ждем когда страница прогрузится (грузится долго! поэтому sleep побольше)
        sleep(5);

        //Работаем с фреймом mf0
        $this->driver->switchTo()->frame('mf0');

        while(true){

            //Выбираем строки в таблице
            $vlanTableRows = $this->driver
                ->findElement(WebDriverBy::id('listDMAC'))
                ->findElements(WebDriverBy::tagName('tr'));

            //Первая строка - шапка - она нас не интересует
            array_shift($vlanTableRows);

            foreach ($vlanTableRows as $vlanTableRow) {
                $tds = $vlanTableRow->findElements(WebDriverBy::tagName('td'));

                $portNum = $tds[1]->getText();

                //Преобразуем дополнительно очищаем от спецсимволов
                $mac = $this->cleanMacString($tds[2]->getText());

                if (!isset($results[$portNum])) {
                    $results[$portNum] = [];
                }
                $results[$portNum][] = $mac;
            }

            //ищем кнопку "далее"
            $nextButton = $this->driver->findElement(WebDriverBy::id('nextbutton'));

            //Если кнопка далее неактивна - выходим
            if ($nextButton->getAttribute('disabled')){
                return $results;
            }

            //Нажимаем кнопку далее
            $nextButton->click();

            //Ждем когда страница перелестнеться (долго)
            sleep(7);
        }
    }

    /**
     * Получить спискок VLAN-ов
     */
    public function getVlans()
    {
        $this->driver->switchTo()->defaultContent();

        //Нажимаем ссылку "802.1Q VLAN" в левом фрейме меню
        $leftMenuFrame = $this->driver->findElement(WebDriverBy::name('leftMenu'));
        $this->driver->switchTo()->frame($leftMenuFrame);
        $this->driver->switchTo()->frame('treeConfig');

        $this->driver->findElement(WebDriverBy::cssSelector('a[title="VLAN"]'))->click();
        sleep(1);

        $this->driver->findElement(WebDriverBy::cssSelector('a[title="802.1Q VLAN"]'))->click();
        sleep(1);

        //Возвращаемся на верхний уровень страницы (выходим из фреймов)
        $this->driver->switchTo()->defaultContent();

        //Ждем когда страница прогрузится
        sleep(2);

        //Работаем с фреймом mf0
        $this->driver->switchTo()->frame('mf0');

        //Выбираем строки в таблице
        $vlanTableRows = $this->driver
            ->findElement(WebDriverBy::id('listQVlan'))
            ->findElements(WebDriverBy::tagName('tr'));

        //Первая строка - шапка - она нас не интересует
        array_shift($vlanTableRows);

        $results = [];
        foreach ($vlanTableRows as $vlanTableRow) {
            $tds = $vlanTableRow->findElements(WebDriverBy::tagName('td'));

            $vlanId = $tds[0]->getText();

            //Имя влана лежит в инпуте
            $vlanName = $tds[1]->findElement(WebDriverBy::id('VlanName'))->getAttribute('value');

            $vlanUntagedPorts = $tds[2]->getText();
            $vlanTaggedPorts = $tds[3]->getText();

            //Очищаем строки от знаков переноса
            $vlanUntagedPorts = str_replace("\n", "", $vlanUntagedPorts);
            $vlanTaggedPorts = str_replace("\n", "", $vlanTaggedPorts);

            //Разибваем строку с портами в массив
            $vlanUntagedPorts = explode(",", $vlanUntagedPorts);
            $vlanTaggedPorts = explode(",", $vlanTaggedPorts);

            $results[$vlanId] = [
                'name' => $vlanName,
                'ports' => [
                    'untagged' => $vlanUntagedPorts,
                    'tagged' => $vlanTaggedPorts,
                ]
            ];
        }

        return $results;
    }
}