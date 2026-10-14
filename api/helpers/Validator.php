<?php

final class Validator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            foreach ($fieldRules as $rule) {
                $error = self::applyRule($field, $value, $rule, $data);
                if ($error) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    private static function applyRule(string $field, $value, string $rule, array $data): ?string
    {
        if ($rule === 'required' && ($value === null || $value === '')) {
            return self::label($field) . ' is required';
        }
        if ($rule === 'accepted' && !in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true)) {
            return self::label($field) . ' must be accepted';
        }
        if (($value === null || $value === '') && $rule !== 'required') {
            return null;
        }
        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Valid email is required';
        }
        if ($rule === 'pak_mobile' && !preg_match('/^(03[0-9]{9}|\+923[0-9]{9})$/', (string) $value)) {
            return 'Valid Pakistani mobile number is required';
        }
        if ($rule === 'cnic' && !preg_match('/^[0-9]{5}-?[0-9]{7}-?[0-9]$/', (string) $value)) {
            return 'Valid CNIC is required';
        }
        if ($rule === 'strong_password' && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', (string) $value)) {
            return 'Password must contain upper case, lower case, number, and at least 8 characters';
        }
        if ($rule === 'iban' && !preg_match('/^[A-Z]{2}[0-9A-Z]{13,32}$/', (string) $value)) {
            return 'Valid IBAN is required';
        }
        if ($rule === 'date' && !self::isDate((string) $value)) {
            return self::label($field) . ' must be a valid date';
        }
        if ($rule === 'datetime' && !self::isDateTime((string) $value)) {
            return self::label($field) . ' must be a valid date and time';
        }
        if ($rule === 'positive_int' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0)) {
            return self::label($field) . ' is invalid';
        }
        if (strpos($rule, 'min:') === 0 && strlen((string) $value) < (int) substr($rule, 4)) {
            return self::label($field) . ' is too short';
        }
        if (strpos($rule, 'max:') === 0 && strlen((string) $value) > (int) substr($rule, 4)) {
            return self::label($field) . ' is too long';
        }
        if (strpos($rule, 'digits:') === 0) {
            $length = (int) substr($rule, 7);
            if (!preg_match('/^[0-9]{' . $length . '}$/', (string) $value)) {
                return self::label($field) . ' must be ' . $length . ' digits';
            }
        }
        if (strpos($rule, 'same:') === 0) {
            $other = substr($rule, 5);
            if (($data[$other] ?? null) !== $value) {
                return self::label($field) . ' must match ' . self::label($other);
            }
        }
        if (strpos($rule, 'in:') === 0) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array((string) $value, $allowed, true)) {
                return self::label($field) . ' is invalid';
            }
        }

        return null;
    }

    private static function label(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }

    private static function isDate(string $value): bool
    {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTime && $date->format('Y-m-d') === $value;
    }

    private static function isDateTime(string $value): bool
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $date instanceof DateTime && $date->format('Y-m-d H:i:s') === $value;
    }
}
