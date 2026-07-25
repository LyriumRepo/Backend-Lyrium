<?php

declare(strict_types=1);

namespace App\Models;

use DeviceDetector\DeviceDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdminSession extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'country' => 'string',
    ];

    private static array $ddCache = [];

    private function dd(): DeviceDetector
    {
        $ua = $this->user_agent ?? '';
        $key = md5($ua);

        if (!isset(self::$ddCache[$key])) {
            $dd = new DeviceDetector($ua);
            $dd->parse();
            self::$ddCache[$key] = $dd;
        }

        return self::$ddCache[$key];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query, int $minutes = 15)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp);
    }

    public function scopeWithUser($query)
    {
        return $query->whereNotNull('user_id')->where('user_id', '!=', '');
    }

    public function getDeviceAttribute(): string
    {
        $ua = $this->user_agent ?? '';
        $dd = $this->dd();
        $brand = $dd->getBrandName();
        $model = $dd->getModel();
        $deviceName = $dd->getDeviceName();

        if ($brand && $model) {
            if (str_contains($model, $brand)) {
                return $model;
            }
            return "{$brand} {$model}";
        }

        if ($deviceName === 'desktop' || $deviceName === '') {
            if (preg_match('/Android/i', $ua)) {
                if (preg_match('/Mobile/', $ua)) {
                    return 'Teléfono Android';
                }
                return 'Tablet Android';
            }
            if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
                if (preg_match('/iPad/', $ua)) {
                    return 'iPad';
                }
                return 'iPhone';
            }

            if ($brand) {
                return "{$brand} Desktop";
            }
            if (!preg_match('/\S/', $ua)) {
                return 'Desconocido';
            }
            return 'Desktop';
        }

        if ($brand) {
            return $brand;
        }

        return $deviceName ? ucfirst($deviceName) : 'Desconocido';
    }

    public function getBrowserAttribute(): string
    {
        $client = $this->dd()->getClient();
        return $client['name'] ?? 'Desconocido';
    }

    public function getBrowserVersionAttribute(): string
    {
        $client = $this->dd()->getClient();
        return $client['version'] ?? '';
    }

    public function getPlatformAttribute(): string
    {
        $os = $this->dd()->getOs();
        $osName = $os['name'] ?? '';
        $osVersion = $os['version'] ?? '';

        if (!$osName) {
            $ua = $this->user_agent ?? '';
            if (preg_match('/Windows NT (\d+\.\d+)/', $ua, $m)) {
                $versionMap = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7'];
                $v = $versionMap[$m[1]] ?? $m[1];
                $osName = "Windows {$v}";
            } elseif (preg_match('/Android (\d+\.?\d*)/', $ua, $m)) {
                $osName = "Android {$m[1]}";
            } elseif (preg_match('/iPhone OS (\d+_?\d*)/', $ua, $m)) {
                $osName = "iOS {$m[1]}";
            } elseif (preg_match('/Mac OS X (\d+[._]\d+)/', $ua, $m)) {
                $osName = "macOS " . str_replace('_', '.', $m[1]);
            } elseif (preg_match('/Linux/', $ua)) {
                if (preg_match('/Android/', $ua)) {
                    $osName = 'Android';
                } else {
                    $osName = 'Linux';
                }
            }
        }

        if ($osName && $osVersion) {
            return "{$osName} {$osVersion}";
        }

        return $osName ?: 'Desconocido';
    }

    public function getPlatformVersionAttribute(): string
    {
        $os = $this->dd()->getOs();
        return $os['version'] ?? '';
    }

    public function getIsMobileAttribute(): bool
    {
        if ($this->dd()->isMobile()) {
            return true;
        }
        if ($this->dd()->isTablet()) {
            return true;
        }
        $ua = $this->user_agent ?? '';
        return (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua);
    }

    public function getIsPhoneAttribute(): bool
    {
        if ($this->dd()->getDeviceName() === 'smartphone') {
            return true;
        }
        $ua = $this->user_agent ?? '';
        return preg_match('/Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua) && !preg_match('/iPad/', $ua);
    }

    public function getIsTabletAttribute(): bool
    {
        if ($this->dd()->getDeviceName() === 'tablet') {
            return true;
        }
        $ua = $this->user_agent ?? '';
        return (bool) preg_match('/iPad|Tablet|ASUS_T00|SM-T|SM-X|KFAPWI|Nexus 7|NDS/i', $ua);
    }

    public function getIsDesktopAttribute(): bool
    {
        if ($this->dd()->isDesktop()) {
            $ua = $this->user_agent ?? '';
            if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
                return false;
            }
            return true;
        }
        return false;
    }
}
