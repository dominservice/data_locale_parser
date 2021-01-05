# Data Locale Parser

[![Latest Version](https://img.shields.io/github/release/dominservice/data_locale_parser.svg?style=flat-square)](https://github.com/dominservice/laravel_chat/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/dominservice/data_locale_parser.svg?style=flat-square)](https://packagist.org/packages/dominservice/laravel_chat)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Data Locale Parser is a package for Laravel 7.* and 8.*, which lists all countries, currencies and languages, with names and ISO 3166-1 codes in all languages and data formats.

## Installation

Require package via Composer: `composer require dominservice/data_locale_parser`

## Usage

- Locale (en, en_US, fr, fr_CA...)
    - If no locale is given (or if it is set to null), then it will default to 'en'
- Format (csv, flags.html, html, json, mysql.sql, php, postgresql.sql, sqlite.sql, sqlserver.sql, txt, xml, yaml)

Declare ussage
```php
use \Dominservice\DataLocaleParser\DataParser;
```
(...)
```php
private $dataParser;
```
(...)
```php
public function __construct() {
    $this->dataParser = new DataParser();
}
```
Get all countries
```php
$this->dataParser->getListCountries('en', 'json');
```
Get all currencies
```php
$this->dataParser->getListCurrencies('en', 'json');
```
Get all Languages
```php
$this->dataParser->getListLanguages('en', 'json');
```

Get country
```php
$this->dataParser->getCountry('PL', 'en');
```
Get currency
```php
$this->dataParser->getCurrency('PLN', 'en');
```
Get Language
```php
$this->dataParser->geLanguage('pl_PL', 'en');
```

