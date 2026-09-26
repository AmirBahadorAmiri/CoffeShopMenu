<?php

declare(strict_types=1);

final class AdminAuth
{
    public static function start(array $adminConfig): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($adminConfig['session_name']);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure,
        ]);
        session_start();

        $timeout = (int) $adminConfig['idle_minutes'] * 60;
        if (isset($_SESSION['admin_last']) && (time() - (int) $_SESSION['admin_last'] > $timeout)) {
            self::destroy();
            session_start();
        }
        $_SESSION['admin_last'] = time();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['admin_user']) && $_SESSION['admin_user'] === true;
    }

    public static function login(): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = true;
        $_SESSION['admin_last'] = time();
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function logout(): void
    {
        self::destroy();
    }

    public static function requireLogin(string $loginUrl = 'login.php'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['admin_csrf'];
    }

    public static function validateCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['admin_csrf'])
            && hash_equals((string) $_SESSION['admin_csrf'], $token);
    }

    public static function flash(string $text, string $type = 'ok'): void
    {
        $_SESSION['admin_flash'] = ['type' => $type, 'text' => $text];
    }

    public static function getFlash(): ?array
    {
        if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
            return null;
        }

        $flash = $_SESSION['admin_flash'];
        unset($_SESSION['admin_flash']);

        return $flash;
    }
}
