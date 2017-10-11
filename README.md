# phpSimpleMySQL
Makes MySQL in PHP as easy as four methods and keeps it secure.

All values sent through the four data methods are automatically escaped which protects your project from SQL Injection attacks.

## Getting started
In your PHP script, load the Class file at the beginning of the code:
```php
require_once('db.class.php');
```

Now you can initialize a DB object and start working with your database like following:
```php
$options = [
  'host' => 'localhost', 'port' => 3306, 'charset' => 'utf8',
  'database' => 'db', 'prefix' => 'prefix_',
  'user': '' => 'password': ''
];
$database = new \Le\DB($options);
```

## Methods
### `dataGet($table_name, $columns, $conditions, $additional)`
`$table_name` is a string containing the name of the table we're selecting data from. Notice: the prefix will be prepended to the table name if specified

`$columns` is a string containing the names of columns separated by a comma (`,`), or a wildcard (`*`) if you wish to select all columns

`$conditions` specifies the conditions used to search for the rows, refer to the [Conditions format](#conditions-format) section for more info

`$additional:` an array that contains all additions to the query; can contain the following keys:
-   `'limit'` is an integer that represents how many rows should be fetched; if set to `0` or not defined, there will be no limit
-   `'offset'` defines how many rows to skip in the result; if not set, defaults to 0. Notice: it can only be used if the `'limit'` is also set
-   `'order'` is a string specifying the rule the results will be sorted by; refer to the [MySQL ORDER manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info
-   `'single_no_key'` if true and accompanied with `'limit'` set to 1, the single row will not be inside an array with the index of 0; if not set, it defaults to false

### `dataInsert($table_name, $data, $additional)`
`$table_name` is a string containing the name of the table we're inserting data in. Notice: the prefix will be prepended to the table name if specified

`$data` can be an array or a string containing the rows to insert into the table, refer to the [Insert Data format](#insert-data-format) section for more info

`$additional:` an array that contains all additions to the query; can contain the following keys:
-   `'stacked_values'` is also explained in the [Insert Data format](#insert-data-format) section

### `dataUpdate($table_name, $data, $conditions, $additional)`
`$table_name` is a string containing the name of the table we're updating the data in. Notice: the prefix will be prepended to the table name if specified

`$data` is an array containing the data to be updated, e.g.: `['account_balance' => '150']`

`$conditions` specifies the conditions used to search for the rows, refer to the [Conditions format](#conditions-format) section for more info

`$additional:` an array that contains all additions to the query; can contain the following keys:
-   `'limit'` is an integer that represents how many rows should be updated; if set to `0` or not defined, there will be no limit
-   `'offset'` defines how many rows to skip in the query; if not set, defaults to 0. Notice: it can only be used if the `'limit'` is also set
-   `'order'` is a string specifying the rule the results will be sorted by; refer to the [MySQL ORDER manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info

### `dataDelete($table_name, $conditions, $additional)`
`$table_name` is a string containing the name of the table we're deleting data from. Notice: the prefix will be prepended to the table name if specified

`$conditions` specifies the conditions used to search for the rows, refer to the [Conditions format](#conditions-format) section for more info

`$additional:` an array that contains all additions to the query; can contain the following keys:
-   `'limit'` is an integer that represents how many rows should be deleted; if set to `0` or not defined, there will be no limit
-   `'offset'` defines how many rows to skip in the query; if not set, defaults to 0. Notice: it can only be used if the `'limit'` is also set
-   `'order'` is a string specifying the rule the results will be sorted by; refer to the [MySQL ORDER manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info

### `escape($data)`
It returns a string escaped with single quotes (`'`) and ready for passing into the `$conditions` parameter.

`$data` is the string you would like to escape and make it ready for the query

## Parameter formats
### Conditions format
It can be an array formatted like this: `['first_name' => 'John', 'last_name' => 'Doe']`

Notice: all conditions specified through the array are connected with an AND constructor; you can make a NOT constructor by adding a bang (`!`) at the end of the column name

If you want to use other logical constructors, the conditions can be provided as a string in the following format: `first_name='John' AND last_name='Doe'`.

Notice: do not forget to manually escape the user input (with the `escape()` method) when constructing this string.

### Insert Data Format
This is an array and it can be formated in one of the following ways:

`['first_name' => 'John', 'last_name' => 'Doe']`

Notice: you can use this formatting type only if you are inserting a single row

`[['first_name' => 'Bob', 'last_name' => 'Hills'], ['first_name' => 'Mike', 'last_name' => 'Rotch']]`

`['first_name' => ['Bob', 'Mike'], 'last_name' => ['Hills', 'Rotch']]`

Notice: if you are using the last format type, the `'stacked_values'` element of the `$additional` parameter has to be `true`, because the values are stacked under the same key name

## Output
The [Methods](#methods) return an array that consists of the following elements:
-   `query` the query sent to the server
-   `success` a boolean stating if the result was successful
-   `error` defined in case of an error; has three elements: `code`, `message` and `trace`
-   `count` the number of the rows affected by the query
-   `id` row ID of the last inserted row; only for the `dataInsert()` method
-   `data` is an array of rows returned by the query for `dataGet()` method; if `'single_no_key'` was used, there is no nesting

## Example
```php
$result = $database->dataGet('table', 'column1, column2', ['shown' => true], ['limit' => 10]);
print_r($result);
```

## Disclaimer
I do not guarantee that this code is 100% secure and it should be used at your own responsibility.

If you find any errors or mistakes, open a ticket or create a pull request.

Please feel free to leave a comment and share your thoughts on this!
