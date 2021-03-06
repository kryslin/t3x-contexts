<?php

namespace Netresearch\Contexts\Tests\Functional;

class TcaTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/contexts'];

    /**
     * Test the tca configuration
     */
    public function testHasTcaEntries()
    {
        //contexts types
        self::assertArrayHasKey('domain', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('getparam', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('ip', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('httpheader', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('combination', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('session', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);

        //contexts flat settings fo pages/tt_content
        self::assertArrayHasKey('extensionFlatSettings', $GLOBALS['TCA']['tx_contexts_contexts']);
        self::assertSame('tx_contexts', $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['pages'][0]);
        self::assertSame('tx_contexts_nav', $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['pages'][1]);
        self::assertSame('tx_contexts', $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['tt_content'][0]);

        //pages
        self::assertArrayHasKey('enableSettings', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']);
        self::assertSame('tx_contexts', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['enableSettings']);
        self::assertArrayHasKey('flatSettings', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']);
        self::assertSame('tx_contexts_disable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][0]);
        self::assertSame('tx_contexts_enable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][1]);
        self::assertSame('tx_contexts_nav_disable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts_nav'][0]);
        self::assertSame('tx_contexts_nav_enable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts_nav'][1]);

        //tt_content
        self::assertArrayHasKey('enableSettings', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']);
        self::assertSame('tx_contexts', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['enableSettings']);
        self::assertArrayHasKey('flatSettings', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']);
        self::assertSame('tx_contexts_disable', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][0]);
        self::assertSame('tx_contexts_enable', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][1]);
    }
}
