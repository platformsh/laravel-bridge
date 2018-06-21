# Laravel bridge for Platform.sh

This simple bridge library connects a Laravel-based application to [Platform.sh](https://platform.sh/).  In the typical case it should be completely fire-and-forget.

Laravel prefers all configuration to come in through environment variables with specific names in a specific format.  Platform.sh provides configuration information as environment variables in a different specific format.  This library handles mapping the Platform.sh variables to the format Laravel expects for common values.

## Usage

Simply require this package using Composer.  When Composer's autoload is included this library will be activated and the environment variables set.  As long as that happens before Laravel bootstraps its configuration (which it almost certainly will) everything should work fine with no further user-interaction necessary.

```
composer require platformsh/laravel-bridge
```

## Mappings performed

* If a Platform.sh relationship named `database` is defined, it will be taken as an SQL database and mapped to the `DB_*` environment variables for Laravel.

* If no `APP_ENV` value is set, it will default to `prod`.
