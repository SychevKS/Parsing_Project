<?php
    namespace Facebook\WebDriver;

    include 'simple_html_dom.php';

    use Facebook\WebDriver\Remote\DesiredCapabilities;
    use Facebook\WebDriver\Remote\RemoteWebDriver;
use mysqli;

require_once('vendor/autoload.php');

    $host = 'http://localhost:4444';
    $capabilities = DesiredCapabilities::chrome();

    $mysqli = new mysqli('localhost','root', '', 'tz');



    //массивы для бд
    $array_num = [];
    $array_occnum = [];
    $array_email = [];
    $array_link = [];
    $array_docs = [[],[],[]];


    $html = file_get_html('https://etp.eltox.ru/registry/procedure?id=&procedure=&oos_id=&company=&inn=&type=1&price_from=&price_to=&published_from=&published_to=&offer_from=&offer_to=&status=');
    $block = $html->find('.descriptTenderTd');
    $driver = RemoteWebDriver::create($host, $capabilities);
    for($i = 0; $i < count($block); $i++) {
        $procs = $block[$i]->find('dl dt a');
        $a = 'https://etp.eltox.ru/'.$procs[0]->href;
        array_push($array_link, $a);
        $driver->get($a);
        $elements = $driver->findElements(WebDriverBy::cssSelector('.table > tbody > tr > td'));
        foreach($elements as $element) {
            $element = $element->getText();
            if(preg_match("/^\d{4}$/", $element)) {  
                array_push($array_num, $element);
            }
            if(preg_match("/^\d{11}$/", $element)) {  
                array_push($array_occnum, $element);
            }
            if(preg_match("/^([a-zA-Z0-9_-]+\.)*[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[A-Za-z]{2,6}$/", $element)) {  
                array_push($array_email, $element);
            }
        }
    }
    $driver->quit();

    $driver = RemoteWebDriver::create($host, $capabilities);
    for($i = 0; $i < count($block); $i++) {
        $procs = $block[$i]->find('dl dt a');
        $a = 'https://etp.eltox.ru/'.$procs[0]->href.'#tab-attachment';
        $driver->get($a);
        $elements = $driver->findElements(WebDriverBy::cssSelector('span.qq-upload-file > a'));
        $j = 0;
        foreach($elements as $element) {
            $href = $element->getAttribute('href');
            $doc_name = $element->getText();
            $array_docs[$i][$j] = [ $doc_name , $href ];
            $j++; 
        }
    }
    $driver->quit();


    for($i = 0; $i < count($array_num); $i++) {
        echo $array_num[$i].'<br>';
        echo $array_occnum[$i].'<br>';
        echo $array_email[$i].'<br>';
        echo $array_link[$i].'<br>';

        $mysqli->query("INSERT INTO `procs` (`ooc`, `num_occ`, `link`, `email`) VALUES ('$array_num[$i]', '$array_occnum[$i]', '$array_link[$i]', '$array_email[$i]')");

        for($j = 0; $j < count($array_docs[$i]); $j++) {
            echo $d = $array_docs[$i][$j][0].'<br>';
            echo $l = $array_docs[$i][$j][1].'<br>';
            $mysqli->query("INSERT INTO `docs` (`id_ooc`, `doc`, `link_doc`) VALUES ((SELEct `id_procs` FROM `procs` WHERE `ooc` = '$array_num[$i]'), '$d', '$l')");
        }
    }


    mysqli_close($mysqli);
?>