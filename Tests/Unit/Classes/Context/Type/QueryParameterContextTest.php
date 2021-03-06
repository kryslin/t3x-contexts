<?php

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

class QueryParameterContextTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        error_reporting(error_reporting() & ~E_NOTICE);

        foreach ($_GET as $key => $dummy) {
            unset($_GET[$key]);
        }
    }

    public function testMatchParameterMissing()
    {
        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            ['field_values'     , null, 'sDEF', 'lDEF', 'vDEF', '123'],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertFalse($getm->match(), 'No parameter means no match');
    }

    public function testMatchParameterNoValue()
    {
        $_GET['affID'] = '';

        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            ['field_values'     , null, 'sDEF', 'lDEF', 'vDEF', '123'],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertFalse($getm->match(), 'No value means no match');
    }

    public function testMatchParameterCorrectValue()
    {
        $_GET['affID'] = 123;

        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            ['field_values'     , null, 'sDEF', 'lDEF', 'vDEF', '123'],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertTrue($getm->match(), 'Correct value');
    }

    public function testMatchParameterCorrectValueOfMany()
    {
        $_GET['affID'] = 125;

        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            [
                'field_values'     , null, 'sDEF', 'lDEF', 'vDEF',
                "123\n124\n125\n"
            ],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertTrue($getm->match(), 'Correct value');
    }

    public function testMatchParameterWrongValueOfMany()
    {
        $_GET['affID'] = 124125;

        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            [
                'field_values'     , null, 'sDEF', 'lDEF', 'vDEF',
                "123\n124\n125\n"
            ],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertFalse($getm->match(), 'value is not allowed');
    }

    public function testMatchParameterAnyValue()
    {
        $_GET['affID'] = 'aslkfj';

        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            ['field_values'     , null, 'sDEF', 'lDEF', 'vDEF', ''],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertTrue($getm->match(), 'Any value is correct');
    }

    public function testMatchParameterAnyValueMissing()
    {
        unset($_GET['affID']);

        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'],
            ['field_values'     , null, 'sDEF', 'lDEF', 'vDEF', ''],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        self::assertFalse($getm->match(), 'Any value is missing');
    }

    /**
     * @expectedException Exception
     */
    public function testMatchUnconfiguredNoParameter()
    {
        $getm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            ['getConfValue']
        );
        $getm->setUseSession(false);
        $retValMap = [
            ['field_name'       , null, 'sDEF', 'lDEF', 'vDEF', ''],
            ['field_values'     , null, 'sDEF', 'lDEF', 'vDEF', ''],
        ];

        $getm->expects(self::any())
            ->method('getConfValue')
            ->willReturnMap($retValMap);

        $getm->match();
    }
}
