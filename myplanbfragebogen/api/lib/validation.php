<?php
// Server-side validation - port of lib/validation.ts

define('OTHER_PREFIX', '__other__:');

function validateField(array $config, $value): ?string {
    if (empty($config['required'])) return null;

    $type  = $config['type'] ?? '';
    $label = $config['label'] ?? '';

    switch ($type) {
        case 'text':
        case 'email':
        case 'tel':
            $str = is_string($value) ? trim($value) : '';
            if ($str === '') return "Bitte $label eingeben";
            if ($type === 'email' && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
                return 'Bitte eine gültige E-Mail-Adresse eingeben';
            }
            return null;

        case 'textarea':
            $str = is_string($value) ? trim($value) : '';
            if ($str === '') return "Bitte $label ausfüllen";
            return null;

        case 'textarea-with-dontknow':
            if (!is_array($value)) return "Bitte $label ausfüllen";
            if (empty($value['dontKnow']) && trim($value['text'] ?? '') === '') {
                return "Bitte $label ausfüllen oder \"Weiß ich nicht\" ankreuzen";
            }
            return null;

        case 'date':
            $str = is_string($value) ? $value : '';
            $parts = explode('.', $str);
            if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
                return 'Bitte ein vollständiges Datum angeben';
            }
            return null;

        case 'time':
            $str = is_string($value) ? $value : '';
            $parts = explode(':', $str);
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                return 'Bitte eine vollständige Uhrzeit angeben';
            }
            return null;

        case 'place-autocomplete':
            $str = is_string($value) ? trim($value) : '';
            if ($str === '') return 'Bitte einen Ort auswählen';
            return null;

        case 'radio':
            $str = is_string($value) ? $value : '';
            if ($str === '') return 'Bitte eine Option wählen';
            if ($str === OTHER_PREFIX) return 'Bitte einen Text eingeben';
            if (strpos($str, OTHER_PREFIX) === 0 && trim(substr($str, strlen(OTHER_PREFIX))) === '') {
                return 'Bitte einen Text eingeben';
            }
            return null;

        case 'dropdown':
            $str = is_string($value) ? $value : '';
            if ($str === '') return "Bitte $label wählen";
            return null;

        case 'chips':
            $arr = is_array($value) ? $value : [];
            if (count($arr) === 0) return 'Bitte mindestens eine Option wählen';
            return null;

        case 'checkbox-group':
            if (!is_array($value) || empty($value['selected']) || count($value['selected']) === 0) {
                return 'Bitte mindestens eine Option wählen';
            }
            return null;

        case 'gdpr-checkbox':
            if ($value !== true) return 'Bitte stimme der Datenschutzerklärung zu';
            return null;

        case 'slider':
            if ($value === null || $value === '') return 'Bitte einen Wert wählen';
            return null;

        default:
            return null;
    }
}

function validateSubmission(array $steps, array $answers): array {
    $errors = [];
    foreach ($steps as $step) {
        foreach ($step['questions'] as $question) {
            $value = $answers[$question['id']] ?? null;
            $error = validateField($question, $value);
            if ($error !== null) {
                $errors[] = ['field' => $question['id'], 'message' => $error];
            }
        }
    }
    return $errors;
}
