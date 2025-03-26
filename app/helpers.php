<?php

use App\Enums\NotificationType;
use App\Mail\DevsNotification;
use App\Models\Client;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Snapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

if (!defined('MODEL_NAMESPACE')) {
    define('MODEL_NAMESPACE', 'App\Models\\');
}
if (!defined('EXPORT_NAMESPACE')) {
    define('EXPORT_NAMESPACE', 'App\Exports\\');
}
if (!defined('QUANTITY_PRECISION')) {
    define('QUANTITY_PRECISION', 8);
}

if (!function_exists('moneyFormat')) {
    function moneyFormat(
        $amount,
        $currency = null,
        bool $withoutDecimals = false,
    ): string {
        if (!$currency) {
            $currency = config('app.currency');
        }

        $nf = new \NumberFormatter(
            config('app.locale'),
            \NumberFormatter::CURRENCY,
        );
        $formatted = $nf->formatCurrency($amount, $currency);
        if ($withoutDecimals) {
            $formatted = str_replace('.00', '', $formatted);
        }
        return $formatted;
    }
}

if (!function_exists('getSystemUptime')) {
    function getSystemUptime(): \Carbon\Carbon
    {
        if (!file_exists(storage_path() . '/system_uptime.txt')) {
            return now();
        }

        $uptime = \file_get_contents(storage_path() . '/system_uptime.txt');
        return \Carbon\Carbon::parse($uptime);
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user?->hasPermissionTo($permission) ?? false;
    }
}

if (!function_exists('publicMinioUrl')) {
    function publicMinioUrl($path): string
    {
        return route('cdn', ['path' => str_replace('public/', '', $path)]);
    }
}

if (!function_exists('getModelByName')) {
    function getModelByName(string $model): string
    {
        if (!str_contains(MODEL_NAMESPACE, $model)) {
            $model = MODEL_NAMESPACE . $model;
        }

        abort_if(!class_exists($model), 400, 'Invalid model!');

        return $model;
    }
}

if (!function_exists('getModelNameByClass')) {
    function getModelNameByClass(string $class): string
    {
        return last(explode('\\', $class));
    }
}
if (!function_exists('getExportClassByName')) {
    function getExportClassByName(string $model): string
    {
        if (!str_contains(EXPORT_NAMESPACE, $model)) {
            $model = EXPORT_NAMESPACE . $model . 'Export';
        }

        abort_if(!class_exists($model), 400, 'Invalid export class!');

        return $model;
    }
}

if (!function_exists('getIp')) {
    function getIp(): string
    {
        foreach (
            [
                'HTTP_CF_CONNECTING_IP',
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR',
            ]
            as $key
        ) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (
                        filter_var(
                            $ip,
                            FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_PRIV_RANGE |
                                FILTER_FLAG_NO_RES_RANGE,
                        ) !== false
                    ) {
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return the server IP if the client IP is not found using this method.
    }
}

if (!function_exists('isOfficeIp')) {
    function isOfficeIp($ip): bool
    {
        return in_array($ip, config('app.office_ips'));
    }
}

if (!function_exists('generateUrlForModel')) {
    function getRouteForModel($model, $routeName = null): string
    {
        $className = class_basename($model);
        $route = Str::kebab(Str::pluralStudly($className)) . '.' . $routeName;

        if (Route::has($route)) {
            return route($route, $model);
        }

        return '#';
    }
}

if (!function_exists('company')) {
    function company(): Client|null
    {
        // currently the system only supports one company
        return Client::isCompany()->first();
    }
}

if (!function_exists('setting')) {
    function setting($key, $defaultValue = null): mixed
    {
        return Setting::where('key', $key)->first()->value ?? $defaultValue;
    }
}

if (!function_exists('getMimetypeFromExtension')) {
    function getMimetypeFromExtension(string|null $extension): ?string
    {
        switch ($extension) {
            case 'pdf':
                return 'application/pdf';
            case 'doc':
            case 'docx':
                return 'application/msword';
            case 'xls':
            case 'xlsx':
                return 'application/vnd.ms-excel';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            case 'txt':
                return 'text/plain';
            case 'csv':
                return 'text/csv';
            case 'zip':
                return 'application/zip';
            case 'rar':
                return 'application/x-rar-compressed';
            case 'tar':
                return 'application/x-tar';
            case 'gz':
                return 'application/gzip';
        }

        return null;
    }
}

if (!function_exists('activeAt')) {
    function activeAt($query, $date = null): mixed
    {
        $date = Snapshot::getActiveFrom() ?? $date;

        // Validate and parse the date
        try {
            $date = Carbon::parse($date); // Ensures it's a valid DateTime object
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Invalid date format: ' . $date,
            );
        }

        $date = $date->format('Y-m-d H:i:s');

        return $query
            ->where('active_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query
                    ->where('active_to', '>', $date)
                    ->orWhereNull('active_to');
            });
    }
}

if (!function_exists('notifyDevelopers')) {
    function notifyDevelopers(
        string $message,
        $appNotification = true,
        $emailNotification = true,
    ): void {
        if (Cache::has('devs-' . $message)) {
            return;
        }

        $devs = User::developer()->get();

        if ($appNotification) {
            foreach ($devs as $dev) {
                Notification::create([
                    'notifiable_id' => $dev->id,
                    'notifiable_type' => User::class,
                    'type' => NotificationType::Error,
                    'title' => 'Error!',
                    'content' => $message,
                ]);
            }
        }

        if ($emailNotification) {
            foreach ($devs as $dev) {
                Mail::to($dev->email)->queue(
                    new DevsNotification($message, $dev->full_name),
                );
            }
        }

        Cache::put('devs-' . $message, true, now()->addDay());
    }
}

if (!function_exists('batchUpdate')) {
    function batchUpdate(string $table, array $updates): void
    {
        $ids = [];
        $cases = [];

        foreach ($updates as $update) {
            $id = $update['id'];
            $ids[] = $id;

            foreach ($update as $key => $value) {
                if ($key === 'id') {
                    continue;
                }

                $cases[$key][] = "WHEN id = {$id} THEN {$value}";
            }
        }

        $update = [
            'updated_at' => now(),
        ];
        foreach ($cases as $key => $case) {
            $update[$key] = DB::raw('CASE ' . implode(' ', $case) . ' END');
        }

        DB::table($table)
            ->whereIn('id', $ids)
            ->update($update);
    }
}
