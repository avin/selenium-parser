<?php
namespace Avin\SeleniumParser;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Mockery\Exception;

class ParserDES_1100_16 extends SeleniumParser
{
    protected $host;
    protected $password;

    /**
     * Очистить мак-строку от спецсимволов
     * @param $macString
     * @return string
     */
    protected function cleanMacString($macString){
        return  strtolower(preg_replace('/[^\d\w]/u', '', $macString));
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
    public function doAuth(){
        //Просто переходим по урлу с паролем в качестве GET-параметра
        $this->driver->get("http://{$this->host}/cgi/login.cgi?pass={$this->password}&Challenge=Ciwl");
    }

    public function getDeviceInfo(){
        //Заходим на страницу с инфой
        $this->driver->get("http://{$this->host}/H_00_Devtab.htm");

        //Собираем строки из таблицы tabDeviceInfo
        $vlanTableRows = $this->driver->findElement(WebDriverBy::id('tabDeviceInfo'))->findElements(WebDriverBy::tagName('tr'));

        $result = [];

        foreach ($vlanTableRows as $vlanTableRow) {

            //Выбираем из строки ячейки
            $tds = [];
            foreach ($vlanTableRow->findElements(WebDriverBy::tagName('td')) as $td) {

                $tds[] = $td->getText();
            }

            //Разбираем данные
            foreach ($tds as $tdIndex => $td) {

                switch ($td){
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
    public function getMacs(){
        //Заходим на страницу с таблицей маков
        $this->driver->get("http://{$this->host}/H_46_DF_Table.htm");

        //Выбираем строки в таблице
        $vlanTableRows = $this->driver
            ->findElement(WebDriverBy::id('dynamicData'))
            ->findElements(WebDriverBy::tagName('tr'));

        $results = [];
        foreach ($vlanTableRows as $vlanTableRow) {
            $tds = $vlanTableRow->findElements(WebDriverBy::tagName('td'));

            $portNum = $tds[1]->getText();

            //Преобразуем дополнительно очищаем от спецсимволов
            $mac = $this->cleanMacString($tds[2]->getText());

            if (!isset($results[$portNum])){
                $results[$portNum] = [];
            }
            $results[$portNum][] = $mac;
        }

        return $results;

    }

    /**
     * Получить спискок VLAN-ов
     */
    public function getVlans()
    {
        //Заходим на страницу с таблицей вланов
        $this->driver->get("http://{$this->host}/H_21_1QVLAN_Asy_table.htm");

        //Выбираем строки в таблице
        $vlanTableRows = $this->driver
            ->findElement(WebDriverBy::id('mainGray'))
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