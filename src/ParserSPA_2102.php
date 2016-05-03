<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Mockery\Exception;

class ParserSPA_2102 extends SeleniumParser
{
    protected $host;
    protected $password;

    /**
     * Настраиваем параметры подключения
     */
    public function setup($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    public function getDeviceInfo()
    {
        //Заходим на страницу железки
        $this->driver->get("http://{$this->user}:{$this->password}@{$this->host}");

        //Собираем строки из таблицы
        $infoRows = $this->driver->findElement(WebDriverBy::id('Status'))->findElements(WebDriverBy::tagName('tr'));

        $result = [];

        foreach ($infoRows as $infoRow) {

            //Выбираем из строки ячейки
            $tds = [];
            foreach ($infoRow->findElements(WebDriverBy::tagName('td')) as $td) {

                $tds[] = $td->getText();
            }

            //Разбираем данные
            foreach ($tds as $tdIndex => $td) {

                switch ($td) {
                    case 'Serial Number:':
                        $result['serialNumber'] = $tds[$tdIndex + 1];
                        break;
                }
            }
        }

        return $result;
    }
}