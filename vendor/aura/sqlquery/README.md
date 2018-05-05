# Aura.SqlQuery

Provides query builders for MySQL, Postgres, SQLite, and Microsoft SQL Server.
These builders are independent of any particular database connection library,
although [PDO](http://php.net/PDO) in general is recommended.

## Foreword

### Installation

This library requires PHP 5.3.9 or later; we recommend using the latest available version of PHP as a matter of principle. It has no userland dependencies.

It is installable and autoloadable via Composer as [aura/sqlquery](https://packagist.org/packages/aura/sqlquery).

Alternatively, [download a release](https://github.com/auraphp/Aura.SqlQuery/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/auraphp/Aura.SqlQuery/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/auraphp/Aura.SqlQuery/?branch=2.x)
[![Code Coverage](https://scrutinizer-ci.com/g/auraphp/Aura.SqlQuery/badges/coverage.png?b=2.x)](https://scrutinizer-ci.com/g/auraphp/Aura.SqlQuery/?branch=2.x)
[![Build Status](https://travis-ci.org/auraphp/Aura.SqlQuery.png?branch=2.x)](https://travis-ci.org/auraphp/Aura.SqlQuery)

To run the unit tests at the command line, issue `phpunit` at the package root. (This requires [PHPUnit][] to be available as `phpunit`.)

[PHPUnit]: http://phpunit.de/manual/

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.

## Getting Started

First, instantiate a _QueryFactory_ with a database type:

```php
<?php
use Aura\SqlQuery\QueryFactory;

$query_factory = new QueryFactory('sqlite');
?>
```

You can then use the factory to create query objects:

```php
<?php
$select = $query_factory->newSelect();
$insert = $query_factory->newInsert();
$update = $query_factory->newUpdate();
$delete = $query_factory->newDelete();
?>
```

The query objects do not execute queries against a database. When you are done
building the query, you will need to pass it to a database connection of your
choice. In the examples below, we will use [PDO](http://php.net/pdo) for the
database connection, but any database library that uses named placeholders and
bound values should work just as well (e.g. the [Aura.Sql][] _ExtendedPdo_
class).

[Aura.Sql]: https://github.com/auraphp/Aura.Sql/tree/develop-2


## Identifier Quoting

In most cases, the query objects will quote identifiers for you. For example,
under the common _Select_ object with double-quotes for identifiers:

```php
<?php
$select->cols(array('foo', 'bar AS barbar'))
       ->from('table1')
       ->from('table2')
       ->where('table2.zim = 99');

echo $select->getStatement();
// SELECT
//     "foo",
//     "bar" AS "barbar"
// FROM
//     "table1",
//     "table2"
// WHERE
//     "table2"."zim" = 99

?>
```

If you discover that a partially-qualified identifier has not been auto-quoted
for you, change it to a fully-qualified identifer (e.g., from `col_name` to
`table_name.col_name`).

## Common Query Objects

Although you must specify a database type when instantiating a _QueryFactory_,
you can tell the factory to return "common" query objects instead of database-
specific ones.  This will make only the common query methods available, which
helps with writing database-portable applications. To do so, pass the constant
`QueryFactory::COMMON` as the second constructor parameter.

```php
<?php
use Aura\SqlQuery\QueryFactory;

// return Common, not SQLite-specific, query objects
$query_factory = new QueryFactory('sqlite', QueryFactory::COMMON);
?>
```

> N.b. You still need to pass a database type so that identifiers can be
> quoted appropriately.

All query objects implement the "Common" methods.

### SELECT

#### Building A Query

Build a _Select_ query using the following methods. They do not need to
be called in any particular order, and may be called multiple times.

```php
<?php
$select = $query_factory->newSelect();

$select
    ->distinct()                    // SELECT DISTINCT
    ->cols(array(                   // select these columns
        'id',                       // column name
        'name AS namecol',          // one way of aliasing
        'col_name' => 'col_alias',  // another way of aliasing
        'COUNT(foo) AS foo_count'   // embed calculations directly
    ))
    ->from('foo AS f')              // FROM these tables
    ->fromSubSelect(                // FROM sub-select AS my_sub
        'SELECT ...',
        'my_sub'
    )
    ->join(                         // JOIN ...
        'LEFT',                     // left/inner/natural/etc
        'doom AS d',                // this table name
        'foo.id = d.foo_id'         // ON these conditions
    )
    ->joinSubSelect(                // JOIN to a sub-select
        'INNER',                    // left/inner/natural/etc
        'SELECT ...',               // the subselect to join on
        'subjoin',                  // AS this name
        'sub.id = foo.id'           // ON these conditions
    )
    ->where('bar > :bar')           // AND WHERE these conditions
    ->where('zim = ?', 'zim_val')   // bind 'zim_val' to the ? placeholder
    ->orWhere('baz < :baz')         // OR WHERE these conditions
    ->groupBy(array('dib'))         // GROUP BY these columns
    ->having('foo = :foo')          // AND HAVING these conditions
    ->having('bar > ?', 'bar_val')  // bind 'bar_val' to the ? placeholder
    ->orHaving('baz < :baz')        // OR HAVING these conditions
    ->orderBy(array('baz'))         // ORDER BY these columns
    ->limit(10)                     // LIMIT 10
    ->offset(40)                    // OFFSET 40
    ->forUpdate()                   // FOR UPDATE
    ->union()                       // UNION with a followup SELECT
    ->unionAll()                    // UNION ALL with a followup SELECT
    ->bindValue('foo', 'foo_val')   // bind one value to a placeholder
    ->bindValues(array(             // bind these values to named placeholders
        'bar' => 'bar_val',
        'baz' => 'baz_val',
    ));
?>
```

> N.b.: The `*where()` and `*having()` methods take an arbitrary number of
trailing arguments, each of which is a value to bind to a sequential question-
mark placeholder in the condition clause.
>
> Similarly, the `*join*()` methods take an optional final argument, a
sequential array of values to bind to sequential question-mark placeholders in
the condition clause.

#### Resetting Query Elements

The _Select_ class comes with the following methods to "reset" various clauses
a blank state. This can be useful when reusing the same query in different
variations (e.g., to re-issue a query to get a `COUNT(*)` without a `LIMIT`, to
find the total number of rows to be paginated over).

- `resetCols()` removes all columns
- `resetTable()` removes all `FROM` and `JOIN` clauses
- `resetWhere()`, `resetGroupBy()`, `resetHaving()`, and `resetOrderBy()`
  remove the respective clauses
- `resetUnions()` removes all `UNION` and `UNION ALL` clauses
- `resetFlags()` removes all database-engine-specific flags
- `resetBindValues()` removes all values bound to named placeholders

#### Issuing The Query

Once you have built the query, pass it to the database connection of your
choice as a string, and send the bound values along with it.

```php
<?php
// a PDO connection
$pdo = new PDO(...);

// prepare the statment
$sth = $pdo->prepare($select->getStatement());

// bind the values and execute
$sth->execute($select->getBindValues());

// get the results back as an associative array
$result = $sth->fetch(PDO::FETCH_ASSOC);
?>
```

### INSERT

#### Single-Row Insert

Build an _Insert_ query using the following methods. They do not need to
be called in any particular order, and may be called multiple times.

```php
<?php
$insert = $query_factory->newInsert();

$insert
    ->into('foo')                   // INTO this table
    ->cols(array(                   // bind values as "(col) VALUES (:col)"
        'bar',
        'baz',
    ))
    ->set('ts', 'NOW()')            // raw value as "(ts) VALUES (NOW())"
    ->bindValue('foo', 'foo_val')   // bind one value to a placeholder
    ->bindValues(array(             // bind these values
        'bar' => 'foo',
        'baz' => 'zim',
    ));
?>
```

The `cols()` method allows you to pass an array of key-value pairs where the
key is the column name and the value is a bind value (not a raw value):

```php
<?php
$insert = $query_factory->newInsert();

$insert->into('foo')             // insert into this table
    ->cols(array(                // insert these columns and bind these values
        'foo' => 'foo_value',
        'bar' => 'bar_value',
        'baz' => 'baz_value',
    ));
?>
```

Once you have built the query, pass it to the database connection of your
choice as a string, and send the bound values along with it.

```php
<?php
// the PDO connection
$pdo = new PDO(...);

// prepare the statement
$sth = $pdo->prepare($insert->getStatement());

// execute with bound values
$sth->execute($insert->getBindValues());

// get the last insert ID
$name = $insert->getLastInsertIdName('id');
$id = $pdo->lastInsertId($name);
?>
```

#### Multiple-Row (Bulk) Insert

If you want to do a multiple-row or bulk insert, call the `addRow()` method
after finishing the first row, then build the next row you want to insert. The
columns in the rows after the first will be inserted in the same order as the
first row.

```php
<?php
$insert = $query_factory->newInsert();

// insert into this table
$insert->into('foo');

// set up the first row
$insert->cols(array(
    'bar' => 'bar-0',
    'baz' => 'baz-0'
));
$insert->set('ts', 'NOW()');

// set up the second row. the columns here are in a different order
// than in the first row, but it doesn't matter; the INSERT object
// keeps track and builds them the same order as the first row.
$insert->addRow();
$insert->set('ts', 'NOW()');
$insert->cols(array(
    'bar' => 'bar-1',
    'baz' => 'baz-1'
));

// set up further rows ...
$insert->addRow();
// ...

// execute a bulk insert of all rows
$pdo = new PDO(...);
$sth = $pdo->prepare($insert->getStatement());
$sth->execute($insert->getBindValues());
?>
```

> N.b.: If you add a row and do not specify a value for a column that was
> present in the first row, the _Insert_ will throw an exception.

If you pass an array of column key-value pairs to `addRow()`, they will be
bound to the next row, thus allowing you to skip setting up the first row
manually with `col()` and `cols()`:

```php
<?php
// set up the first row
$insert->addRow(array(
    'bar' => 'bar-0',
    'baz' => 'baz-0'
));
$insert->set('ts', 'NOW()');

// set up the second row
$insert->addRow(array(
    'bar' => 'bar-1',
    'baz' => 'baz-1'
));
$insert->set('ts', 'NOW()');

// etc.
?>
```

If you only need to use bound values, and do not need to set raw values, and
have the entire data set as an array already, you can use `addRows()` to add
them all at once:

```php
<?php
$rows = array(
    array(
        'bar' => 'bar-0',
        'baz' => 'baz-0'
    ),
    array(
        'bar' => 'bar-1',
        'baz' => 'baz-1'
    ),
);
$insert->addRows($rows);
?>
```

> N.b.: SQLite 3.7.10 and earlier do not support the "standard" multiple-row
> insert syntax. Thus, bulk inserts with _Insert_ object will not work on those
> earlier versions of SQLite. We suggest wrapping multuple INSERT operations
> with a transaction as an alternative.

### UPDATE

Build an _Update_ query using the following methods. They do not need to
be called in any particular order, and may be called multiple times.

```php
<?php
$update = $query_factory->newUpdate();

$update
    ->table('foo')                  // update this table
    ->cols(array(                   // bind values as "SET bar = :bar"
        'bar',
        'baz',
    ))
    ->set('ts', 'NOW()')            // raw value as "(ts) VALUES (NOW())"
    ->where('zim = :zim')           // AND WHERE these conditions
    ->where('gir = ?', 'doom')      // bind this value to the condition
    ->orWhere('gir = :gir')         // OR WHERE these conditions
    ->bindValue('bar', 'bar_val')   // bind one value to a placeholder
    ->bindValues(array(             // bind these values to the query
        'baz' => 99,
        'zim' => 'dib',
        'gir' => 'doom',
    ));
?>
```

The `cols()` method allows you to pass an array of key-value pairs where the
key is the column name and the value is a bind value (not a raw value):

```php
<?php
$update = $query_factory->newUpdate();

$update->table('foo')           // update this table
    ->cols(array(               // update these columns and bind these values
        'foo' => 'foo_value',
        'bar' => 'bar_value',
        'baz' => 'baz_value',
    ));
?>
```

Once you have built the query, pass it to the database connection of your
choice as a string, and send the bound values along with it.

```php
<?php
// the PDO connection
$pdo = new PDO(...);

// prepare the statement
$sth = $pdo->prepare($update->getStatement())

// execute with bound values
$sth->execute($update->getBindValues());
?>
```

### DELETE

Build a _Delete_ query using the following methods. They do not need to
be called in any particular order, and may be called multiple times.

```php
<?php
$delete = $query_factory->newDelete();

$delete
    ->from('foo')                   // FROM this table
    ->where('zim = :zim')           // AND WHERE these conditions
    ->where('gir = ?', 'doom')      // bind this value to the condition
    ->orWhere('gir = :gir')         // OR WHERE these conditions
    ->bindValue('bar', 'bar_val')   // bind one value to a placeholder
    ->bindValues(array(             // bind these values to the query
        'baz' => 99,
        'zim' => 'dib',
        'gir' => 'doom',
    ));
?>
```

Once you have built the query, pass it to the database connection of your
choice as a string, and send the bound values along with it.

```php
<?php
// the PDO connection
$pdo = new PDO(...);

// prepare the statement
$sth = $pdo->prepare($delete->getStatement())

// execute with bound values
$sth->execute($delete->getBindValues());
?>
```

## MySQL Query Objects ('mysql')

These 'mysql' query objects have additional MySQL-specific methods:

- SELECT
    - `calcFoundRows()` to add or remove `SQL_CALC_FOUND_ROWS` flag
    - `cache()` to add or remove `SQL_CACHE` flag
    - `noCache()` to add or remove `SQL_NO_CACHE` flag
    - `bigResult()` to add or remove `SQL_BIG_RESULT` flag
    - `smallResult()` to add or remove `SQL_SMALL_RESULT` flag
    - `bufferResult()` to add or remove `SQL_BUFFER_RESULT` flag
    - `highPriority()` to add or remove `HIGH_PRIORITY` flag
    - `straightJoin()` to add or remove `STRAIGHT_JOIN` flag

- INSERT
    - `highPriority()` to add or remove `HIGH_PRIORITY` flag
    - `lowPriority()` to add or remove `LOW_PRIORITY` flag
    - `ignore()` to add or remove `IGNORE` flag
    - `delayed()` to add or remove `DELAYED` flag

- UPDATE
    - `lowPriority()` to add or remove `LOW_PRIORITY` flag
    - `ignore()` to add or remove `IGNORE` flag
    - `where()` and `orWhere()` to add WHERE conditions flag
    - `orderBy()` to add an ORDER BY clause flag
    - `limit()` to set a LIMIT count

- DELETE
    - `lowPriority()` to add or remove `LOW_PRIORITY` flag
    - `ignore()` to add or remove `IGNORE` flag
    - `quick()` to add or remove `QUICK` flag
    - `orderBy()` to add an ORDER BY clause
    - `limit()` to set a LIMIT count

In addition, the _Insert_ object has support for `ON DUPLICATE KEY UPDATE`:

- `onDuplicateKeyUpdate($col, $raw_value)` sets a raw value
- `onDuplicateKeyUpateCol($col, $value)` is a `col()` equivalent for the update
- `onDuplicateKeyUpdateCols($cols)` is a `cols()`equivalent for the update

Placeholders for bound values in the `ON DUPLICATE KEY UPDATE` portions will be
automatically suffixed with `__on_duplicate key` to deconflict them from the
insert placeholders.


## PostgreSQL Query Objects ('pgsql')

These 'pgsql' query objects have additional PostgreSQL-specific methods:

- INSERT
    - `returning()` to add a `RETURNING` clause

- UPDATE
    - `returning()` to add a `RETURNING` clause

- DELETE
    - `returning()` to add a `RETURNING` clause


### Last Insert ID Names in PostgreSQL

PostgreSQL determines the default sequence name for the last inserted ID by concatenating the table name, the column name, and a `seq` suffix, using underscore separators (e.g. `table_col_seq`).

However, when inserting into an extended or inherited table, the parent table is used for the sequence name, not the child (insertion) table. This package allows you to override the default last-insert-id name with the method `setLastInsertIdNames()` on both _QueryFactory_ and the _Insert_ object itself.  Pass an array of `inserttable.col` keys mapped to `parenttable_col_seq` values, and the _Insert_ object will use the mapped sequence names instead of the default names.

```php
<?php
$query_factory->setLastInsertIdNames(array(
    'child.id' => 'parent_id_seq'
));

$insert = $query_factory->newInsert();
$insert->into('child');
// ...
$seq = $insert->getLastInsertIdName('id');
?>
```

The `$seq` name is now `parent_id_seq`, not `child_id_seq` as it would have been by default.


## SQLite Query Objects ('sqlite')

These 'sqlite' query objects have additional SQLite-specific methods:

- INSERT
    - `orAbort()` to add or remove an `OR ABORT` flag
    - `orFail()` to add or remove an `OR FAIL` flag
    - `orIgnore()` to add or remove an `OR IGNORE` flag
    - `orReplace()` to add or remove an `OR REPLACE` flag
    - `orRollback()` to add or remove an `OR ROLLBACK` flag

- UPDATE
    - `orAbort()` to add or remove an `OR ABORT` flag
    - `orFail()` to add or remove an `OR FAIL` flag
    - `orIgnore()` to add or remove an `OR IGNORE` flag
    - `orReplace()` to add or remove an `OR REPLACE` flag
    - `orRollback()` to add or remove an `OR ROLLBACK` flag
    - `orderBy()` to add an ORDER BY clause
    - `limit()` to set a LIMIT count
    - `offset()` to set an OFFSET count

- DELETE
    - `orAbort()` to add or remove an `OR ABORT` flag
    - `orFail()` to add or remove an `OR FAIL` flag
    - `orIgnore()` to add or remove an `OR IGNORE` flag
    - `orReplace()` to add or remove an `OR REPLACE` flag
    - `orRollback()` to add or remove an `OR ROLLBACK` flag
    - `orderBy()` to add an ORDER BY clause
    - `limit()` to set a LIMIT count
    - `offset()` to set an OFFSET count

## Microsoft SQL Query Objects ('sqlsrv')

The 'sqlsrv' query objects have no additional methods specific to Microsoft SQL Server.

In general, `limit()` and `offset()` with Microsoft SQL Server are best
combined with `orderBy()`. The `limit()` and `offset()` methods on the
Microsoft SQL Server query objects will generate sqlsrv-specific variations of
`LIMIT ... OFFSET`:

- If only a `LIMIT` is present, it will be translated as a `TOP` clause.

- If both `LIMIT` and `OFFSET` are present, it will be translated as an
  `OFFSET ... ROWS FETCH NEXT ... ROWS ONLY` clause. In this case there *must*
  be an `ORDER BY` clause, as the limiting clause is a sub-clause of `ORDER
  BY`.

## Table Prefixes

One frequently-requested feature for this package is support for "automatic
table prefixes" on all queries.  This feature sounds great in theory, but in
practice is it (1) difficult to implement well, and (2) even when implemented it
turns out to be not as great as it seems in theory. This assessment is the
result of the hard trials of experience. For those of you who want modifiable
table prefixes, we suggest using constants with your table names prefixed as
desired; as the prefixes change, you can then change your constants.
