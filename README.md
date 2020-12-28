# laravel-flare-scrubber
Request Data Scrubber for Laravel Flare Reporting

Flare doesn't seem to provide any documentation on scrubbing sensitive data from requests before sending over errors.  This package is a simple service provider that allows a config to define what data should be sanitized.

## Installation
```
composer require johnwilhite/laravel-flare-scrubber
```

## Usage
If you don't already have one, create a `config/flare.php` file and define a `sensitive_data` array.

```
<?php

return [
    'sensitive_data' => [
        'keys' => [
            'ssn',
            'bank_routing_number',
            'credit_card_number'
        ],
        'key_regex' => [
            '/^ssn/'
        ],
        'value_regex' => [
            '/^\d{3}-?\d{2}-?\d{4}$/'
        ]
    ]
];
```
Before sending your data to flare, this provider will recursively search the request data and sanitize the values of any match.  Key matches can apply to entire arrays as well. 
There are 3 options that can be used optionally and interchangeably: 
- `keys`
An exact match to the key name.

- `key_regex`
A regex pattern to match a key.

- `value_regex`
A regex pattern to match a value.

Additionally, you may define the sanitized value text at `flare.sensitive_data.sanitization_text`.  The default is `***SANITIZED***`.

## License
MIT
