<?php
/**
 * Simple file-based cache for raffle endpoints
 * @example
 *   $val = raffle_cache_get('settings:abc123');
 *   if ($val === null) {
 *     $val = expensive_query();
 *     raffle_cache_set('settings:abc123', $val, 30);
 *   }
 */
define('RAFFLE_CACHE_DIR', sys_get_temp_dir() . '/raffle_cache');

if (!is_dir(RAFFLE_CACHE_DIR)) {
    @mkdir(RAFFLE_CACHE_DIR, 0775, true);
}

function raffle_cache_get($key) {
    $f = RAFFLE_CACHE_DIR . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.cache';
    if (!file_exists($f)) return null;
    $raw = @file_get_contents($f);
    if ($raw === false) return null;
    $data = @unserialize($raw);
    if (!is_array($data) || !isset($data['exp'], $data['val'])) return null;
    if ($data['exp'] < time()) {
        @unlink($f);
        return null;
    }
    return $data['val'];
}

function raffle_cache_set($key, $val, $ttl = 30) {
    $f = RAFFLE_CACHE_DIR . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.cache';
    @file_put_contents($f, serialize(['val' => $val, 'exp' => time() + max(1, (int)$ttl)]));
}

function raffle_cache_delete($key) {
    $f = RAFFLE_CACHE_DIR . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.cache';
    if (file_exists($f)) @unlink($f);
}

function raffle_cache_flush() {
    $files = glob(RAFFLE_CACHE_DIR . '/*.cache');
    if ($files) foreach ($files as $f) @unlink($f);
}
