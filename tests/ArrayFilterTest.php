<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DataShaman\ArrayFilter;


class ArrayFilterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->filter = new ArrayFilter();
    }

    public function testIsAssocArray()
    {
        $this->assertTrue($this->filter->isAssocArray(
            array( 'name' => 'Cats' )
        ));

        $this->assertTrue($this->filter->isAssocArray(
            array( 'cats' => 'Cats', 'dogs' => 'Dogs' )
        ));

        $this->assertTrue($this->filter->isAssocArray(
            array( 0 => 'Cats', 'name' => 'Dogs' )
        ));

        $this->assertTrue($this->filter->isAssocArray(
            array( 'name' => 'Dogs', 0 => 'Cats' )
        ));

        $this->assertFalse($this->filter->isAssocArray(
            array( 0 => 'Cats' )
        ));

        $this->assertFalse($this->filter->isAssocArray(
            array( 0 => 'Cats', 1 => 'Dogs' )
        ));
    }

    public function testBasicTests()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Cats' ),
            array( 'name' => 'Cats' )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dogs' ),
            array( 'name' => 'Cats' )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dogs' ),
            array( 'name' => array( '$any' => true ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dogs' ),
            array( 'name' => array( '$only' => array( 'Dogs', 'Cats' ) ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chickens' ),
            array( 'name' => array( '$only' => array( 'Dogs', 'Cats' ) ) )
        ));
    }

    public function testPresent()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chickens' ),
            array( 'name' => array( '$present' => true ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'cat' => 'Meow' ),
            array( 'name' => array( '$present' => true ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chickens' ),
            array( 'name' => array( '$present' => false ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'cat' => 'Meow' ),
            array( 'name' => array( '$present' => false ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'object' => array( 'name' => 'Chickens' ) ),
            array( 'object' => array( '$present' => true ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'object' => array( 'name' => 'Chickens' ) ),
            array( 'object' => array( '$present' => false ) )
        ));
    }

    public function testExtraUndefinedAttributes()
    {
        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dogs' ),
            array(
                'name' => array( '$only' => array( 'Dogs', 'Cats' ) ),
                'value' => array( '$only' => array( 1, 2, 3, 4 ) ),
            )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dogs' ),
            array(
                'name' => array( '$only' => array( 'Dogs', 'Cats' ) ),
                'value' => array( '$only' => array( 1, 2, 3, 4 ) ),
            ),
            array( 'match' => 'any' )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dogs', 'value' => 3 ),
            array(
                'name' => array( '$only' => array( 'Dogs', 'Cats' ) ),
                'value' => array( '$only' => array( 1, 2, 3, 4 ) ),
            )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dogs' ),
            array(
                'name' => array( '$only' => array( 'Dogs', 'Cats' ) ),
                'value' => array( '$any' => true ),
            )
        ));
    }

    public function testSourceMatchOption()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => array( '$any' => true ), 'type' => 'animal' ),
            array( 'match' => 'source' )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'extraAttribute' => 'Stuff' ),
            array( 'name' => 'Dog', 'color' => array( '$any' => true ), 'type' => 'animal' ),
            array( 'match' => 'source' )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'extraAttribute' => 'Stuff' ),
            array( 'name' => 'Dog', 'color' => array( '$any' => true ), 'type' => 'animal' )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'extraAttribute' => 'Stuff' ),
            array( 'name' => 'Dog', 'color' => array( '$any' => true ), 'type' => 'animal', 'extraAttribute' => array(
                '$only' => array( 'Stuff', 'Things' ),
            )),
            array( 'match' => 'source' )
        ));
    }

    public function testAllMatchOption()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), 'Equal match');

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true ) ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true ) ),
            array( 'match' => 'all' )
        ), 'Deep equal match');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), 'Missing on first');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), 'Missing on second');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true ) ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => false ) ),
            array( 'match' => 'all' )
        ), 'Deep attribute different');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true ) ),
            array( 'match' => 'all' )
        ), 'No deep on first');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true ) ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), 'No deep on second');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true, 'another' => 'attribute' ) ),
            array( 'match' => 'all' )
        ), 'Deep second has another attribute');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal', 'object' => array( 'deep' => true, 'another' => 'attribute' ) ),
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), 'Deep first has another attribute');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => 'blue', 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), 'Different attribute');

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Dog', 'color' => 'gray', 'type' => 'animal' ),
            array( 'name' => 'Dog', 'color' => array( '$any' => true ), 'type' => 'animal' ),
            array( 'match' => 'all' )
        ), '$conditional');
    }

    public function testDeepMatching()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'data' => array( 'age' => 1, 'gender' => 'male' ) ),
            array( 'name' => 'Chicken', 'data' => array( 'age' => array( '$only' => array( 1, 2, 3, 4 ) ), 'gender' => 'male' ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'data' => array( 'age' => 1, 'gender' => 'female' ) ),
            array( 'name' => 'Chicken', 'data' => array( 'age' => array( '$only' => array( 1, 2, 3, 4 ) ), 'gender' => 'male' ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'data' => array( 'age' => 6, 'gender' => 'male' ) ),
            array( 'name' => 'Chicken', 'data' => array( 'age' => array( '$only' => array( 1, 2, 3, 4 ) ), 'gender' => 'male' ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'data' => array( 'age' => 1, 'gender' => 'female' ) ),
            array( 'name' => 'Chicken', 'data' => array( 'age' => array( '$only' => array( 1, 2, 3, 4 ) ), 'gender' => array( '$only' => array( 'male', 'female' ) ) ) )
        ));
    }

    public function testArrayMatching()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4, 5 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4, 5 ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4, 5, 6 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4, 5 ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4, 5 ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, array( '$only' => array( 2, 3 ) ), 3, 4 ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( '$contains' => array( 4, 2 ) ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( '$contains' => array( 4, 2, 5 ) ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 2, 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( '$excludes' => array( 4, 2, 5 ) ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, 3 ) ),
            array( 'name' => 'Chicken', 'array' => array( '$excludes' => array( 4, 2, 5 ) ) )
        ));
    }

    public function testCombinations()
    {
        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => 1 ), 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => 1 ), 3, 4 ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => 2 ), 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => 1 ), 3, 4 ) )
        ));

        $this->assertTrue($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => 2 ), 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => array( '$only' => array( 1, 2 ) ) ), 3, 4 ) )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => 3 ), 3, 4 ) ),
            array( 'name' => 'Chicken', 'array' => array( 1, array( 'cat' => array( '$only' => array( 1, 2 ) ) ), 3, 4 ) )
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
            array( 'id' => 1, 'type' => 'comment', 'user_id' => 124 ),
            array( 'type' => 'comment', 'user_id' => array( '$query' => 'user_id' ) ),
            array( 'queryHandler' => $queryHandler )
        ));

        $this->assertFalse($this->filter->checkFilter(
            array( 'id' => 1, 'type' => 'comment', 'user_id' => 124123 ),
            array( 'type' => 'comment', 'user_id' => array( '$query' => 'user_id' ) ),
            array( 'queryHandler' => $queryHandler )
        ));
    }
}
