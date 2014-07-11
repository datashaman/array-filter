<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use DataShaman\ObjectFilter;


class ObjectFilterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->filter = new ObjectFilter();
    }

    public function testIsAssocArray()
    {
        $this->assertTrue($this->filter->isAssocArray(
            [ 'name' => 'Cats' ]
        ));

        $this->assertTrue($this->filter->isAssocArray(
            [ 'cats' => 'Cats', 'dogs' => 'Dogs' ]
        ));

        $this->assertTrue($this->filter->isAssocArray(
            [ 0 => 'Cats', 'name' => 'Dogs' ]
        ));

        $this->assertTrue($this->filter->isAssocArray(
            [ 'name' => 'Dogs', 0 => 'Cats' ]
        ));

        $this->assertFalse($this->filter->isAssocArray(
            [ 0 => 'Cats' ]
        ));

        $this->assertFalse($this->filter->isAssocArray(
            [ 0 => 'Cats', 1 => 'Dogs' ]
        ));
    }

    public function testBasicTests()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Cats' ],
            [ 'name' => 'Cats' ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dogs' ],
            [ 'name' => 'Cats' ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dogs' ],
            [ 'name' => [ '$any' => true ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dogs' ],
            [ 'name' => [ '$only' => [ 'Dogs', 'Cats' ] ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chickens' ],
            [ 'name' => [ '$only' => [ 'Dogs', 'Cats' ] ] ]
        ));
    }

    public function testPresent()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chickens' ],
            [ 'name' => [ '$present' => true ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'cat' => 'Meow' ],
            [ 'name' => [ '$present' => true ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chickens' ],
            [ 'name' => [ '$present' => false ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'cat' => 'Meow' ],
            [ 'name' => [ '$present' => false ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'object' => [ 'name' => 'Chickens' ] ],
            [ 'object' => [ '$present' => true ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'object' => [ 'name' => 'Chickens' ] ],
            [ 'object' => [ '$present' => false ] ]
        ));
    }

    public function testExtraUndefinedAttributes()
    {
        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dogs' ],
            [
                'name' => [ '$only' => [ 'Dogs', 'Cats' ] ],
                'value' => [ '$only' => [ 1, 2, 3, 4 ] ],
            ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dogs' ],
            [
                'name' => [ '$only' => [ 'Dogs', 'Cats' ] ],
                'value' => [ '$only' => [ 1, 2, 3, 4 ] ],
            ],
            [ 'match' => 'any' ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dogs', 'value' => 3 ],
            [
                'name' => [ '$only' => [ 'Dogs', 'Cats' ] ],
                'value' => [ '$only' => [ 1, 2, 3, 4 ] ],
            ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dogs' ],
            [
                'name' => [ '$only' => [ 'Dogs', 'Cats' ] ],
                'value' => [ '$any' => true ],
            ]
        ));
    }

    public function testSourceMatchOption()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => [ '$any' => true ], 'type' => 'animal' ],
            [ 'match' => 'source' ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'extraAttribute' => 'Stuff' ],
            [ 'name' => 'Dog', 'color' => [ '$any' => true ], 'type' => 'animal' ],
            [ 'match' => 'source' ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'extraAttribute' => 'Stuff' ],
            [ 'name' => 'Dog', 'color' => [ '$any' => true ], 'type' => 'animal' ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'extraAttribute' => 'Stuff' ],
            [ 'name' => 'Dog', 'color' => [ '$any' => true ], 'type' => 'animal', 'extraAttribute' => [
                '$only' => [ 'Stuff', 'Things' ],
            ]],
            [ 'match' => 'source' ]
        ));
    }

    public function testAllMatchOption()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), 'Equal match');

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true ] ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true ] ],
            [ 'match' => 'all' ]
        ), 'Deep equal match');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), 'Missing on first');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), 'Missing on second');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true ] ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => false ] ],
            [ 'match' => 'all' ]
        ), 'Deep attribute different');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true ] ],
            [ 'match' => 'all' ]
        ), 'No deep on first');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true ] ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), 'No deep on second');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true, 'another' => 'attribute' ] ],
            [ 'match' => 'all' ]
        ), 'Deep second has another attribute');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => [ 'deep' => true, 'another' => 'attribute' ] ],
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), 'Deep first has another attribute');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => 'blue', 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), 'Different attribute');

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ],
            [ 'name' => 'Dog', 'color' => [ '$any' => true ], 'type' => 'animal' ],
            [ 'match' => 'all' ]
        ), '$conditional');
    }

    public function testDeepMatching()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'data' => [ 'age' => 1, 'gender' => 'male' ] ],
            [ 'name' => 'Chicken', 'data' => [ 'age' => [ '$only' => [ 1, 2, 3, 4 ] ], 'gender' => 'male' ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'data' => [ 'age' => 1, 'gender' => 'female' ] ],
            [ 'name' => 'Chicken', 'data' => [ 'age' => [ '$only' => [ 1, 2, 3, 4 ] ], 'gender' => 'male' ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'data' => [ 'age' => 6, 'gender' => 'male' ] ],
            [ 'name' => 'Chicken', 'data' => [ 'age' => [ '$only' => [ 1, 2, 3, 4 ] ], 'gender' => 'male' ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'data' => [ 'age' => 1, 'gender' => 'female' ] ],
            [ 'name' => 'Chicken', 'data' => [ 'age' => [ '$only' => [ 1, 2, 3, 4 ] ], 'gender' => [ '$only' => [ 'male', 'female' ] ] ] ]
        ));
    }

    public function testArrayMatching()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4, 5 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4, 5 ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4, 5, 6 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4, 5 ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4, 5 ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, [ '$only' => [ 2, 3 ] ], 3, 4 ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ '$contains' => [ 4, 2 ] ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ '$contains' => [ 4, 2, 5 ] ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 2, 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ '$excludes' => [ 4, 2, 5 ] ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, 3 ] ],
            [ 'name' => 'Chicken', 'array' => [ '$excludes' => [ 4, 2, 5 ] ] ]
        ));
    }

    public function testCombinations()
    {
        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => 1 ], 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => 1 ], 3, 4 ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => 2 ], 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => 1 ], 3, 4 ] ]
        ));

        $this->assertTrue($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => 2 ], 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => [ '$only' => [ 1, 2 ] ] ], 3, 4 ] ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => 3 ], 3, 4 ] ],
            [ 'name' => 'Chicken', 'array' => [ 1, [ 'cat' => [ '$only' => [ 1, 2 ] ] ], 3, 4 ] ]
        ));
    }

    public function testFilterQueries()
    {
        $queryHandler = function($query, $object) {
            if ($query === 'user_id') {
                return 124;
            }
        };

        $this->assertTrue($this->filter->checkFilter(
            [ 'id' => 1, 'type' => 'comment', 'user_id' => 124 ],
            [ 'type' => 'comment', 'user_id' => [ '$query' => 'user_id' ] ],
            [ 'queryHandler' => $queryHandler ]
        ));

        $this->assertFalse($this->filter->checkFilter(
            [ 'id' => 1, 'type' => 'comment', 'user_id' => 124123 ],
            [ 'type' => 'comment', 'user_id' => [ '$query' => 'user_id' ] ],
            [ 'queryHandler' => $queryHandler ]
        ));
    }
}
