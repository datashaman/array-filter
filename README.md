JSON Filter
===

A literal port of the excellent Javascript library [ mmckegg / json-filter ](https://github.com/mmckegg/json-filter) to PHP.

Credit to [ Matt McKegg ](https://github.com/mmckegg) for creating an incredibly useful, well-tested piece of software.

Match PHP objects against filters.

## Installation

```shell
$ composer require datashaman/object-filter
```

## Filters

Filters are just objects that have the keys and values you want your final object to have. e.g. if you wanted to require that the field `type` was always `person` your filter would be `{type: 'person'}`. 

If things aren't so black and white, the following conditionals are available:

#### $present

Specify that the value must not be null or false (i.e. 'truthy'). 

```php
[
  'name' => [ '$present' => true ]
]
```

#### $any

Specify that the value can be anything. Useful when matching all keys.

```php
[
  'description' => [ '$any' => true ]
]
```

#### $contains

For matching against an array. The array must contain all of the values specified.

```php
[
  'tags' => [ '$contains' => [ 'cat', 'animal' ] ]
]
```

#### $excludes

For matching against an array. The array cannot contain any of the values specified.

```php
[
  'permissions' => [ '$excludes' => [ 'admin', 'mod' ] ]
]
```

#### $only

The value can only be one of the ones specified.

```php
[
  'gender' => [ '$only' => [ 'male', 'female', 'unknown'] ]
]
```

#### $not

The value can be anything except one of the ones specified.

```php
[
  'browser' => [ '$not' => [ 'IE', 'Firefox' ] ]
]
```

#### $matchAny

Allows a filter to branch into multiple filters when at least one must match.

```php
[
  '$matchAny' => [
    [ 'type' => "Post",
      'state' => [ '$only' => [ 'draft', 'published' ] ]
    ],
    [ 'type' => "Comment",
      'state' => [ '$only' => [ 'pending', 'approved', 'spam' ] ]
    ]
  ]
]
```

#### $query

Specify a query to get the value to match. Uses `options.queryHandler`.

```php
[
  'type' => 'item',
  'user_id' => [ '$query' => 'user.id' ]
]
```

#### $optional

A shortcut for specifying a lot of $any filters at the same time.

```php
[
  '$optional' => [ 'description', 'color', 'age' ]
]
```

Is equivalent to:

```php
[
  'description' => [ '$any' => true ],
  'color' => [ '$any' => true ],
  'age' => [ '$any' => true ]
]
```

## API

```php
use DataShaman\ObjectFilter;

$filter = new ObjectFilter;
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
- **context**: Object to pass to the query handler
