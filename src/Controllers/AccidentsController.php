<?php

require_once __DIR__ . '/../Models/Accident.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/Validator.php';

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

    public static function handlePost(array $segments): void {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body)) {
            jsonError('Invalid JSON', 400);
        }

        $errors = validateAccidentData($body, true);
        if (!empty($errors)) {
            jsonError(implode(', ', $errors), 422);
        }

        $body = sanitizeAccidentData($body);
        $id = Accident::create($body);
        jsonSuccess(Accident::getById($id), 201);
    }

    public static function handlePut(array $segments): void {
        $id = filter_var($segments[0] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            jsonError('Invalid ID', 400);
        }

        if (Accident::getById($id) === false) {
            jsonError('Not found', 404);
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body) || empty($body)) {
            jsonError('Invalid JSON', 400);
        }

        $errors = validateAccidentData($body, false);
        if (!empty($errors)) {
            jsonError(implode(', ', $errors), 422);
        }

        $body = sanitizeAccidentData($body);
        Accident::update($id, $body);
        jsonSuccess(Accident::getById($id));
    }

    public static function handleDelete(array $segments): void {
        $id = filter_var($segments[0] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            jsonError('Invalid ID', 400);
        }

        if (Accident::getById($id) === false) {
            jsonError('Not found', 404);
        }

        Accident::delete($id);
        jsonSuccess(['message' => 'Deleted successfully']);
    }
}
