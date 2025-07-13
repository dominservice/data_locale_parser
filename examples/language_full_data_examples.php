<?php

/**
 * Examples of using the enhanced getLanguagesFullData() and getLanguageFullData() methods
 */

use Dominservice\DataLocaleParser\Fasade\DataParserFacade as DataLocale;

// Example 1: Basic usage (backward compatible)
// This will return all languages with names in the current application locale
$allLanguages = DataLocale::getLanguagesFullData();
// Output: Collection of all languages with their full data

// Example 2: Filter by specific locales
// Get data only for English, Spanish and German
$specificLanguages = DataLocale::getLanguagesFullData(['en', 'es', 'de']);
// Output: Collection containing only en, es, and de language data

// Example 3: Use a specific display locale for names
// Get all languages with names in Spanish
$languagesInSpanish = DataLocale::getLanguagesFullData(null, 'es');
// Output: Collection of all languages with names in Spanish

// Example 4: Disable sorting
// Get all languages without sorting them
$unsortedLanguages = DataLocale::getLanguagesFullData(null, null, false);
// Output: Collection of all languages in their original order

// Example 5: Filter by script
// Get all languages that use the Latin script
$latinScriptLanguages = DataLocale::getLanguagesFullData(null, null, true, ['script' => 'Latn']);
// Output: Collection of all languages that use the Latin script

// Example 6: Multiple filters
// Get all languages that use the Latin script and have a regional code
$filteredLanguages = DataLocale::getLanguagesFullData(null, null, true, [
    'script' => 'Latn',
    'regional' => 'en_US'
]);
// Output: Collection of all languages that use the Latin script and have regional code 'en_US'

// Example 7: Filter by multiple possible values
// Get all languages that use either Latin or Cyrillic script
$multiScriptLanguages = DataLocale::getLanguagesFullData(null, null, true, [
    'script' => ['Latn', 'Cyrl']
]);
// Output: Collection of all languages that use either Latin or Cyrillic script

// Example 8: Get a single language's full data
// Get full data for German
$germanData = DataLocale::getLanguageFullData('de');
// Output: Array with full data for German language

// Example 9: Get a single language's full data with a specific display locale
// Get full data for German with the name in French
$germanInFrench = DataLocale::getLanguageFullData('de', 'fr');
// Output: Array with full data for German language with name in French

// Example 10: Handle non-existent language code
// Try to get data for a non-existent language code
$nonExistentLanguage = DataLocale::getLanguageFullData('xx');
// Output: null

// Example 11: Using underscores in locale codes (they're automatically converted to hyphens)
// Get data for English (US)
$englishUS = DataLocale::getLanguageFullData('en_US');
// Output: Array with full data for English (US) language

// Example 12: Check if a fallback translation was used
// Get data for a language in a locale that might not have translations for all languages
$language = DataLocale::getLanguageFullData('de', 'zh');
if (isset($language['using_fallback_name']) && $language['using_fallback_name']) {
    // The name is using a fallback translation (likely English)
    echo "Using fallback translation for {$language['name']}";
}