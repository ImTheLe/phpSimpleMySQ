# php-db-pdo-mysql
## Getting started
### Using Composer
Just install `php-db-pdo-mysql` using
```
php composer.phar require leongrdic/db-pdo-mysql
```

### Not using Composer
You can simply require the `DB.php` file from the `src/` folder at the beginning of your script:
```php
require_once('DB.php');
```

### Initialization
Now you can initialize a DB object and start working with your database like following:
```php
$options = [
  'host' => 'localhost', 'port' => 3306, 'charset' => 'utf8',
  'database' => 'db', 'prefix' => 'prefix_',
  'user' => 'username', 'password' => 'password',
  'return_query' => false
];
$database = new \Le\DB($options);
```

If the optional `return_query` key is set to `true`, an additional 'query' key will be added to all method return arrays.

## Object methods
### `get($table_name, $columns, $conditions, $additional)`
#### Parameters
`$table_name` is a string containing the name of the table we're selecting data from. Notice: the prefix will be prepended to the table name if specified

`$columns` can be one of the following:
-   an array containing the names of columns which will be escaped
-   a string containing the list of columns separated by a comma (`,`) or just a wildcard (`*`)

`$conditions` specifies the conditions used to search for the rows, refer to the [Conditions format](#conditions-format) section for more info

`$additional:` an optional array that contains all additions to the query; can contain the following keys:
-   `'limit'` is an integer that represents how many rows should be fetched; if set to `0` or not defined, there will be no limit
-   `'offset'` defines how many rows to skip in the result; if not set, defaults to 0. Notice: it can only be used if the `'limit'` is also set
-   `'order'` is a string specifying the rule the results will be sorted by; refer to the [MySQL ORDER manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info
-   `'single'` if set to `true`, the limit will automatically be set to `1` and the column-value pairs will be more easily accessible

#### Return
```php
[
  'count' => 3,
  'data' => [
    [ 'column1' => 'value1', ... ],
    ...
  ]
]
```
If `single` additional parameter is set to `true`, the `data` index contains the column-value pairs directly:
```php
[
  'count' => 1,
  'data' => [
    'column1' => 'value1',
    ...
  ]
]
```
If there is no rows matching the conditions, the return value will always be:
```php
[
  'count' => 0,
  'data' => []
]
```

#### Examples
```php
$result = $database->get(
  'table',
  ['column1', 'column2'],
  ['condition' => 'value'],
  [
    'limit' => 10,
    'order' => 'column2 DESC'
  ]
);
$data = $result['data'];
```

To get the count of rows matching the conditions:
```php
$result = $database->get(
  'table',
  'COUNT(*)'
);
$count = $result['data']['COUNT(*)']
```

### `insert($table_name, $data)`
#### Parameters
`$table_name` is a string containing the name of the table we're inserting data in. Notice: the prefix will be prepended to the table name if specified

`$data` can be an array or a string containing the rows to insert into the table, refer to the next section for more info

#### Insert data format
It can be formated in one of the following ways:

`['first_name' => 'John', 'last_name' => 'Doe']`

`[ ['first_name', 'last_name'], ['Bob', 'Mike'], ['Hills', 'Rotch'] ]`

#### Return

The return will contain the number of inserted columns and the id of the last one inserted (if your database has a primary key).

```php
[
  'count' => 1,
  'id' => 54
]
```

#### Examples
```php
$result = $database->insert(
  'table',
  ['column' => 'value', ...]
);
$id = $result['id'];
```

```php
$result = $database->insert(
  'table',
  [
    ['column1', 'column2'],
    ['row1_value1', 'row1_value2'],
    ['row2_value1', 'row2_value2']
  ]
);
$lastid = $result['id'];
```

### `update($table_name, $data, $conditions, $additional)`
#### Parameters
`$table_name` is a string containing the name of the table we're updating the data in. Notice: the prefix will be prepended to the table name if specified

`$data` is an array containing the column-value pairs to be updated, e.g.: `['account_balance' => '150']`

`$conditions` specifies the conditions used to search for the rows, refer to the [Conditions format](#conditions-format) section for more info

`$additional:` an optional array that contains all additions to the query; can contain the following keys:
-   `'limit'` is an integer that represents how many rows should be updated; if set to `0` or not defined, there will be no limit
-   `'offset'` defines how many rows to skip; if not set, defaults to 0. Notice: it can only be used if the `'limit'` is also set
-   `'order'` is a string specifying the rule the rows affected will be sorted by; refer to the [MySQL ORDER manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info
-   `'single'` if set to `true`, the limit will be set to `1`

#### Return
```php
['count' => 1]
```

#### Examples
```php
$result = $database->update(
  'table',
  ['condition' => 'value'],
  ['column' => 'new_value'],
  ['single' => true]
);
$count = $result['count'];
```

### `delete($table_name, $conditions, $additional)`
#### Parameters
`$table_name` is a string containing the name of the table we're deleting data from. Notice: the prefix will be prepended to the table name if specified

`$conditions` specifies the conditions used to search for the rows, refer to the [Conditions format](#conditions-format) section for more info

`$additional:` an optional array that contains all additions to the query; can contain the following keys:
-   `'limit'` is an integer that represents how many rows should be deleted; if set to `0` or not defined, there will be no limit
-   `'offset'` defines how many rows to skip; if not set, defaults to 0. Notice: it can only be used if the `'limit'` is also set
-   `'order'` is a string specifying the rule the rows will be sorted by; refer to the [MySQL ORDER manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info
-   `'single'` if set to `true`, the limit will be set to `1`

#### Return
```php
['count' => 1]
```

#### Examples
```php
$result = $database->delete(
  'table',
  ['condition' => 'value']
);
$count = $result['count'];
```

### `schema($table_name)`
Get the column names for a table.

#### Parameters
`$table_name` is a string containing the name of the table we're fetching the schema of. Notice: the prefix will be prepended to the table name if specified

#### Return
```php
[
  'count' => 2,
  'data' => [
    [
      "Field" => "column1",
      "Type" => "varchar(32)",
      "Null" => "YES",
      "Key" => "",
      "Default" => "",
      "Extra" => ""
    ],
    [
      "Field" => "column2",
      "Type" => "int(10)",
      "Null" => "NO",
      "Key" => "PRI",
      "Default" => "",
      "Extra" => ""
    ]
  ]
]
```

#### Examples
```php
$result = $database->schema('table');
$columns = $result['data'];
```

### `escape($string)`
It returns a string escaped with single quotes (`'`) and ready to be passed into the `$conditions` parameter.

#### Parameters
`$string` is the string you would like to escape and make it ready for the query

#### Example
```php
$conditions = 'column != ' . $database->escape($value);
```

### `escapeName($string)`
It returns a string escaped with backticks (`` ` ``) and ready for passing into the query making it suitable for escaping column or table names.

#### Parameters

`$string` is the column/table name string you would like to escape and make it ready for the query

#### Example
```php
$conditions = $database->escapeName($column) . " != 'value'";
```

### `transactionBegin()`

Begins the MySQL transaction, and any data further written will not be written into the database unless committed.

If a transaction is already started, this method increases the internal counter of concurrent transactions.

### `commit()`

If a transaction is active, commit the changes and returns `true`.
If transaction isn't active, returns `false`.

If the method `transactionBegin()` has been called multiple times, the `commit()` method won't actually commit the MySQL transaction until it's called as many times as the former method.

### `rollback()`

If a transaction is active, discard the changes and returns `true`.
If transaction isn't active, returns `false`.

No matter how many times `transactionBegin()` was called, this method discards the MySQL transaction and resets the internal transaction counter to 0.

## Conditions format
The conditions parameter is optional and can either be an array or a string.

### Array
In the case of an array, it has to be formatted like: `['first_name' => 'John', 'last_name' => 'Doe']`

Notice: all conditions specified through the array are connected with an AND constructor

### String

If you want to use other logical constructors, the conditions can be provided as a string in the following format: `first_name='John' AND last_name='Doe'`.

Notice: do not forget to manually escape the user input (with the `escape()` and `escapeName()` methods) when constructing this string.

## Error handling & debugging

Debugging can help you determine mistakes in your code that utilizes `php-db-pdo-mysql` like invalid parameter formats or wrong data types.
To enable debugging, set the static variable `$debug` of the class to `true`:
```php
\Le\DB::$debug = true;
```

When debugging is turned on, the methods can throw `Error`s containing information about what went wrong. Also, the `return_query` will automati be set to `true` on any new instances of the object.

Besides the debugging errors, `php-db-pdo-mysql` throws no exceptions on its own. Any database errors or query errors are thrown as exceptions by PDO.

To ensure your actions were finished successfully use a `try-catch` block around the main methods and the constructor:
```php
try {
  $result = $database->get('missingtable', '*');
}
catch(Throwable $e){
  echo 'database error occurred';
}
```

## Disclaimer
I do not guarantee that this code is 100% secure and it should be used at your own responsibility.

If you find any errors or mistakes, open a ticket or create a pull request.

Please feel free to leave a comment and share your thoughts on this!
