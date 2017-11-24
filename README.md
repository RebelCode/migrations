# RebelCode - Migrations

[![Build Status](https://travis-ci.org/RebelCode/migrations.svg?branch=master)](https://travis-ci.org/RebelCode/migrations)
[![Code Climate](https://codeclimate.com/github/RebelCode/migrations/badges/gpa.svg)](https://codeclimate.com/github/RebelCode/migrations)
[![Test Coverage](https://codeclimate.com/github/RebelCode/migrations/badges/coverage.svg)](https://codeclimate.com/github/RebelCode/migrations/coverage)
[![Latest Stable Version](https://poser.pugx.org/rebelcode/migrations/version)](https://packagist.org/packages/rebelcode/migrations)

Simple database migration library.

This library is mostly an extension of [byjg/migration].

----

## Technical Details

### `AbstractDatabase`

Extends `ByJG\DbMigration\Database\AbstractDatabase` to allow customizable database, table and column names.

This is achieved by passing all SQL to a formatter function prior to execution. The formatter function replaces tokens
in the query with the actual values for the database, table and column names. In order to also have customizable
placeholders, the placeholders are first interpolated using `sprintf()`, then the formatter function performs the
necessary replacements.

### `MySqlDatabase`

Implementation for a MySql database.

This implementation uses the default SQL format placeholders:

| Placeholder  | Description                                                                   |
| ------------ | ----------------------------------------------------------------------------- |
| {db}         | The name of the database where the migrations are performed and logged.       |
| {lt}         | The name of the table where migrations are logged.                            |
| {lt_version} | The name of the column in the log table where the version is listed.          |
| {lt_status}  | The name of the column in the log table where the migration status is listed. |

### `AbstactMigrator`

Extends `ByJG\DbMigration\Migration` for multiple reasons.

To fix a bug that caused the first/last migration to be incorrectly applied.  
For instance, if migrating down from v5 to v2, the "down" SQL for v2 would also be applied, effectively leaving the
database at v1.

To throw better exceptions and in a more uniform way.  
The exception interfaces and implementations are included in this package.

To allow for preparation of SQL queries prior to execution. This is crucial for descendant classes, as it gives them a
chance to modify the SQL or take action before the migration query is executed.

To allow descendant implementations to define their own SQL resolution. Descendant classes can now have different ways
of resolving the SQL query and are not restricted to simply retrieving them from files.  
Searching for files is, however, the default behavior for this abstract class.

### `MySqlFormatMigrator`

A MySql migrator implementation that accepts a list of callback functions that can alter the SQL queries. 

[byjg/migration]: https://github.com/byjg/migration
