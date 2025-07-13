# Data Locale Parser Examples

This directory contains example scripts demonstrating how to use various features of the Data Locale Parser library.

## Enhanced Language Full Data Methods

The file `language_full_data_examples.php` demonstrates the enhanced functionality of the `getLanguagesFullData()` method and the new `getLanguageFullData()` method.

### Enhancements to `getLanguagesFullData()`

The `getLanguagesFullData()` method has been enhanced with several new features while maintaining backward compatibility:

1. **Caching**: The language data is now cached in memory to avoid loading the file on every call.
2. **Custom Display Locale**: You can now specify which locale to use for language names instead of always using the current application locale.
3. **Sorting Control**: You can enable or disable sorting of the results.
4. **Advanced Filtering**: You can filter languages by various attributes like script, regional code, etc.
5. **Fallback Mechanism**: If translations aren't available in the requested locale, it will fall back to English and indicate this in the result.

### New Method: `getLanguageFullData()`

A new method `getLanguageFullData()` has been added to retrieve full data for a single language code. This is more efficient than retrieving all languages and then filtering for a specific one.

### Backward Compatibility

All enhancements maintain backward compatibility with existing code. The original functionality of `getLanguagesFullData()` is preserved, so existing code will continue to work without modification.

## Language Handling Middleware

The file `language_middleware_example.php` demonstrates how to use the language handling middleware to automatically detect and set the application locale based on URL paths and request headers.

### Features

1. **URL Path Detection**: Automatically detect language from URL paths (e.g., `/en/page`, `/pl/page`).
2. **API Route Support**: Special handling for API routes, detecting language from the second segment (e.g., `/api/en/endpoint`).
3. **Header Detection**: Fallback to detecting language from request headers if URL detection fails.
4. **Configuration Options**: Customize behavior through configuration, including allowed locales, default locale, and API prefixes.

### Examples Include

- Basic route configuration with the middleware
- API route configuration
- Route parameters with language codes
- Accessing the current locale in controllers
- Custom configuration options
- Middleware registration

## Usage Examples

See the `language_full_data_examples.php` file for detailed examples of how to use the enhanced language data methods.

See the `language_middleware_example.php` file for detailed examples of how to use the language handling middleware.
