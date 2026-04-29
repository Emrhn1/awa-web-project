<?php

function validateAccidentData(array $data, bool $requireAll = true): array {
    $errors = [];

    if ($requireAll || isset($data['date'])) {
        if (empty($data['date'])) {
            $errors[] = 'date is required';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
            $errors[] = 'date must be in YYYY-MM-DD format';
        }
    }

    if ($requireAll || isset($data['time'])) {
        if (empty($data['time'])) {
            $errors[] = 'time is required';
        } elseif (!preg_match('/^\d{2}:\d{2}$/', $data['time'])) {
            $errors[] = 'time must be in HH:MM format';
        }
    }

    if ($requireAll || isset($data['latitude'])) {
        if (!isset($data['latitude']) || $data['latitude'] === '') {
            $errors[] = 'latitude is required';
        } elseif (!is_numeric($data['latitude']) || $data['latitude'] < -90 || $data['latitude'] > 90) {
            $errors[] = 'latitude must be a number between -90 and 90';
        }
    }

    if ($requireAll || isset($data['longitude'])) {
        if (!isset($data['longitude']) || $data['longitude'] === '') {
            $errors[] = 'longitude is required';
        } elseif (!is_numeric($data['longitude']) || $data['longitude'] < -180 || $data['longitude'] > 180) {
            $errors[] = 'longitude must be a number between -180 and 180';
        }
    }

    if ($requireAll || isset($data['city'])) {
        if (empty($data['city'])) {
            $errors[] = 'city is required';
        }
    }

    if ($requireAll || isset($data['severity'])) {
        $allowed = ['minor', 'major', 'fatal'];
        if (empty($data['severity'])) {
            $errors[] = 'severity is required';
        } elseif (!in_array($data['severity'], $allowed)) {
            $errors[] = 'severity must be minor, major or fatal';
        }
    }

    if (!empty($data['vehicle_count']) && !is_numeric($data['vehicle_count'])) {
        $errors[] = 'vehicle_count must be a number';
    }
    if (!empty($data['injury_count']) && !is_numeric($data['injury_count'])) {
        $errors[] = 'injury_count must be a number';
    }
    if (!empty($data['death_count']) && !is_numeric($data['death_count'])) {
        $errors[] = 'death_count must be a number';
    }

    return $errors;
}

function sanitizeAccidentData(array $data): array {
    $fields = ['city', 'district', 'description'];
    foreach ($fields as $field) {
        if (!empty($data[$field])) {
            $data[$field] = strip_tags(trim($data[$field]));
        }
    }
    return $data;
}
