<?php

return array(
    'allowFileLog' => true,
    'allowGraylog' => true,
    'capabilities' => array('contacts', 'calendars', 'debug', 'mail'),
    'logLevel' => \Psr\Log\LogLevel::INFO,
    'graylogEndpoint' => 'logging.operations.audriga.com',
    'graylogUseTls' => false
);
