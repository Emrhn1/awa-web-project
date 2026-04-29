<?php

require_once __DIR__ . '/Controllers/AccidentsController.php';

const ROUTES = [
    'accidents' => [
        'GET'    => ['AccidentsController', 'handleGet'],
        'POST'   => ['AccidentsController', 'handlePost'],
        'PUT'    => ['AccidentsController', 'handlePut'],
        'DELETE' => ['AccidentsController', 'handleDelete'],
    ],
];
