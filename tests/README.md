# Regression snapshots

The test suite protects both the package API contract and the data returned by
the current input files.

Locale sorting is tested dynamically against the `Collator` available in the
current PHP runtime for all 1,758 lists. Sorted output is intentionally not
stored as a portable snapshot because ICU collation data can differ between
operating systems and PHP versions.

## Baseline

Versioned snapshots live in `tests/Fixtures/snapshots`.

The pre-CLDR baseline remains available in
`tests/Fixtures/legacy/umpirsky-v3`. This makes translation updates auditable
without weakening the snapshots used by the current test suite.

- `data/representative-lists.json` contains complete raw country, currency, and
  language lists for 27 representative locales.
- `data/all-locales-fingerprints.json` contains raw fingerprints for every
  available locale: 628 country, 566 currency, and 564 language lists.
- `parser/` covers single values, exceptions, custom lists, address formatting,
  full country data, and full language data.
- `framework/` covers helpers, routes, the service provider, locale middleware,
  and the language controller.

Run all tests:

```bash
composer test
```

Run all tests and save a JUnit report to `build/test-results/junit.xml`:

```bash
composer test:report
```

## Comparing a rebuilt dataset

After changing the input data or its storage format, record candidate output
without modifying the baseline:

```bash
composer test:snapshots:record
diff -ru tests/Fixtures/snapshots build/snapshots/current
```

This command writes the complete candidate result tree to
`build/snapshots/current`. Snapshot tests deliberately succeed in record mode,
so the explicit `diff` is the compatibility decision point.

Expected translation corrections should be reviewed in that diff. Structural
changes, missing keys, changed types, unexpected ordering changes, exceptions,
routes, middleware behavior, or address formatting changes should be treated
as regressions.

## Updating the accepted baseline

Only update the versioned baseline after the candidate diff has been reviewed:

```bash
composer test:snapshots:update
composer test
```

After rebuilding locale sources, also run:

```bash
composer data:validate
```

If the previous runtime tree is available, benchmark it explicitly with
`php tools/benchmark-data.php /path/to/legacy-data`.

Do not update snapshots merely to make a failing test pass. The baseline is the
record of the behavior deployed by existing consumers.
