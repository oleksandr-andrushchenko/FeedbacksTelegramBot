<?php


$url = 'https://business-guide.com.ua/enterprises?q=%E1%EE%F0%E8%F1%E0+%E3%EC%E8%F0%B3+9%E2&Submit=%CF%EE%F8%F3%EA';
$e = fn ($s) => mb_convert_encoding($s, 'Windows-1251');
var_dump($url, 'https://business-guide.com.ua/enterprises?'.http_build_query(['q' => $e('бориса гмирі 9в'), 'Submit' => $e('Пошук')]));
die;

var_dump(
    file_get_contents(
        sprintf(
            'https://business-guide.com.ua/enterprises?q=%s&Submit=%s',
            urlencode('бориса гмирі 9в'),
            urlencode('Пошук')
        )
    )
);

var_dump(file_get_contents('https://business-guide.com.ua/enterprises?q=%E1%EE%F0%E8%F1%E0+%E3%EC%E8%F0%B3+9%E2&Submit=%CF%EE%F8%F3%EA'));