phpSimpleMySQL
====================
Makes MySQL in PHP as easy as four methods and keeps it secured.
All values sent through the four data methods are automatically escaped which protects your project from SQL Injection attacks.

Getting started
---------------------
In your PHP script, load the Class file at the beginning of the code:
```php
require_once('db.class.php');
```

Now you can initialize a DB object and start working with your database like following:
```php
$database = new DB($server_host, $database_name, $username, $password, $server_port);
```
Notice: if not specified, the ```$server_port``` parameter defaults to 3306 (the default MySQL server port).

Additional settings
---------------------
Set the connection charset by calling the ```charset()``` method, e.g.:
```php
$database->charset('utf8');
```

If your table names contain prefixes, you can set a global prefix by calling the ```prefix()``` method, e.g.:
```php
$database->prefix('prefix_');
```

Data methods
---------------------
#### ```dataGet($table_name, $columns, $conditions, $limit, $order, $add_key)```
> ```$table_name``` is a string containing the name of the table we're selecting data from
> 
> Notice: the prefix will be prepended to the table name if specified
> 
> ```$columns``` is a string containing the names of columns separated by a comma (```,```), or a wildcard (```*```) if you wish to select all columns
> 
> ```$conditions``` specifies the conditions used to search for the rows, refer to the [Conditions format](#format_conditions) section for more info
> 
> ```$limit``` is an integer that represents how much rows should be fetched; if set to ```0``` or not defined, there will be no limit
> 
> ```$order``` is a string specifying the rule the results will be soreted by; refer to the [MySQL ORDER BY manual](http://dev.mysql.com/doc/refman/5.7/en/sorting-rows.html) for more info
> 
> ```$add_key``` if true and in case there is only one row returned, the single row will be inside an array with the index of 0; if not set, it defaults to false

#### ```dataInsert($table_name, $data, $stack_values)```
> ```$table_name``` is a string containing the name of the table we're inserting data in
> 
> Notice: the prefix will be prepended to the table name if specified
> 
> ```$data``` can be an array or a string containing the rows to insert into the table, refer to the [Insert Data format](#format_data_insert) section for more info
> 
> ```$stack_values``` is also explained in the Insert Data format section

#### ```dataUpdate($table_name, $data, $conditions, $limit)```
> ```$table_name``` is a string containing the name of the table we're updating the data in
> 
> Notice: the prefix will be prepended to the table name if specified
> 
> ```$data``` is an array containing the data to be updated, e.g.: ```['account_balance' => '150']```
> 
> ```$conditions``` specifies the conditions used to search for the rows, refer to the [Conditions format](#format_conditions) section for more info
> 
> ```$limit``` is an integer that represents how much rows should be affected; if set to ```0``` or not defined, there will be no limit

#### ```dataDelete($table_name, $conditions, $limit)```
> ```$table_name``` is a string containing the name of the table we're deleting data from
> 
> Notice: the prefix will be prepended to the table name if specified
> 
> ```$conditions``` specifies the conditions used to search for the rows, refer to the [Conditions format](#format_conditions) section for more info
> 
> ```$limit``` is an integer that represents how much rows should be deleted; if set to ```0``` or not defined, there will be no limit

Parameter formats
---------------------
#### <a name="format_conditions"></a>Conditions format
> It can be a string in the following format: ```first_name='John' AND last_name='Doe'```
> 
> Notice: you can use other logical constructors, such as OR, NOT, etc.: the data in the brackets is automatically extracted and escaped
> 
> Or it can be an array formatted like this: ```['first_name' => 'John', 'last_name' => 'Doe']```
> 
> Notice: all conditions specified through the array are connected with an AND constructor; you can make a NOT constructor by adding a bang (```!```) at the end of the column name

#### <a name="format_data_insert"></a>Insert Data Format
> This is an array and it can be formated in one of the following ways:
> 
> ```['first_name' => 'John', 'last_name' => 'Doe']```
> 
> Notice: you can use this formatting type only if you are inserting a single row
> 
> ```[['first_name' => 'Bob', 'last_name' => 'Hills'], ['first_name' => 'Mike', 'last_name' => 'Rotch']]```
> 
> ```['first_name' => ['Bob', 'Mike'], 'last_name' => ['Hills', 'Rotch']]```
> 
> Notice: if you are using the last format type, the ```$stack_values``` parameter has to be ```true```, because the values are stacked under the same key name

Disclaimer
---------------------
I do not guarantee that this code is 100% secure and it should be used at your own responsibility.

If you find any errors or mistakes, open a ticket or create a pull request.

Please feel free to leave a comment and share your thoughts on this!
