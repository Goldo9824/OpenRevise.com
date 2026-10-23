<?php

/*
| First deploy only (see scripts/first-deploy.sh). GitHub uploads this file to
| the hosting root next to one archive of the whole site, then calls it over
| HTTPS until it answers "done". It:
|
|   1. extracts the archive into a hidden folder,
|   2. deletes everything else in the hosting root, except the paths in KEEP
|      (the .env file, lesson files, uploaded diagrams, SSL certificate checks),
|   3. moves the new files into place,
|   4. deletes the archive, its own working files and itself.
|
| Each call works for a few seconds and saves where it got to, so no request
| runs into the host's time limits, and a call that is cut off is simply
| redone. Only calls carrying the token whose hash is below are accepted, and
| the file stops working two hours after it was uploaded.
|
| The values in %% are filled in by scripts/first-deploy.sh.
*/

const TOKEN_HASH = '%%TOKEN_HASH%%';
const RELEASE = '%%RELEASE%%';
const KEEP = ['.well-known', 'openrevise-core/.env', 'openrevise-core/storage', '%%APP_DIR%%/media'];
const SECONDS_PER_CALL = 15;
const LIFETIME = 7200;

$root = __DIR__;
$archive = $root.'/.openrevise-release-'.RELEASE.'.zip';
$staging = $root.'/.openrevise-release-'.RELEASE;
$progressFile = $root.'/.openrevise-release-'.RELEASE.'.json';
$own = [basename(__FILE__), basename($archive), basename($staging), basename($progressFile)];
$deadline = microtime(true) + SECONDS_PER_CALL;

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

function answer($status, $text)
{
    http_response_code($status);
    echo $text, "\n";
    exit;
}

function timeUp()
{
    return microtime(true) > $GLOBALS['deadline'];
}

function relative($path)
{
    return ltrim(substr($path, strlen($GLOBALS['root'])), '/');
}

// Deletes a file or a folder and everything in it. Returns false when the time
// for this call ran out first; what could not be deleted is added to $failed.
function removeTree($path, array &$failed)
{
    if (is_link($path) || is_file($path)) {
        if (! @unlink($path)) {
            $failed[] = relative($path);
        }

        return true;
    }
    if (! is_dir($path)) {
        return true;
    }

    try {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        $count = 0;
        foreach ($items as $item) {
            $file = $item->getPathname();
            $deleted = $item->isDir() && ! $item->isLink() ? @rmdir($file) : @unlink($file);
            if (! $deleted) {
                $failed[] = relative($file);
            }
            if (++$count % 200 === 0 && timeUp()) {
                return false;
            }
        }
    } catch (UnexpectedValueException $e) {
        // A folder that cannot be opened: reported below with its parent.
    }
    if (! @rmdir($path)) {
        $failed[] = relative($path);
    }

    return true;
}

// Whether a folder holds one of the kept paths, so it must be emptied around
// them rather than deleted.
function holdsKept($relativePath)
{
    foreach (KEEP as $kept) {
        if (strpos($kept, $relativePath.'/') === 0) {
            return true;
        }
    }

    return false;
}

// Paths that could not be deleted are skipped from then on, so every call
// deletes something until nothing is left.
function wipe($dir, array $own, array &$failed)
{
    foreach (scandir($dir) ?: [] as $name) {
        $path = $dir.'/'.$name;
        $relativePath = relative($path);
        if ($name === '.' || $name === '..' || in_array($relativePath, $own, true)
            || in_array($relativePath, KEEP, true) || in_array($relativePath, $failed, true)) {
            continue;
        }
        if (is_dir($path) && ! is_link($path) && holdsKept($relativePath)) {
            if (! wipe($path, $own, $failed)) {
                return false;
            }
        } elseif (! removeTree($path, $failed) || timeUp()) {
            return false;
        }
    }

    return true;
}

// Moves the extracted files into place. Folders that are still there (the
// ones holding kept paths) are merged; files are replaced.
function moveInto($from, $to)
{
    foreach (scandir($from) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $source = $from.'/'.$name;
        $target = $to.'/'.$name;
        if (is_dir($source) && ! is_link($source) && is_dir($target) && ! is_link($target)) {
            if (! moveInto($source, $target)) {
                return false;
            }
            @rmdir($source);
        } elseif (! @rename($source, $target)) {
            answer(500, 'error: could not move '.relative($target).' into place. Delete it in the LWS file manager and run the deploy again.');
        }
        if (timeUp()) {
            return false;
        }
    }

    return true;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || ! hash_equals(TOKEN_HASH, hash('sha256', (string) ($_POST['token'] ?? '')))) {
    answer(403, 'Forbidden.');
}
if (time() - filemtime(__FILE__) > LIFETIME) {
    answer(410, 'error: this unpacker has expired. Run the deploy again.');
}
ignore_user_abort(true);
@set_time_limit(120);

// Where earlier calls got to. The lock stops two calls working at once.
$handle = fopen($progressFile, 'c+');
if (! $handle || ! flock($handle, LOCK_EX | LOCK_NB)) {
    answer(409, 'busy');
}
$progress = json_decode(stream_get_contents($handle), true) ?: ['step' => 'extract', 'next' => 0];

$save = function (array $next) use ($handle, &$progress) {
    $progress = $next;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($progress));
    fflush($handle);
};

// 1. Extract into the hidden folder, a batch of entries at a time.
if ($progress['step'] === 'extract') {
    if (! is_file($archive)) {
        answer(500, 'error: the archive is missing. Run the deploy again.');
    }
    $failed = [];
    if ($progress['next'] === 0 && file_exists($staging) && ! removeTree($staging, $failed)) {
        answer(200, 'continue: clearing an earlier attempt');
    }
    @mkdir($staging, 0755);

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($archive) !== true) {
            answer(500, 'error: the archive cannot be opened. Run the deploy again.');
        }
        // At least one batch per call, so every call moves forward.
        $total = $zip->numFiles;
        while ($progress['next'] < $total) {
            $names = [];
            for ($i = $progress['next']; $i < min($total, $progress['next'] + 250); $i++) {
                $names[] = $zip->getNameIndex($i);
            }
            if (! $zip->extractTo($staging, $names)) {
                answer(500, 'error: could not extract '.$names[0].' (is the hosting space full?)');
            }
            $save(['step' => 'extract', 'next' => $i]);
            if (timeUp()) {
                break;
            }
        }
        $zip->close();
        if ($progress['next'] < $total) {
            answer(200, "continue: extracted {$progress['next']} of {$total} entries");
        }
    } else {
        // Without the zip extension, PHP's own Phar reader does it in one go.
        try {
            (new PharData($archive))->extractTo($staging, null, true);
        } catch (Exception $e) {
            answer(500, 'error: could not extract the archive: '.$e->getMessage());
        }
    }
    $save(['step' => 'wipe']);
}

// 2. Delete what was on the server before.
if ($progress['step'] === 'wipe') {
    $failed = $progress['failed'] ?? [];
    $finished = wipe($root, $own, $failed);
    $failed = array_values(array_unique($failed));
    if (! $finished) {
        $save(['step' => 'wipe', 'failed' => $failed]);
        answer(200, 'continue: deleting the files already on the server');
    }
    $save(['step' => 'move', 'failed' => $failed]);
}

// 3. Move the new files into place.
if ($progress['step'] === 'move') {
    if (! moveInto($staging, $root)) {
        answer(200, 'continue: moving the new files into place');
    }
    $save(['step' => 'finish', 'failed' => $progress['failed']]);
}

// 4. Clean up and stop working for good.
$failed = [];
removeTree($staging, $failed);
@unlink($archive);
if (function_exists('opcache_reset')) {
    @opcache_reset();
}
flock($handle, LOCK_UN);
fclose($handle);
@unlink($progressFile);
@unlink(__FILE__);

$notDeleted = $progress['failed'] ?? [];
answer(200, $notDeleted ? "done\nCould not delete: ".implode(', ', array_slice($notDeleted, 0, 50)) : 'done');
