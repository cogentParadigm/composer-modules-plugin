# Composer Modules Plugin

A composer plugin which dumps a topologically sorted list of packages of specific types into vendor/modules.php.

This allows a framework module system to be built on top of composer. Suppose you want to create a module system.
You would probably want some way for modules to declare themselves and to allow modules to declare other modules as dependecies. Well composer already does this in a more rubust way than most framework developers are likely to implement.
If we want to build a module system on top of composer, we really just need a way to pull out a list of the relevant
packages, put them in order, and perhaps grab some configuration data from them.

# Usage

First, pick a package type for your modules. We'll be using `starbug-module`, so a `composer.json` for such a a module would look like this.

```json
{
  "name": "starbug/my-module",
  "type": "starbug-module"
}
```

To use this as a module type, we need to do two things in our root package.

1. Require `starbug/composer-modules-plugin`
2. Add the `modules-plugin` key under `extra` mapping composer package types to your own module types.

Here's a `composer.json` which includes both, and also requires our example module above (`starbug/my-module`).

```json
{
  "name": "starbug/my-project",
  "type": "project",
  "require": {
    "starbug/composer-modules-plugin": "^0.8",
    "starbug/my-module": "^1.0"
  },
  "extra": {
    "modules-plugin": {
      "types": {
        "starbug-module": "module"
      }
    }
  }
}
```

Given this package definition above, when you run `composer install` or `composer update`, a file will be written to `vendor/modules.php` with the contents below.

```php
<?php
return [
  "my-module" => [
    "type" => "module",
    "path" => "vendor/starbug/my-module"
  ]
];
```

### Parameters

You can also pass parameters from modules. To do so we have to specify the parameter name in the root package and specify a value for the parameter from each module.


To specify the parameter name in the root package, we can add a `parameters` key under `modules-plugin`.

```json
{
  "name": "starbug/my-project",
  "type": "project",
  "require": {
    "starbug/composer-modules-plugin": "^0.8",
    "starbug/my-module": "^1.0"
  },
  "extra": {
    "modules-plugin": {
      "types": {
        "starbug-module": "module"
      },
      "parameters": ["color"]
    }
  }
}
```

To pass the parameter from a module, specify it directly under `extra`.

```json
{
  "name": "starbug/my-module",
  "type": "starbug-module",
  "description": "An example module.",
  "license": "GPL-3.0-or-later",
  "extra": {
    "color": "blue"
  }
}
```

With the color paramater added, this is what our output module list would now look like.

```php
<?php
return [
  "my-module" => [
    "type" => "module",
    "path" => "vendor/starbug/my-module",
    "color" => "blue"
  ]
];
```

# Acknowledgements

The topological sorting implementation is provided by [marcj/topsort](https://github.com/marcj/topsort.php).

