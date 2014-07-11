Array Filter
======

A literal port of the excellent Javascript library [ mmckegg / json-filter ](https://github.com/mmckegg/json-filter) to PHP.

Credit to [ Matt McKegg ](https://github.com/mmckegg) for creating an incredibly useful, well-tested piece of software.

Match PHP arrays against filters.

## Installation

```shell
$ composer require datashaman/array-filter
```

## Filters

Filters are just arrays that have the keys and values you want your final array to have. e.g. if you wanted to require that the field `type` was always `person` your filter would be `{type: 'person'}`. 

If things aren't so black and white, the following conditionals are available:

#### $present

Specify that the value must not be null or false (i.e. 'truthy'). 

```php
array(
  'name' => array( '$present' => true )
)
```

#### $any

Specify that the value can be anything. Useful when matching all keys.

```php
array(
  'description' => array( '$any' => true )
)
```

#### $contains

For matching against an array. The array must contain all of the values specified.

```php
array(
  'tags' => array( '$contains' => array( 'cat', 'animal' ) )
)
```

#### $excludes

For matching against an array. The array cannot contain any of the values specified.

```php
array(
  'permissions' => array( '$excludes' => array( 'admin', 'mod' ) )
)
```

#### $only

The value can only be one of the ones specified.

```php
array(
  'gender' => array( '$only' => array( 'male', 'female', 'unknown' ) )
)
```

#### $not

The value can be anything except one of the ones specified.

```php
array(
  'browser' => array( '$not' => array( 'IE', 'Firefox' ) )
)
```

#### $matchAny

Allows a filter to branch into multiple filters when at least one must match.

```php
array(
  '$matchAny' => array(
    array( 'type' => "Post",
      'state' => array( '$only' => array( 'draft', 'published' ) )
    ),
    array( 'type' => "Comment",
      'state' => array( '$only' => array( 'pending', 'approved', 'spam' ) )
    )
  )
)
```

#### $query

Specify a query to get the value to match. Uses `options.queryHandler`.

```php
array(
  'type' => 'item',
  'user_id' => array( '$query' => 'user.id' )
)
```

#### $optional

A shortcut for specifying a lot of $any filters at the same time.

```php
array(
  '$optional' => array( 'description', 'color', 'age' )
)
```

Is equivalent to:

```php
array(
  'description' => array( '$any' => true ),
  'color' => array( '$any' => true ),
  'age' => array( '$any' => true )
)
```

## API

```php
use DataShaman\ArrayFilter;

$filter = new ArrayFilter;
$filter->checkFilter($source, $filter, $options);
```

### checkFilter(source, filter, options)

#### options:

- **match**: specify: 'filter', 'source', 'any', 'all'
  - filter: every filter permission must be satisfied (i.e. required fields)
  - source: every source key must be specified in filter
  - any: the keys don't matter, but if there is a match, they must pass
  - all: all keys must be exactly the same, otherwise fails - for finding changed items - no $conditionals work in this mode
- **queryHandler**: Accepts a function(query, localContext) that returns resulting value
- **context**: Array to pass to the query handler
