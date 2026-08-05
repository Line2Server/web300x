<?php

function config(array $array, string $path, mixed $default = null): mixed
{
    $segments = explode('.', $path);
    $current = $array;

    foreach ($segments as $segment) {
        if (!is_array($current) || !array_key_exists($segment, $current)) {
            return $default;
        }
        $current = $current[$segment];
    }

    return $current;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lang($id)
{
    global $LANG;

    return isset($LANG[$id]) ? $LANG[$id] : 'LANG_'.$id;
}