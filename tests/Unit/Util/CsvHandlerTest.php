<?php

namespace App\Tests\Unit\Util;

use App\Util\CsvHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CsvHandler::class)]
class CsvHandlerTest extends TestCase
{

    #[DataProvider('providerForGenerate')]
    public function testGenerate(array $arrayData, array $expectedCsv, ?string $seperator): void
    {
        $this->assertSame($expectedCsv, CsvHandler::generateFromArray($arrayData, $seperator));
    }


    #[DataProvider('providerForGenerateWillThrowException')]
    public function testGenerateWillThrowException(array $arrayData): void
    {
        $this->expectException(InvalidArgumentException::class);

        CsvHandler::generateFromArray($arrayData);
    }

    public static function providerForGenerate(): array
    {
        return [
            'default seperator' => [
                'arrayData' => [
                    [
                        'test1' => 'a',
                        'test2' => 'b',
                        'test3' => 'c',
                    ],
                    [
                        'test1' => '1',
                        'test2' => '2',
                        'test3' => '3',
                    ]
                ],
                'expectedCsv' => [
                    'test1,test2,test3',
                    'a,b,c',
                    '1,2,3',
                ],
                'seperator' => null,
            ],
            'custom seperator' => [
                'arrayData' => [
                    [
                        'test1' => 'a',
                        'test2' => 'b',
                        'test3' => 'c',
                    ],
                    [
                        'test1' => '1',
                        'test2' => '2',
                        'test3' => '3',
                    ]
                ],
                'expectedCsv' => [
                    'test1;test2;test3',
                    'a;b;c',
                    '1;2;3',
                ],
                'seperator' => ';',
            ],
        ];
    }

    public static function providerForGenerateWillThrowException(): array
    {
        return [
            'single dimensional input array' => [
                'arrayData' => [
                    1,
                    2,
                    3,
                ],
            ],
            'multi dimensional array with unequal layers' => [
                'arrayData' => [
                    [
                        'test1' => 1,
                        'test2' => 2,
                    ],
                    [
                        'test1' => 3,
                    ]
                ],
            ],
            'multi dimensional array with unequal keys' => [
                'arrayData' => [
                    [
                        'test1' => 1,
                        'test2' => 2,
                    ],
                    [
                        'test3' => 3,
                        'test4' => 4,
                    ]
                ],
            ],
            'multi dimensional array is not associative' => [
                'arrayData' => [
                    [
                        1,
                        2,
                    ],
                    [
                        3,
                        4,
                    ]
                ],
            ],
            'multi dimensional array has more than 2 layers' => [
                'arrayData' => [
                    [
                        'test' => [
                            'nestedTest' => 1,
                        ]
                    ]
                ],
            ],
        ];
    }
}