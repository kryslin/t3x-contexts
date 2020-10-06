<?php

class CombinationTest extends \Netresearch\Contexts\Tests\Unit\TestBase
{
    public function testGetDependenciesSucces()
    {
        $abstractMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );

        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            ]
        );

        $instance->expects(self::once())
            ->method('getConfValue')
            ->willReturn('(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST ');

        $arTest = $instance->getDependencies(
            [
                123 => $abstractMock,
                125 => $instance,
            ]
        );

        self::assertArrayHasKey(123, $arTest);
        self::assertEquals([123 => true], $arTest);
    }

    public function testGetDependenciesSuccesWithDisabled()
    {
        $abstractMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => true,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );

        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            ]
        );

        $instance->expects(self::once())
            ->method('getConfValue')
            ->willReturn('(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST ');

        $arTest = $instance->getDependencies(
            [
                123 => $abstractMock,
                125 => $instance,
            ]
        );

        self::assertArrayHasKey(123, $arTest);
        self::assertEquals([123 => false], $arTest);
    }

    public function testGetDependenciesEmpty()
    {
        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            ]
        );

        $instance->expects(self::once())
            ->method('getConfValue')
            ->willReturn('(context1 && context2 || context3) xor >< context5 ');

        $arTest = $instance->getDependencies(
            [125 => $instance]
        );

        self::assertEmpty($arTest);
    }

    public function testMatchSuccess()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $ipContextMock->expects(self::any())
            ->method('match')
            ->willReturn(true);
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>124,
                    'type'=>'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $getContextMock->expects(self::any())
            ->method('match')
            ->willReturn(true);

        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            ]
        );
        $container = $this->getMock(
            '\Netresearch\Contexts\Context\Container',
            []
        );

        $arContexts = [
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        ];

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->willReturn('UNITTEST && getUNITTEST');

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals(
            [
                123 => $ipContextMock,
                124 => $getContextMock,
                125 => $instance,
            ],
            $matched
        );
    }

    public function testMatchSuccessWithDisabled()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $ipContextMock->expects(self::any())
            ->method('match')
            ->willReturn(true);
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>124,
                    'type'=>'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => true,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $getContextMock->expects(self::any())
            ->method('match')
            ->willReturn(true);

        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
            ]
        );
        $container = $this->getMock(
            '\Netresearch\Contexts\Context\Container',
            []
        );

        $arContexts = [
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        ];

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->willReturn('UNITTEST && getUNITTEST');

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals(
            [
                123 => $ipContextMock,
                125 => $instance,
            ],
            $matched
        );
    }

    public function testMatchFailed()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $ipContextMock->expects(self::any())
            ->method('match')
            ->willReturn(false);
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>124,
                    'type'=>'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $getContextMock->expects(self::any())
            ->method('match')
            ->willReturn(true);

        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
                'findInContainer',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            ]
        );
        $container = $this->getMock(
            '\Netresearch\Contexts\Context\Container',
            []
        );

        $arContexts = [
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        ];

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->willReturn('UNITTEST && getUNITTEST');

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals([124 => $getContextMock], $matched);
    }

    public function testMatchFailedWithDisabled()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $ipContextMock->expects(self::any())
            ->method('match')
            ->willReturn(false);
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            [
                [
                    'uid'=>124,
                    'type'=>'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => true,
                    'hide_in_backend' => false,
                ]
            ],
            '',
            true,
            true,
            true,
            [
                '__construct',
            ]
        );
        $getContextMock->expects(self::any())
            ->method('match')
            ->willReturn(true);

        $instance = $this->getMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            [
                'getConfValue',
                'findInContainer',
            ],
            [
                'uid'=>125,
                'type'=>'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
            ]
        );
        $container = $this->getMock(
            '\Netresearch\Contexts\Context\Container',
            []
        );

        $arContexts = [
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        ];

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->willReturn('UNITTEST && getUNITTEST');

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals([], $matched);
    }
}
