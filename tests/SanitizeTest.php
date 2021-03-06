<?php

namespace rockunit;


use rock\sanitize\Attributes;
use rock\sanitize\rules\Rule;
use rock\sanitize\Sanitize;

class SanitizeTest extends \PHPUnit_Framework_TestCase
{
    public function testScalar()
    {
        $sanitize = Sanitize::removeTags();
        $this->assertSame('foo bar', $sanitize->sanitize('foo <b>bar</b>'));
    }

    public function testAttributes()
    {
        $input = [
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz'
        ];
        $expected = [
            'name' => 'foo bar',
            'email' => 'bar alert(\'Hello\');baz',
        ];
        $sanitize = Sanitize::attributes(
            [
                'name' => Sanitize::removeTags(),
                'email' => Sanitize::removeScript()
            ]
        );
        $this->assertSame($expected, $sanitize->sanitize($input));

        // skip
        $expected = [
            'name' => 'foo <b>bar</b>',
            'email' => 'bar alert(\'Hello\');baz',
        ];
        $sanitize = Sanitize::attributes(
            [
                'username' => Sanitize::removeTags(),
                'email' => Sanitize::removeScript()
            ]
        );
        $this->assertSame($expected, $sanitize->sanitize($input));
    }

    public function testAttributesAsObject()
    {
        $input = (object)[
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz'
        ];
        $expected = [
            'name' => 'foo bar',
            'email' => 'bar alert(\'Hello\');baz',
        ];
        $sanitize = Sanitize::attributes(
            [
                'name' => Sanitize::removeTags(),
                'email' => Sanitize::removeScript()
            ]
        );
        $this->assertInstanceOf('\stdClass', $sanitize->sanitize($input));
        $this->assertSame($expected, (array)$sanitize->sanitize($input));

        // skip
        $input = (object)[
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz'
        ];
        $expected = [
            'name' => 'foo <b>bar</b>',
            'email' => 'bar alert(\'Hello\');baz',
        ];
        $sanitize = Sanitize::attributes(
            [
                'username' => Sanitize::removeTags(),
                'email' => Sanitize::removeScript()
            ]
        );
        $this->assertInstanceOf('\stdClass', $sanitize->sanitize($input));
        $this->assertSame($expected, (array)$sanitize->sanitize($input));
    }

    /**
     * @expectedException \rock\sanitize\SanitizeException
     */
    public function testAttributesThrowException()
    {
        $input = [
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz'
        ];
        $sanitize = Sanitize::attributes(
            [
                'name' => 'unknown',
                'email' => Sanitize::removeScript()
            ]
        );
        $sanitize->sanitize($input);
    }

    public function testRemainder()
    {
        $input = [
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz',
            'age' => 22,
            'wages' => -100
        ];
        $expected = [
            'name' => 'foo bar',
            'email' => 'bar alert(\'Hello\');baz',
            'age' => 22,
            'wages' => 0,
        ];
        $sanitize = Sanitize::attributes(
            [
                'name' => Sanitize::removeTags(),
                '*' => Sanitize::positive(),
                'email' => Sanitize::removeScript(),
            ]
        );
        $this->assertSame($expected, $sanitize->sanitize($input));

        // custom label
        $sanitize = Sanitize::attributes([
                'name' => Sanitize::removeTags(),
                '_rem' => Sanitize::positive(),
                'email' => Sanitize::removeScript(),
            ])->setRemainder('_rem');
        $this->assertSame($expected, $sanitize->sanitize($input));
    }

    public function testAllOf()
    {
        $input = [
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz'
        ];
        $expected = [
            'name' => 'foo bar',
            'email' => 'bar alert(\'Hello\');baz',
        ];
        $sanitize = Sanitize::attributes(Sanitize::removeTags());
        $this->assertSame($expected, $sanitize->sanitize($input));
    }

    public function testAllOfAsObject()
    {
        $input = (object)[
            'name' => 'foo <b>bar</b>',
            'email' => 'bar <script>alert(\'Hello\');</script>baz'
        ];
        $expected = [
            'name' => 'foo bar',
            'email' => 'bar alert(\'Hello\');baz',
        ];
        $sanitize = Sanitize::attributes(Sanitize::removeTags());
        $this->assertInstanceOf('\stdClass', $sanitize->sanitize($input));
        $this->assertSame($expected, (array)$sanitize->sanitize($input));

        $sanitize = Sanitize::removeTags();
        $this->assertInstanceOf('\stdClass', $sanitize->sanitize($input));
        $this->assertSame($expected, (array)$sanitize->sanitize($input));
    }

    public function testRecursive()
    {
        $input = [
            'name' => '<b>Tom</b>',
            'other' => [
                'email' => '<b>tom@site.com</b>',
                'username' => '<b>user</b>',
                'note' => [
                    '<b>text...</b>'
                ]
            ]
        ];

        $expected = [
            'name' => 'Tom',
            'other' =>
                [
                    'email' => 'tom@site.com',
                    'username' => 'user',
                    'note' => [
                        'text...'
                    ]
                ],
        ];
        $sanitize = Sanitize::removeTags();
        $this->assertSame($expected, Sanitize::attributes($sanitize)->sanitize($input));

        $input = [
            [
                'name' => '<b>Tom</b>',
                'other' => [
                    'email' => '<b>tom@site.com</b>'
                ]
            ],
            [
                'name' => '<i>Jerry</i>',
                'other' => [
                    'email' => '<b>jerry@site.com</b>'
                ]
            ]
        ];

        $sanitize = Sanitize::removeTags();
        $expected = [
                [
                    'name' => 'Tom',
                    'other' =>
                        [
                            'email' => 'tom@site.com',
                        ],
                ],

                [
                    'name' => 'Jerry',
                    'other' =>
                        [
                            'email' => 'jerry@site.com',
                        ],
                ],
        ];
        $this->assertSame($expected, Sanitize::attributes($sanitize)->sanitize($input));

        // fail
        $input = [
            'name' => '<b>Tom</b>',
            'other' => [
                'email' => '<b>tom@site.com</b>',
                'note' => [
                    '<b>text...</b>'
                ]
            ]
        ];

        $expected = [
            'name' => 'Tom',
            'other' =>
                [
                    'email' => '<b>tom@site.com</b>',
                    'note' => [
                        '<b>text...</b>'
                    ]
                ],
        ];
        $this->assertSame($expected, Sanitize::attributes(Sanitize::removeTags())->setRecursive(false)->sanitize($input));

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'email' => '<b>tom@site.com</b>',
                    'note' => [
                        '<b>text...</b>'
                    ]
                ],
        ];
        $this->assertSame($expected, Sanitize::attributes(['other' => Sanitize::removeTags()])->setRecursive(false)->sanitize($input));
    }

    public function testRecursiveAsObject()
    {
        $object = new Recursive_1();
        $s = Sanitize::attributes(Sanitize::removeTags()->trim())->sanitize($object);
        $this->assertSame('text foo', $s->foo);
        $this->assertSame('text bar', $s->bar);

        $object = new Recursive_1();
        $s = Sanitize::attributes(['bar' => Sanitize::removeTags()->trim()])->sanitize($object);
        $this->assertSame('<b>text</b> foo', $s->foo);
        $this->assertSame('text bar', $s->bar);

        $object = new Recursive_1();
        $object->bar = new Recursive_2();
        $s = Sanitize::attributes(Sanitize::removeTags())->sanitize($object);
        $this->assertSame('text foo', $s->foo);
        $this->assertSame('text baz', $s->bar->baz);

        $object = new Recursive_1();
        $object->bar = new Recursive_2();
        $s = Sanitize::attributes(['bar' =>Sanitize::removeTags()])->sanitize($object);
        $this->assertSame('<b>text</b> foo', $s->foo);
        $this->assertSame('text baz', $s->bar->baz);

        // fail
        $object = new Recursive_1();
        $object->bar = new Recursive_2();
        $s = Sanitize::attributes(Sanitize::removeTags())->setRecursive(false)->sanitize($object);
        $this->assertSame('text foo', $s->foo);
        $this->assertSame('text <b>baz</b>', $s->bar->baz);
    }

    public function testRecursiveMultiArray()
    {
        $input = [
            'name' => '<b>Tom</b>',
            'other' => [
                'text' => '<b> foo</b> ',
                'email' => '<b> tom@site.com </b>',
                'note' => [
                    'first' => ' <b>text... </b>'
                ]
            ]
        ];

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => 'foo',
                    'email' => 'tom@site.com',
                    'note' =>
                        [
                            'first' => 'text...',
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other' =>  Sanitize::removeTags()->trim()])->sanitize($input));

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => '<b> foo</b> ',
                    'email' => '<b> tom@site.com </b>',
                    'note' =>
                        [
                            'first' => ' <b>text... </b>',
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other' =>  Sanitize::removeTags()->trim()])->setRecursive(false)->sanitize($input));
    }

    public function testChain()
    {
        $input = [
            'name' => '<b>Tom</b>',
            'other' => [
                'text' => '<b>foo</b>',
                'email' => '<b>tom@site.com</b>',
                'note' => [
                    'first' => '<b>text...</b>',
                    'last' => '<b>last...</b>'
                ]
            ]
        ];
        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => '<b>foo</b>',
                    'email' => 'tom@site.com',
                    'note' =>
                        [
                            'first' => '<b>text...</b>',
                            'last' => '<b>last...</b>'
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other.email' =>  Sanitize::removeTags()])->sanitize($input));

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => '<b>foo</b>',
                    'email' => 'tom@site.com',
                    'note' =>
                        [
                            'first' => '<b>text...</b>',
                            'last' => 'last...'
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other.email' =>  Sanitize::removeTags(), 'other.note.last' =>  Sanitize::removeTags()])->sanitize($input));

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => '<b>foo</b>',
                    'email' => 'tom@site.com',
                    'note' =>
                        [
                            'first' => 'text...',
                            'last' => 'last...'
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other.email' =>  Sanitize::removeTags(), 'other.note' =>  Sanitize::removeTags()])->sanitize($input));

        // fail

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => '<b>foo</b>',
                    'email' => '<b>tom@site.com</b>',
                    'note' =>
                        [
                            'first' => '<b>text...</b>',
                            'last' => '<b>last...</b>'
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other.emaill' =>  Sanitize::removeTags()])->sanitize($input));

        $expected = [
            'name' => '<b>Tom</b>',
            'other' =>
                [
                    'text' => '<b>foo</b>',
                    'email' => 'tom@site.com',
                    'note' =>
                        [
                            'first' => '<b>text...</b>',
                            'last' => '<b>last...</b>',
                        ],
                ],
        ];
        $this->assertEquals($expected, Sanitize::attributes(['other.email' =>  Sanitize::removeTags(), 'other.note' =>  Sanitize::removeTags()])->setRecursive(false)->sanitize($input));
    }

    /**
     * @expectedException \rock\sanitize\SanitizeException
     */
    public function testUnknownRule()
    {
        Sanitize::unknown()->sanitize('');
    }

    public function testCustomRule()
    {
        $config = [
            'rules' => [
                'round' => Round::className()
            ]
        ];
        $s = new Sanitize($config);
        $this->assertSame(7.0, $s->round()->sanitize(7.4));
    }

    public function testRules()
    {
        $rules = ['removeTags', 'call' => ['trim'], 'toType'];
        $this->assertSame(777, Sanitize::rules($rules)->sanitize('<b> 777</b>'));
    }

    public function testExistsRule()
    {
        $this->assertTrue((new Sanitize())->existsRule('string'));
        $this->assertFalse((new Sanitize())->existsRule('unknown'));
    }

    public function testMultiRules()
    {
        $s = Sanitize::call('strip_tags')->call('abs');
        $this->assertSame(5.5, $s->sanitize('<b>-5.5</b>'));
    }

    /**
     * @link https://github.com/romeOz/rock-sanitize/issues/1
     */
    public function testIssue1()
    {
        $sanitize = Sanitize::attributes(Sanitize::removeTags()->call('trim')->toType());
        $input = [
            'form' => [
                '_csrf' => 'foo',
                'email' => '',
                'password' => ' <b>bar</b> ',
            ],
            'button' => '',
        ];
        $this->assertSame(
            [
                'form' =>
                    [
                        '_csrf' => 'foo',
                        'email' => '',
                        'password' => 'bar',
                    ],
                'button' => '',
            ],
            $sanitize->sanitize($input)
        );
    }

    /**
     * @link https://github.com/romeOz/rock-sanitize/issues/2
     */
    public function testIssue2()
    {
        $sanitize = Sanitize::attributes(Sanitize::rules([['call' => 'trim']]));
        $input = [
            'form' => [
                '_csrf' => 'foo',
                'email' => '',
                'password' => ' bar ',
            ],
            'button' => ' baz   ',
        ];
        $this->assertSame(
            array (
                'form' =>
                    array (
                        '_csrf' => 'foo',
                        'email' => '',
                        'password' => 'bar',
                    ),
                'button' => 'baz',
            ),
            $sanitize->sanitize($input)
        );
    }

    public function testGetRawRules()
    {
        $rawRules = Sanitize::attributes(Sanitize::removeTags())->getRawRules();
        $rawRule = current($rawRules);
        $this->assertTrue($rawRule instanceof Attributes);
    }
}

class Round extends Rule
{
    protected $precision = 0;
    public function __construct($precision = 0)
    {
        $this->precision= $precision;
    }

    /**
     * @inheritdoc
     */
    public function sanitize($input)
    {
        return round($input, $this->precision);
    }
}

class Recursive_1
{
    public $foo = '<b>text</b> foo';
    public $bar = ' <b>text bar </b> ';
}

class Recursive_2
{
    public $baz = 'text <b>baz</b>';
}