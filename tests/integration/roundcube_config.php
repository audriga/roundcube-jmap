<?php

return array(
    'capabilities' => array('jscontact', 'mail', 'debug'),
    'adminUsers' => array('wp13405851-openxport'),
    'verboseErrorOutput' => true,
    'allowFileLog' => true,
    'allowGraylog' => true,
    'logLevel' => \Psr\Log\LogLevel::INFO,
    'graylogEndpoint' => 'logging.operations.audriga.com',
    'graylogUseTls' => false
);
