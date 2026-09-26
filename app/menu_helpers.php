<?php

declare(strict_types=1);

final class MenuDataException extends RuntimeException
{
}

function menu_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function menu_anchor_id(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug === '' ? 'category' : 'category-' . $slug;
}

function format_toman(?int $price): string
{
    if ($price === null) {
        return 'ناموجود';
    }

    static $formatter = null;
    if ($formatter === null && class_exists(NumberFormatter::class)) {
        $formatter = new NumberFormatter('fa_IR', NumberFormatter::DECIMAL);
    }

    $number = $formatter instanceof NumberFormatter
        ? $formatter->format($price)
        : number_format($price);

    return $number . ' تومان';
}
