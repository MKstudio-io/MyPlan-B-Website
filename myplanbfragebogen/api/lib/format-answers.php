<?php
// Answer formatting for emails - port of lib/format-answers.ts

function formatValue(string $type, $value): string {
    if ($value === null || $value === '') return '–';

    switch ($type) {
        case 'textarea-with-dontknow':
            if (is_array($value) && !empty($value['dontKnow'])) return 'Weiß ich nicht';
            return is_array($value) && !empty($value['text']) ? $value['text'] : '–';

        case 'chips':
            return is_array($value) && count($value) > 0 ? implode(', ', $value) : '–';

        case 'checkbox-group':
            if (!is_array($value)) return '–';
            $items = $value['selected'] ?? [];
            if (!empty($value['otherText'])) {
                foreach ($items as $i => $s) {
                    if ($s === 'Anderes' || $s === 'Sonstiges') {
                        $items[$i] = "$s: {$value['otherText']}";
                        break;
                    }
                }
            }
            return count($items) > 0 ? implode(', ', $items) : '–';

        case 'radio':
            $str = (string)$value;
            if (strpos($str, OTHER_PREFIX) === 0) {
                $text = substr($str, strlen(OTHER_PREFIX));
                return 'Sonstiges: ' . ($text !== '' ? $text : '–');
            }
            return $str;

        case 'gdpr-checkbox':
            return $value === true ? 'Ja' : 'Nein';

        case 'slider':
            return $value !== null && $value !== '' ? (string)$value : '–';

        default:
            return (string)$value;
    }
}

function escapeHtml(string $str): string {
    $escaped = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    return str_replace("\n", '<br>', $escaped);
}

function formatAnswersText(array $steps, array $answers): string {
    $lines = [];
    foreach ($steps as $step) {
        $lines[] = "--- {$step['title']} ---";
        foreach ($step['questions'] as $q) {
            if ($q['type'] === 'gdpr-checkbox') continue;
            $val = formatValue($q['type'], $answers[$q['id']] ?? null);
            $lines[] = "{$q['label']}: $val";
        }
        $lines[] = '';
    }
    return implode("\n", $lines);
}

function formatAnswersHtml(array $steps, array $answers): string {
    $sections = [];
    foreach ($steps as $step) {
        $rows = '';
        foreach ($step['questions'] as $q) {
            if ($q['type'] === 'gdpr-checkbox') continue;
            $val = escapeHtml(formatValue($q['type'], $answers[$q['id']] ?? null));
            $label = htmlspecialchars($q['label'], ENT_QUOTES, 'UTF-8');
            $rows .= "<tr>
                <td style=\"padding: 6px 12px 6px 0; color: #718096; vertical-align: top; width: 200px; font-size: 14px;\">$label</td>
                <td style=\"padding: 6px 0; font-size: 14px;\">$val</td>
            </tr>\n";
        }
        $title = htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8');
        $sections[] = "<h3 style=\"color: #2D3748; margin: 24px 0 8px; font-size: 16px; border-bottom: 1px solid #E2E8F0; padding-bottom: 4px;\">$title</h3>
        <table style=\"width: 100%; border-collapse: collapse;\">$rows</table>";
    }
    return implode("\n", $sections);
}
