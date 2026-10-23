<?php

/*
|--------------------------------------------------------------------------
| OpenRevise installer
|--------------------------------------------------------------------------
|
| A one-time setup wizard at openrevise.com/install.php. It is plain PHP, so
| it works before Laravel is configured:
|
|   1. Install key      proves you control the hosting (the key is in a file)
|   2. Requirements     PHP version, extensions, writable folders
|   3. Site             domain, owner name and contact email
|   4. Databases        the content, admin and analytics databases
|   5. Admin account    the single admin's sign-in
|   6. Install          writes .env, creates the tables, then locks itself
|
| Once finished it writes openrevise-core/storage/install.lock and refuses to
| run again. Deleting that file reopens it; it never drops existing tables.
*/

declare(strict_types=1);

const OR_MIN_PHP = '8.3.0';

// ---- Where things are ------------------------------------------------------

$core = null;
foreach ([__DIR__.'/openrevise-core', __DIR__.'/../..'] as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php')) {
        $core = realpath($candidate);
        break;
    }
}

if ($core === null) {
    or_page('Installer', '<p>The <code>openrevise-core</code> folder was not found next to this file. Deploy the site first, then come back to this page.</p>');
    exit;
}

$lockFile = $core.'/storage/install.lock';
$keyFile = $core.'/storage/install-key.txt';
$envFile = $core.'/.env';

if (is_file($lockFile)) {
    http_response_code(403);
    or_page('Already installed', '<p>OpenRevise is already installed, so this installer is locked.</p><p>To run it again (for example to change the database passwords), delete <code>openrevise-core/storage/install.lock</code> with the LWS file manager or FTP, then reload this page. Existing tables and content are never deleted.</p>');
    exit;
}

// ---- Session and the install key ------------------------------------------

session_name('openrevise_install');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$_SESSION['data'] ??= or_existing_env($envFile);

if (! is_file($keyFile)) {
    @mkdir(dirname($keyFile), 0755, true);
    @file_put_contents($keyFile, bin2hex(random_bytes(12))."\n");
}

$step = $_SESSION['step'] ?? 'key';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'back') {
            $order = ['key', 'requirements', 'site', 'databases', 'admin', 'install'];
            $i = array_search($step, $order, true);
            $step = $order[max(1, (int) $i - 1)];
        } elseif ($step === 'key') {
            $expected = trim((string) @file_get_contents($keyFile));
            if ($expected !== '' && hash_equals($expected, trim((string) ($_POST['key'] ?? '')))) {
                session_regenerate_id(true);
                $step = 'requirements';
            } else {
                sleep(2);
                $errors[] = 'That is not the install key.';
            }
        } elseif ($step === 'requirements') {
            if (or_requirements_ok($core)) {
                $step = 'site';
            } else {
                $errors[] = 'Fix the items marked in red first.';
            }
        } elseif ($step === 'site') {
            $domain = strtolower(trim((string) ($_POST['domain'] ?? '')));
            $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));
            $domain = preg_replace('/^www\./', '', $domain);
            $email = trim((string) ($_POST['contact_email'] ?? ''));

            if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain) && ! str_ends_with($domain, '.localhost')) {
                $errors[] = 'Enter the main domain, like openrevise.com.';
            }
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'The contact email is not valid.';
            }

            $_SESSION['data']['domain'] = $domain;
            $_SESSION['data']['owner_name'] = trim((string) ($_POST['owner_name'] ?? ''));
            $_SESSION['data']['contact_email'] = $email;

            if (! $errors) {
                $step = 'databases';
            }
        } elseif ($step === 'databases') {
            foreach (['content', 'admin', 'analytics'] as $db) {
                foreach (['host', 'name', 'user', 'password'] as $field) {
                    $_SESSION['data']['db'][$db][$field] = trim((string) ($_POST[$db.'_'.$field] ?? ''));
                }
                $error = or_test_database($_SESSION['data']['db'][$db]);
                if ($error) {
                    $errors[] = ucfirst($db).' database: '.$error;
                }
            }

            if (! $errors) {
                $step = 'admin';
            }
        } elseif ($step === 'admin') {
            $name = trim((string) ($_POST['admin_name'] ?? ''));
            $email = trim((string) ($_POST['admin_email'] ?? ''));
            $password = (string) ($_POST['admin_password'] ?? '');

            if ($name === '') {
                $errors[] = 'Enter a name.';
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address: it is your sign-in.';
            }
            if (strlen($password) < 12) {
                $errors[] = 'The password must be at least 12 characters.';
            }
            if ($password !== (string) ($_POST['admin_password_confirm'] ?? '')) {
                $errors[] = 'The two passwords do not match.';
            }

            $_SESSION['data']['admin'] = ['name' => $name, 'email' => $email];
            $_SESSION['data']['seed'] = isset($_POST['seed']);

            if (! $errors) {
                $_SESSION['admin_password'] = $password;
                $step = 'install';
            }
        } elseif ($step === 'install') {
            $result = or_install($core, $envFile, $lockFile, $keyFile, $_SESSION['data'], (string) ($_SESSION['admin_password'] ?? ''));

            if ($result['ok']) {
                $data = $_SESSION['data'];
                $_SESSION = [];
                session_destroy();
                or_page('Installed', or_done_html($data, $result['log']));
                exit;
            }

            $errors[] = $result['error'];
        }
    }

    $_SESSION['step'] = $step;
}

or_page('Set up OpenRevise', or_step_html($step, $core, $keyFile, $errors));

// ---- Steps ----------------------------------------------------------------

function or_step_html(string $step, string $core, string $keyFile, array $errors): string
{
    $d = $_SESSION['data'];
    $html = or_progress($step);

    if ($errors) {
        $html .= '<div class="alert">'.implode('<br>', array_map('or_e', $errors)).'</div>';
    }

    $form = fn (string $inner, string $button, bool $back = true) => '<form method="post"><input type="hidden" name="csrf" value="'.or_e($_SESSION['csrf']).'">'.$inner
        .'<div class="actions">'.($back ? '<button name="action" value="back" class="btn ghost" formnovalidate>Back</button>' : '').'<button name="action" value="next" class="btn">'.$button.'</button></div></form>';

    switch ($step) {
        case 'key':
            $relative = 'openrevise-core/storage/'.basename($keyFile);

            return $html.'<h1>Welcome</h1><p>This wizard sets up OpenRevise on this hosting space in a few minutes. To prove you control the hosting, open this file with the LWS file manager or FTP and copy the key inside it:</p><p><code>'.or_e($relative).'</code></p>'
                .$form('<label>Install key<input name="key" autocomplete="off" required autofocus></label>', 'Continue', false);

        case 'requirements':
            $rows = '';
            foreach (or_requirements($core) as [$label, $ok, $detail]) {
                $rows .= '<tr><td>'.or_e($label).'</td><td class="'.($ok ? 'ok' : 'bad').'">'.($ok ? 'OK' : 'Missing').'</td><td>'.or_e($detail).'</td></tr>';
            }

            return $html.'<h1>Requirements</h1><p>OpenRevise needs these on the server. On LWS, PHP version and extensions are set in the panel under the PHP configuration of the hosting.</p><table>'.$rows.'</table>'
                .$form('', 'Continue');

        case 'site':
            $domain = $d['domain'] ?? preg_replace('/^www\./', '', strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'openrevise.com')[0]));

            return $html.'<h1>Your site</h1>'
                .$form('<label>Main domain<input name="domain" value="'.or_e($domain).'" required><small>The app, API and admin live on app., api. and admin. of this domain.</small></label>'
                    .'<label>Owner name<input name="owner_name" value="'.or_e($d['owner_name'] ?? '').'"><small>Shown on the About and Privacy pages as the person responsible for the site.</small></label>'
                    .'<label>Contact email<input name="contact_email" type="email" value="'.or_e($d['contact_email'] ?? '').'"><small>Shown on the Contact and Privacy pages.</small></label>', 'Continue');

        case 'databases':
            $fields = '';
            foreach (['content' => 'Notes, questions and mock exams', 'admin' => 'Admin account, API keys, logs and settings', 'analytics' => 'Page views and daily totals'] as $db => $what) {
                $v = $d['db'][$db] ?? [];
                $fields .= '<fieldset><legend>'.ucfirst($db).' database <span>'.$what.'</span></legend><div class="grid">'
                    .'<label>Host<input name="'.$db.'_host" value="'.or_e($v['host'] ?? 'localhost').'" required></label>'
                    .'<label>Database name<input name="'.$db.'_name" value="'.or_e($v['name'] ?? '').'" required></label>'
                    .'<label>User<input name="'.$db.'_user" value="'.or_e($v['user'] ?? '').'" required></label>'
                    .'<label>Password<input name="'.$db.'_password" type="password" value="'.or_e($v['password'] ?? '').'"></label>'
                    .'</div></fieldset>';
            }

            return $html.'<h1>Databases</h1><p>Create three MySQL databases in the LWS panel, each with its own user, and enter them here. Each connection is tested before you continue.</p>'
                .$form($fields, 'Test and continue');

        case 'admin':
            $a = $d['admin'] ?? [];

            return $html.'<h1>Admin account</h1><p>The single account for admin.'.or_e($d['domain'] ?? '').'. You can add a passkey from the admin settings afterwards.</p>'
                .$form('<label>Name<input name="admin_name" value="'.or_e($a['name'] ?? '').'" required></label>'
                    .'<label>Email (your sign-in)<input name="admin_email" type="email" value="'.or_e($a['email'] ?? '').'" required></label>'
                    .'<label>Password<input name="admin_password" type="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters.</small></label>'
                    .'<label>Password again<input name="admin_password_confirm" type="password" minlength="12" autocomplete="new-password" required></label>'
                    .'<label class="check"><input type="checkbox" name="seed" '.(($d['seed'] ?? true) ? 'checked' : '').'> Add the launch exam boards (AQA, Edexcel, OCR) and subjects. They can be edited or removed later.</label>', 'Continue');

        default:
            return $html.'<h1>Ready to install</h1><p>The installer will now:</p><ul><li>write the settings file <code>openrevise-core/.env</code>;</li><li>create the tables in the three databases (existing tables are kept);</li>'
                .(($d['seed'] ?? false) ? '<li>add the launch exam boards and subjects;</li>' : '')
                .'<li>create the admin account for '.or_e($d['admin']['email'] ?? '').';</li><li>lock itself so it cannot run again.</li></ul>'
                .$form('', 'Install OpenRevise');
    }
}

function or_progress(string $step): string
{
    $steps = ['key' => 'Key', 'requirements' => 'Requirements', 'site' => 'Site', 'databases' => 'Databases', 'admin' => 'Admin', 'install' => 'Install'];
    $out = '<ol class="progress">';
    $reached = true;
    foreach ($steps as $key => $label) {
        $out .= '<li class="'.($key === $step ? 'current' : ($reached ? 'done' : '')).'">'.$label.'</li>';
        if ($key === $step) {
            $reached = false;
        }
    }

    return $out.'</ol>';
}

// ---- Checks ------------------------------------------------------------------

/** @return list<array{0: string, 1: bool, 2: string}> */
function or_requirements(string $core): array
{
    $rows = [['PHP '.OR_MIN_PHP.' or newer', version_compare(PHP_VERSION, OR_MIN_PHP, '>='), 'This server runs PHP '.PHP_VERSION]];

    foreach (['pdo_mysql', 'intl', 'gd', 'mbstring', 'openssl', 'tokenizer', 'xml', 'dom', 'ctype', 'fileinfo', 'filter', 'curl', 'session', 'zip'] as $ext) {
        $rows[] = ["PHP extension $ext", extension_loaded($ext), $ext === 'zip' ? 'Used by Filament for exports' : ''];
    }

    @mkdir($core.'/bootstrap/cache', 0755, true);
    foreach (['storage', 'storage/framework', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/lessons', 'storage/app/private'] as $dir) {
        @mkdir($core.'/'.$dir, 0755, true);
    }

    foreach (['' => 'openrevise-core (for the .env file)', '/storage' => 'openrevise-core/storage', '/bootstrap/cache' => 'openrevise-core/bootstrap/cache'] as $path => $label) {
        $rows[] = ["Writable: $label", is_writable($core.$path), ''];
    }

    return $rows;
}

function or_requirements_ok(string $core): bool
{
    foreach (or_requirements($core) as [$label, $ok]) {
        if (! $ok && ! str_contains($label, ' zip')) {
            return false;
        }
    }

    return true;
}

/** @param array{host: string, name: string, user: string, password: string} $db */
function or_test_database(array $db): ?string
{
    if ($db['name'] === '' || $db['user'] === '') {
        return 'enter the database name and user.';
    }

    try {
        $pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['name'].';charset=utf8mb4', $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->query('SELECT 1');

        return null;
    } catch (Throwable $e) {
        return 'could not connect ('.$e->getMessage().').';
    }
}

// ---- Install -----------------------------------------------------------------

/** @return array{ok: bool, error?: string, log?: string} */
function or_install(string $core, string $envFile, string $lockFile, string $keyFile, array $d, string $password): array
{
    if ($password === '' || empty($d['admin']['email']) || empty($d['db'])) {
        return ['ok' => false, 'error' => 'Some details are missing. Go back through the steps.'];
    }

    $existing = or_parse_env($envFile);
    $https = ! str_ends_with($d['domain'], '.localhost');
    $mediaDir = __DIR__.'/app.'.$d['domain'].'/media';

    $values = [
        'APP_NAME' => 'OpenRevise',
        'APP_ENV' => 'production',
        'APP_KEY' => $existing['APP_KEY'] ?? 'base64:'.base64_encode(random_bytes(32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => ($https ? 'https://' : 'http://').$d['domain'],
        'APP_TIMEZONE' => 'Europe/London',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'BCRYPT_ROUNDS' => '12',
        'OPENREVISE_DOMAIN' => $d['domain'],
        'OPENREVISE_SCHEME' => $https ? 'https' : 'http',
        'OPENREVISE_MEDIA_PATH' => is_dir(dirname($mediaDir)) ? $mediaDir : ($existing['OPENREVISE_MEDIA_PATH'] ?? ''),
        'OPENREVISE_OWNER_NAME' => $d['owner_name'] ?? '',
        'OPENREVISE_CONTACT_EMAIL' => $d['contact_email'] ?? '',
        'DB_DRIVER' => 'mysql',
        'DB_PORT' => '3306',
    ];

    foreach (['content', 'admin', 'analytics'] as $db) {
        $prefix = strtoupper($db);
        $values["{$prefix}_DB_HOST"] = $d['db'][$db]['host'];
        $values["{$prefix}_DB_DATABASE"] = $d['db'][$db]['name'];
        $values["{$prefix}_DB_USERNAME"] = $d['db'][$db]['user'];
        $values["{$prefix}_DB_PASSWORD"] = $d['db'][$db]['password'];
    }

    $values += [
        'LOG_CHANNEL' => 'stack',
        'LOG_STACK' => 'daily',
        'LOG_LEVEL' => 'warning',
        'SESSION_DRIVER' => 'file',
        'SESSION_LIFETIME' => '60',
        'SESSION_ENCRYPT' => 'true',
        'SESSION_SECURE_COOKIE' => $https ? 'true' : 'false',
        'CACHE_STORE' => 'file',
        'QUEUE_CONNECTION' => 'sync',
        'FILESYSTEM_DISK' => 'local',
        'BROADCAST_CONNECTION' => 'log',
        'MAIL_MAILER' => 'log',
    ];

    $env = "# Written by the OpenRevise installer on ".date('Y-m-d H:i').". Never commit this file.\n";
    foreach ($values as $key => $value) {
        $env .= $key.'='.or_env_value((string) $value)."\n";
    }

    if (@file_put_contents($envFile, $env) === false) {
        return ['ok' => false, 'error' => 'Could not write openrevise-core/.env. Check that the folder is writable.'];
    }
    @chmod($envFile, 0600);

    if ($values['OPENREVISE_MEDIA_PATH'] !== '') {
        @mkdir($values['OPENREVISE_MEDIA_PATH'], 0755, true);
    }

    // Now Laravel can start: create the tables and the admin account.
    try {
        require_once $core.'/vendor/autoload.php';
        $app = require $core.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $output = new Symfony\Component\Console\Output\BufferedOutput;
        $run = function (string $command, array $args = []) use ($kernel, $output) {
            $code = $kernel->call($command, $args, $output);
            if ($code !== 0) {
                throw new RuntimeException("\"$command\" failed:\n".$output->fetch());
            }
        };

        $run('migrate', ['--force' => true]);
        if (! empty($d['seed'])) {
            $run('db:seed', ['--class' => 'Database\\Seeders\\LaunchSeeder', '--force' => true]);
        }
        $run('openrevise:admin', [
            '--email' => $d['admin']['email'],
            '--name' => $d['admin']['name'],
            '--password' => $password,
        ]);

        $log = $output->fetch();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Installing failed: '.$e->getMessage()];
    }

    file_put_contents($lockFile, 'Installed '.date('c')."\n");
    @unlink($keyFile);

    return ['ok' => true, 'log' => $log];
}

function or_done_html(array $d, string $log): string
{
    $scheme = str_ends_with($d['domain'], '.localhost') ? 'http://' : 'https://';
    $admin = $scheme.'admin.'.$d['domain'].'/login';

    return '<h1>OpenRevise is installed</h1><p>The installer has locked itself.</p>'
        .'<ul><li>Sign in to the admin panel: <a href="'.or_e($admin).'">'.or_e($admin).'</a></li>'
        .'<li>Students use <a href="'.or_e($scheme.'app.'.$d['domain']).'">'.or_e('app.'.$d['domain']).'</a></li>'
        .'<li>Claude uses <a href="'.or_e($scheme.'api.'.$d['domain']).'">'.or_e('api.'.$d['domain']).'</a> with a key made in the admin panel.</li></ul>'
        .'<h2>Two last things</h2><ol><li>In the LWS panel, add a daily cron job running <code>php '.or_e(realpath(__DIR__.'/openrevise-core') ?: 'openrevise-core').'/artisan openrevise:analytics-rollup</code>. (Without it, the dashboard catches up when you open it.)</li>'
        .'<li>After a deploy that adds database changes, run <code>php artisan migrate --force</code> in the openrevise-core folder from the LWS web terminal.</li></ol>'
        .($log !== '' ? '<details><summary>Details</summary><pre>'.or_e($log).'</pre></details>' : '');
}

// ---- Helpers -----------------------------------------------------------------

function or_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function or_env_value(string $value): string
{
    if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+=-]+$/', $value)) {
        return $value;
    }

    return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
}

/** @return array<string, string> */
function or_parse_env(string $file): array
{
    $values = [];
    foreach (is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
        if (str_starts_with(ltrim($line), '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (strlen($value) > 1 && $value[0] === '"' && str_ends_with($value, '"')) {
            $value = stripcslashes(substr($value, 1, -1));
        }
        $values[trim($key)] = $value;
    }

    return $values;
}

/** Re-running after an earlier install: start from what is already set. */
function or_existing_env(string $file): array
{
    $env = or_parse_env($file);
    $data = [
        'domain' => $env['OPENREVISE_DOMAIN'] ?? null,
        'owner_name' => $env['OPENREVISE_OWNER_NAME'] ?? '',
        'contact_email' => $env['OPENREVISE_CONTACT_EMAIL'] ?? '',
        'seed' => ! is_file($file),
    ];

    foreach (['content', 'admin', 'analytics'] as $db) {
        $prefix = strtoupper($db);
        if (isset($env["{$prefix}_DB_DATABASE"])) {
            $data['db'][$db] = [
                'host' => $env["{$prefix}_DB_HOST"] ?? 'localhost',
                'name' => $env["{$prefix}_DB_DATABASE"],
                'user' => $env["{$prefix}_DB_USERNAME"] ?? '',
                'password' => $env["{$prefix}_DB_PASSWORD"] ?? '',
            ];
        }
    }

    return array_filter($data, fn ($v) => $v !== null);
}

function or_page(string $title, string $body): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

    echo '<!DOCTYPE html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>'.or_e($title).' | OpenRevise</title><style>'
        .'*{box-sizing:border-box}body{margin:0;background:#f8faf9;color:#374151;font:15px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif}'
        .'main{max-width:40rem;margin:0 auto;padding:3rem 1.25rem}.logo{font-weight:800;font-size:1.6rem;letter-spacing:-.045em;text-align:center;margin:0 0 1.5rem}.logo b{color:#111827}.logo span{color:#0f6b56}'
        .'.card{background:#fff;border:1px solid #e5e7eb;border-radius:.9rem;padding:2rem}h1{color:#111827;font-size:1.5rem;margin:.5rem 0 1rem;letter-spacing:-.02em}h2{font-size:1.1rem;color:#111827;margin-top:1.5rem}'
        .'label{display:grid;gap:.35rem;font-weight:600;color:#111827;font-size:.9rem;margin:0 0 1rem}label small{font-weight:400;color:#6b7280}label.check{display:flex;gap:.6rem;align-items:flex-start;font-weight:500}'
        .'input{font:inherit;padding:.6rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;width:100%}input[type=checkbox]{width:auto;margin-top:.25rem}input:focus{outline:2px solid #0f6b56;outline-offset:0;border-color:#0f6b56}'
        .'fieldset{border:1px solid #e5e7eb;border-radius:.7rem;padding:1rem 1rem 0;margin:0 0 1rem}legend{font-weight:700;color:#111827;padding:0 .4rem}legend span{font-weight:400;color:#6b7280;font-size:.85rem}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 1rem}'
        .'.actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem}.btn{font:inherit;font-weight:600;border:1px solid #0f6b56;background:#0f6b56;color:#fff;padding:.65rem 1.2rem;border-radius:.55rem;cursor:pointer}.btn.ghost{background:#fff;color:#0b5546}'
        .'.alert{background:#fff4ed;border:1px solid #fdba74;color:#9a3412;border-radius:.6rem;padding:.75rem 1rem;margin-bottom:1rem}table{width:100%;border-collapse:collapse;font-size:.88rem}td{border-bottom:1px solid #eef0f2;padding:.45rem .3rem}.ok{color:#15803d;font-weight:700}.bad{color:#b91c1c;font-weight:700}'
        .'code{background:#f3f4f6;border-radius:.3rem;padding:.1rem .35rem;font-size:.85em;word-break:break-all}pre{background:#111827;color:#e5e7eb;padding:1rem;border-radius:.5rem;overflow:auto;font-size:.8rem}a{color:#0f6b56}'
        .'.progress{display:flex;flex-wrap:wrap;gap:.35rem;list-style:none;padding:0;margin:0 0 1.25rem;font-size:.75rem}.progress li{padding:.15rem .6rem;border-radius:1rem;background:#f3f4f6;color:#6b7280}.progress li.done{background:#e4f0ea;color:#0b5546}.progress li.current{background:#0f6b56;color:#fff}'
        .'@media(max-width:560px){.grid{grid-template-columns:1fr}.card{padding:1.25rem}}</style></head><body><main><p class="logo"><b>open</b><span>revise</span></p><div class="card">'.$body.'</div></main></body></html>';
}
