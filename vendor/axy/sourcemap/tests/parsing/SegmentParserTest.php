<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\parsing;

use axy\sourcemap\parsing\SegmentParser;
use axy\sourcemap\PosMap;

/**
 * coversDefaultClass axy\sourcemap\parsing\SegmentParser
 */
class SegmentParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::nextLine
     * covers ::parse
     */
    public function testParse()
    {
        $parser = new SegmentParser();
        $parser->nextLine(0);
        $pos1 = $parser->parse('AAAA');
        $this->assertInstanceOf('axy\sourcemap\PosMap', $pos1);
        $expected1 = [
            [
                'line' => 0,
                'column' => 0,
            ],
            [
                'fileIndex' => 0,
                'fileName' => null,
                'line' => 0,
                'column' => 0,
                'nameIndex' => null,
                'name' => null,
            ],
        ];
        $this->assertSame($expected1, [(array)$pos1->generated, (array)$pos1->source]);
        $pos2 = $parser->parse('CCCEC');
        $this->assertInstanceOf('axy\sourcemap\PosMap', $pos2);
        $expected2 = [
            [
                'line' => 0,
                'column' => 1,
            ],
            [
                'fileIndex' => 1,
                'fileName' => null,
                'line' => 1,
                'column' => 2,
                'nameIndex' => 1,
                'name' => null,
            ],
        ];
        $this->assertSame($expected2, [(array)$pos2->generated, (array)$pos2->source]);
        $parser->nextLine(3);
        $pos3 = $parser->parse('GACD');
        $this->assertInstanceOf('axy\sourcemap\PosMap', $pos3);
        $expected3 = [
            [
                'line' => 3,
                'column' => 3,
            ],
            [
                'fileIndex' => 1,
                'fileName' => null,
                'line' => 2,
                'column' => 1,
                'nameIndex' => null,
                'name' => null,
            ],
        ];
        $this->assertSame($expected3, [(array)$pos3->generated, (array)$pos3->source]);
    }

    /**
     * covers ::parse
     * @dataProvider providerParseError
     * @param string $segment
     * @expectedException \axy\sourcemap\errors\InvalidMappings
     */
    public function testParseError($segment)
    {
        $parser = new SegmentParser();
        $parser->parse($segment);
    }

    /**
     * @return array
     */
    public function providerParseError()
    {
        return [
            'empty' => [''],
            'count3' => ['AAA'],
            'count7' => ['AAAAAAA'],
            'base64' => ['AA*A'],
            'vlq' => ['AAAz'],
        ];
    }

    /**
     * covers ::pack
     * covers ::nextLine
     */
    public function testPack()
    {
        $parser = new SegmentParser();
        $parser->nextLine(0);
        $params1 = [
            [
                'line' => 0,
                'column' => 0,
            ],
            [
                'fileIndex' => 0,
                'line' => 0,
                'column' => 0,
            ],
        ];
        $pos1 = new PosMap($params1[0], $params1[1]);
        $this->assertSame('AAAA', $parser->pack($pos1));
        $params2 = [
            [
                'line' => 0,
                'column' => 1,
            ],
            [
                'fileIndex' => 1,
                'line' => 1,
                'column' => 2,
                'nameIndex' => 1,
            ],
        ];
        $pos2 = new PosMap($params2[0], $params2[1]);
        $this->assertSame('CCCEC', $parser->pack($pos2));
        $parser->nextLine(3);
        $params3 = [
            [
                'line' => 3,
                'column' => 3,
            ],
            [
                'fileIndex' => 1,
                'line' => 2,
                'column' => 1,
            ],
        ];
        $pos3 = new PosMap($params3[0], $params3[1]);
        $this->assertSame('GACD', $parser->pack($pos3));
    }
}
