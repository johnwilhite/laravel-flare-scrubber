<?php

namespace FlareScrubber;

use Illuminate\Support\ServiceProvider;
use Spatie\FlareClient\Report;
use Spatie\LaravelIgnition\Facades\Flare;

class FlareScrubberProvider extends ServiceProvider
{

    protected $keyList;
    protected $keyRegexList;
    protected $valueRegexList;

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
                $report->group('request_data', $context['request_data']);
            }

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
        $this->setOptions();
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
        $this->setOptions();
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
        if (isset($this->keyList) && in_array($key, $this->keyList, true)) {
            return true;
        }

        if (isset($this->keyRegexList)) {
            foreach ($this->keyRegexList as $regex) {
                if (preg_match($regex, $key)) {
                    return true;
                }
            }
        }

        if (isset($this->valueRegexList) && ! is_array($value)) {
            foreach ($this->valueRegexList as $regex) {
                if (preg_match($regex, $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate and set the options from config
     *
     * @return void
     */
    protected function setOptions()
    {
        $keys = config('flare.sensitive_data.keys');
        if (is_array($keys)) {
            $this->keyList = $keys;
        } elseif (isset($keys)) {
            throw new \Exception('flare.sensitive_data.keys must be an array');
        }

        $keyRegex = config('flare.sensitive_data.key_regex');
        if (is_array($keyRegex)) {
            $this->keyRegexList = $keyRegex;
        } elseif (isset($keyRegex)) {
            throw new \Exception('flare.sensitive_data.key_regex must be an array');
        }

        $valueRegex = config('flare.sensitive_data.value_regex');
        if (is_array($valueRegex)) {
            $this->valueRegexList = $valueRegex;
        } elseif (isset($valueRegex)) {
            throw new \Exception('flare.sensitive_data.value_regex must be an array');
        }
    }
}
