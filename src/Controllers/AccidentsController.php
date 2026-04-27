<?php

require_once __DIR__ . '/../Models/Accident.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AccidentsController {

    public static function handleGet(array $segments): void {
        if (!empty($segments[0])) {
            $id = filter_var($segments[0], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                jsonError('Invalid ID', 400);
            }
            $accident = Accident::getById($id);
            if ($accident === false) {
                jsonError('Not found', 404);
            }
            jsonSuccess($accident);
        }

        $filters = [];
        if (!empty($_GET['date']))     $filters['date']     = $_GET['date'];
        if (!empty($_GET['city']))     $filters['city']     = $_GET['city'];
        if (!empty($_GET['severity'])) $filters['severity'] = $_GET['severity'];

        jsonSuccess(Accident::getAll($filters));
    }
}
