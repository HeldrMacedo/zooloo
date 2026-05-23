<?php

/**
 * Rate-limiter file-based (sem dependência externa, sem schema).
 * Bucket: { count, window_start_unix }.
 *
 * Limites padrão:
 *   - por login : 5 tentativas / 5 min (lockout 15 min)
 *   - por IP    : 20 tentativas / 5 min
 *
 * Limites são lidos de application.php → 'rate_limit' (overrides opcionais).
 */
class AuthRateLimiter
{
    const DEFAULT_LOGIN_MAX     = 5;
    const DEFAULT_LOGIN_WINDOW  = 300;   // 5 min
    const DEFAULT_LOGIN_LOCKOUT = 900;   // 15 min
    const DEFAULT_IP_MAX        = 20;
    const DEFAULT_IP_WINDOW     = 300;

    private static function storageDir()
    {
        $dir = __DIR__ . '/../../../tmp/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function bucketPath($key)
    {
        $safe = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key);
        return self::storageDir() . '/' . substr(hash('sha256', $safe), 0, 32) . '.json';
    }

    private static function readBucket($key)
    {
        $path = self::bucketPath($key);
        if (!is_file($path)) return ['count' => 0, 'window_start' => time(), 'locked_until' => 0];
        $raw = @file_get_contents($path);
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) return ['count' => 0, 'window_start' => time(), 'locked_until' => 0];
        return array_merge(['count' => 0, 'window_start' => time(), 'locked_until' => 0], $data);
    }

    private static function writeBucket($key, array $data)
    {
        $path = self::bucketPath($key);
        @file_put_contents($path, json_encode($data), LOCK_EX);
    }

    private static function getConfig()
    {
        $cfg = [];
        if (class_exists('Adianti\\Core\\AdiantiApplicationConfig')) {
            $ini = \Adianti\Core\AdiantiApplicationConfig::get();
            $cfg = isset($ini['rate_limit']) && is_array($ini['rate_limit']) ? $ini['rate_limit'] : [];
        }
        return [
            'login_max'     => (int)($cfg['login_max']     ?? self::DEFAULT_LOGIN_MAX),
            'login_window'  => (int)($cfg['login_window']  ?? self::DEFAULT_LOGIN_WINDOW),
            'login_lockout' => (int)($cfg['login_lockout'] ?? self::DEFAULT_LOGIN_LOCKOUT),
            'ip_max'        => (int)($cfg['ip_max']        ?? self::DEFAULT_IP_MAX),
            'ip_window'     => (int)($cfg['ip_window']     ?? self::DEFAULT_IP_WINDOW),
        ];
    }

    /**
     * Verifica se deve bloquear a tentativa. Retorna ['allowed' => bool, 'retry_after' => seconds].
     * Não conta a tentativa; chamar registerFailure() em falha e registerSuccess() em sucesso.
     */
    public static function check($login, $ip)
    {
        $cfg = self::getConfig();
        $now = time();

        $lb = self::readBucket("login:{$login}");
        if (!empty($lb['locked_until']) && $lb['locked_until'] > $now) {
            return ['allowed' => false, 'retry_after' => $lb['locked_until'] - $now, 'reason' => 'login_locked'];
        }

        $ib = self::readBucket("ip:{$ip}");
        if (($now - (int)$ib['window_start']) <= $cfg['ip_window'] && (int)$ib['count'] >= $cfg['ip_max']) {
            return ['allowed' => false, 'retry_after' => $cfg['ip_window'] - ($now - (int)$ib['window_start']), 'reason' => 'ip_throttled'];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    public static function registerFailure($login, $ip)
    {
        $cfg = self::getConfig();
        $now = time();

        $lb = self::readBucket("login:{$login}");
        if (($now - (int)$lb['window_start']) > $cfg['login_window']) {
            $lb = ['count' => 0, 'window_start' => $now, 'locked_until' => 0];
        }
        $lb['count']++;
        if ($lb['count'] >= $cfg['login_max']) {
            $lb['locked_until'] = $now + $cfg['login_lockout'];
        }
        self::writeBucket("login:{$login}", $lb);

        $ib = self::readBucket("ip:{$ip}");
        if (($now - (int)$ib['window_start']) > $cfg['ip_window']) {
            $ib = ['count' => 0, 'window_start' => $now, 'locked_until' => 0];
        }
        $ib['count']++;
        self::writeBucket("ip:{$ip}", $ib);
    }

    public static function registerSuccess($login, $ip)
    {
        @unlink(self::bucketPath("login:{$login}"));
        // não zera bucket de IP — proteção contra brute-force horizontal continua
    }
}
