# Data Locale Parser

[![Latest Version](https://img.shields.io/github/release/dominservice/data_locale_parser.svg?style=flat-square)](https://github.com/dominservice/data_locale_parser/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/dominservice/data_locale_parser.svg?style=flat-square)](https://packagist.org/packages/dominservice/data_locale_parser)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Data Locale Parser is a package for Laravel 5.6, 5.7, 5.8, 6.*, 7.* and 8.*, which lists all countries, currencies and languages, with names and ISO 3166-1 codes in all languages and data formats.

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
$this->dataParser->getListCountries('en');
```
Get all currencies
```php
$this->dataParser->getListCurrencies('en');
```
Get all Languages
```php
$this->dataParser->getListLanguages('en');
```

All lists return a collection
___

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
If you have collected all data, you make use this code
```php
 $this->dataParser->parseAllDataPerCountry('pl_PL');
```
Then You get 
```php
Illuminate\Support\Collection {▼
  #items: [
    0 => {#1992 ▼
      +"so": "PL"
      +"iso3": "POL"
      +"iso_nr": "616"
      +"fips": "PL"
      +"continent": "EU"
      +"tld": ".pl"
      +"phone": "48"
      +"postal_code_format": "##-###"
      +"postal_code_regex": "^\d{2}-\d{3}$"
      +"currency": {#1991 ▼
        +"name": "złoty polski"
        +"code": "PLN"
        +"symbol": "zł"
      }
      +"languages": array:1 [▼
        "pl" => "polski"
      ]
      +"country": "Polska"
      +"subdivision_iso3166": Illuminate\Support\Collection {#2275 ▼
        #items: array:16 [▼
          "PL-02" => array:2 [▼
            "name" => "Dolnośląskie"
            "name_ascii" => "Dolnoslaskie"
          ]
          "PL-04" => array:2 [▼
            "name" => "Kujawsko-pomorskie"
            "name_ascii" => "Kujawsko-pomorskie"
          ]
          "PL-06" => array:2 [▼
            "name" => "Lubelskie"
            "name_ascii" => "Lubelskie"
          ]
          "PL-08" => array:2 [▼
            "name" => "Lubuskie"
            "name_ascii" => "Lubuskie"
          ]
          "PL-10" => array:2 [▼
            "name" => "Łódzkie"
            "name_ascii" => "Lodzkie"
          ]
          "PL-12" => array:2 [▼
            "name" => "Małopolskie"
            "name_ascii" => "Malopolskie"
          ]
          "PL-14" => array:2 [▼
            "name" => "Mazowieckie"
            "name_ascii" => "Mazowieckie"
          ]
          "PL-16" => array:2 [▼
            "name" => "Opolskie"
            "name_ascii" => "Opolskie"
          ]
          "PL-18" => array:2 [▼
            "name" => "Podkarpackie"
            "name_ascii" => "Podkarpackie"
          ]
          "PL-20" => array:2 [▼
            "name" => "Podlaskie"
            "name_ascii" => "Podlaskie"
          ]
          "PL-22" => array:2 [▼
            "name" => "Pomorskie"
            "name_ascii" => "Pomorskie"
          ]
          "PL-24" => array:2 [▼
            "name" => "Śląskie"
            "name_ascii" => "Slaskie"
          ]
          "PL-26" => array:2 [▶]
          "PL-28" => array:2 [▼
            "name" => "Warmińsko-mazurskie"
            "name_ascii" => "Warminsko-mazurskie"
          ]
          "PL-30" => array:2 [▼
            "name" => "Wielkopolskie"
            "name_ascii" => "Wielkopolskie"
          ]
          "PL-32" => array:2 [▼
            "name" => "Zachodniopomorskie"
            "name_ascii" => "Zachodniopomorskie"
          ]
        ]
      }
    }

    ...
}
```
You make take one country full data 

```php

 $this->dataParser->parseAllDataPerCountry('pl_PL', 'PL');
```

## Credits

- [UN/LOCODE Country Subdivisions ISO 3166-2](https://unece.org/trade/uncefact/unlocode-country-subdivisions-iso-3166-2)
- [Monarobase/country-list](https://github.com/Monarobase/country-list)
- [umpirsky/language-list](https://github.com/umpirsky/language-list)
- [umpirsky/locale-list](https://github.com/umpirsky/locale-list)
- [ipregistry/iso3166](https://github.com/ipregistry/iso3166)