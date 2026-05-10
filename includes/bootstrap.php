<?php

if (defined('APP_BOOTSTRAP_LOADED')) {
    return;
}

define('APP_BOOTSTRAP_LOADED', true);

function appErrorEscape(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function appIsAdminRequest(): bool {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_starts_with($uri, '/admin') || str_contains($script, '/admin/');
}

function appFriendlyErrorType(int $type): string {
    return match ($type) {
        E_ERROR => 'Fatal runtime error',
        E_PARSE => 'Parse error',
        E_CORE_ERROR => 'Core error',
        E_COMPILE_ERROR => 'Compile error',
        E_USER_ERROR => 'Application error',
        E_RECOVERABLE_ERROR => 'Recoverable fatal error',
        default => 'Fatal error',
    };
}

function appRenderErrorPage(string $title, string $summary, array $details = []): void {
    if (($GLOBALS['app_error_page_rendered'] ?? false) === true) {
        return;
    }

    $GLOBALS['app_error_page_rendered'] = true;

    if (PHP_SAPI === 'cli') {
        $lines = [$title, $summary];
        foreach ($details as $label => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $lines[] = $label . ': ' . $value;
        }
        fwrite(STDERR, implode(PHP_EOL, $lines) . PHP_EOL);
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);

    $isAdmin = appIsAdminRequest();
    $backHref = $isAdmin ? '/admin/' : '/';
    $backLabel = $isAdmin ? 'Back to admin' : 'Back to home';
    $supportLabel = $isAdmin ? 'Admin request failed' : 'Page request failed';

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . appErrorEscape($title) . ' | Waves</title>';
    echo '<style>';
    echo ':root{color-scheme:light;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}';
    echo 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px;background:radial-gradient(circle at top,#cde9f4 0,#f5f1e8 46%,#f8fafc 100%);color:#0f172a;}';
    echo '.shell{width:min(760px,100%);background:rgba(255,255,255,.92);border:1px solid rgba(15,23,42,.08);border-radius:28px;box-shadow:0 24px 80px rgba(15,23,42,.14);overflow:hidden;}';
    echo '.top{padding:28px 32px 20px;background:linear-gradient(135deg,#004b7c 0,#0ea5c6 100%);color:#f8fafc;}';
    echo '.eyebrow{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:rgba(255,255,255,.16);font-size:12px;letter-spacing:.08em;text-transform:uppercase;}';
    echo 'h1{margin:18px 0 8px;font-size:42px;line-height:1.02;letter-spacing:0;}';
    echo '.summary{margin:0;max-width:54ch;font-size:16px;line-height:1.6;color:rgba(248,250,252,.88);}';
    echo '.body{padding:28px 32px 32px;}';
    echo '.details{display:grid;gap:12px;margin:0 0 24px;padding:0;list-style:none;}';
    echo '.detail{padding:14px 16px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;}';
    echo '.detail strong{display:block;margin-bottom:6px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#0369a1;}';
    echo '.detail code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:14px;word-break:break-word;color:#0f172a;}';
    echo '.actions{display:flex;gap:12px;flex-wrap:wrap;}';
    echo '.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:999px;text-decoration:none;font-weight:600;}';
    echo '.btn-primary{background:#004b7c;color:#fff;}';
    echo '.btn-secondary{background:#e0f2fe;color:#075985;}';
    echo '.note{margin:24px 0 0;font-size:14px;line-height:1.6;color:#475569;}';
    echo '@media (max-width:640px){body{padding:16px}.top,.body{padding:22px}}';
    echo '</style></head><body><main class="shell">';
    echo '<section class="top">';
    echo '<span class="eyebrow">' . appErrorEscape($supportLabel) . '</span>';
    echo '<h1>' . appErrorEscape($title) . '</h1>';
    echo '<p class="summary">' . appErrorEscape($summary) . '</p>';
    echo '</section><section class="body">';

    if ($details !== []) {
        echo '<ul class="details">';
        foreach ($details as $label => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            echo '<li class="detail"><strong>' . appErrorEscape($label) . '</strong><code>' . appErrorEscape($value) . '</code></li>';
        }
        echo '</ul>';
    }

    echo '<div class="actions">';
    echo '<a class="btn btn-primary" href="' . appErrorEscape($backHref) . '">' . appErrorEscape($backLabel) . '</a>';
    echo '<a class="btn btn-secondary" href="javascript:location.reload()">Reload page</a>';
    echo '</div>';
    echo '<p class="note">The request was interrupted before the page finished rendering. Check the application logs after you reproduce it.</p>';
    echo '</section></main></body></html>';
}

function appRenderThrowablePage(Throwable $throwable): void {
    appRenderErrorPage(
        'Unhandled exception',
        $throwable->getMessage() !== '' ? $throwable->getMessage() : 'The request crashed before it could render normally.',
        [
            'Type' => $throwable::class,
            'File' => $throwable->getFile(),
            'Line' => (string) $throwable->getLine(),
        ]
    );
}

function appRegisterErrorHandlers(): void {
    if (defined('APP_ERROR_HANDLERS_REGISTERED')) {
        return;
    }

    define('APP_ERROR_HANDLERS_REGISTERED', true);

    set_exception_handler(function (Throwable $throwable): void {
        appRenderThrowablePage($throwable);
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();
        if (!$error) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (!in_array($error['type'] ?? 0, $fatalTypes, true)) {
            return;
        }

        appRenderErrorPage(
            'Fatal error',
            $error['message'] ?? 'The request crashed before it could render normally.',
            [
                'Type' => appFriendlyErrorType((int) ($error['type'] ?? 0)),
                'File' => $error['file'] ?? '',
                'Line' => isset($error['line']) ? (string) $error['line'] : '',
            ]
        );
    });
}

appRegisterErrorHandlers();
