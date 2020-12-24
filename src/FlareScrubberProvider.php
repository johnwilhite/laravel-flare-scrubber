<?php

namespace FlareScrubber;

use Illuminate\Support\ServiceProvider;
use Facade\FlareClient\Report;
use Facade\Ignition\Facades\Flare;

class FlareScrubberProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Flare::registerMiddleware(function (Report $report, $next) {
            $context = $report->allContext();

            if (isset($context['request_data'])) {
                $context['request_data'] = $this->filterSensitiveData($context['request_data']);
            }

            $report->group('request_data', $context['request_data']);
            return $next($report);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Remove
     *
     * @param array $data
     * @return array
     */
    protected function filterSensitiveData(array $data) : array
    {
        $this->arrayMapRecursive($data, function (&$value, $key) {
            if ($this->needsSanitizing($value, $key)) {
                $value = config('flare.sensitive_data.sanitization_text') ?? '***SANITIZED***';
            }
        });
        return $data;
    }

    /**
     * Map a recursive array
     * can't use array_walk_recursive for this because it will skip over arrays
     * and only allow access to values
     *
     * @param array $arr
     * @param callable $fn
     * @return void
     */
    protected function arrayMapRecursive(array &$arr, callable $fn) : void
    {
        array_walk($arr, function (&$item, $key) use ($fn) {
            return is_array($item) && ! $this->needsSanitizing($item, $key)
                ? $this->arrayMapRecursive($item, $fn)
                : $fn($item, $key);
        });
    }

    /**
     * Determine if an array or field needs santizing
     *
     * @param $value
     * @param $key
     * @return boolean
     */
    protected function needsSanitizing($value, $key) : bool
    {
        $keys = config('flare.sensitive_data.keys');
        if (is_array($keys)) {
            if (in_array($key, $keys)) {
                return true;
            }
        } elseif (isset($keys)) {
            throw new \Exception('flare.sensitive_data.keys must be an array');
        }

        $keyRegex = config('flare.sensitive_data.key_regex');
        if (is_array($keyRegex)) {
            foreach (config('flare.sensitive_data.key_regex') as $regex) {
                if (preg_match($regex, $key)) {
                    return true;
                }
            }
        } elseif (isset($keyRegex)) {
            throw new \Exception('flare.sensitive_data.key_regex must be an array');
        }

        $valueRegex = config('flare.sensitive_data.value_regex');
        if (is_array($valueRegex) && ! is_array($value)) {
            foreach (config('flare.sensitive_data.value_regex') as $regex) {
                if (preg_match($regex, $value)) {
                    return true;
                }
            }
        } elseif (isset($valueRegex)) {
            throw new \Exception('flare.sensitive_data.value_regex must be an array');
        }

        return false;
    }
}
