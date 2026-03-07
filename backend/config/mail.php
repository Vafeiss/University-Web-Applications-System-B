<?php

return [
    'host' => getenv('SMTP_HOST'),
    'username' => getenv('SMTP_USER'),
    'password' => getenv('SMTP_PASS'),
    'port' => getenv('SMTP_PORT')
];