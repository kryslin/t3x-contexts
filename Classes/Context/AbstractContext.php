<?php

namespace Netresearch\Contexts\Context;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use Netresearch\Contexts\Api\Configuration;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Abstract context - must be extended by the context types
 *
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
abstract class AbstractContext
{
    /**
     * Key for the ip forward header
     *
     * @var string
     */
    public const HTTP_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';

    /**
     * Key for the ip remote address
     *
     * @var string
     */
    public const REMOTE_ADDR = 'REMOTE_ADDR';

    /**
     * Uid of context.
     *
     * @var int
     */
    protected $uid;

    /**
     * Type of context.
     *
     * @var string
     */
    protected $type;

    /**
     * Title of context.
     *
     * @var string
     */
    protected $title;

    /**
     * Alias of context.
     *
     * @var string
     */
    protected $alias;

    /**
     * Unix timestamp of last record modification.
     *
     * @var int
     */
    protected $tstamp;

    /**
     * Invert the match result.
     *
     * @var bool
     */
    protected $invert = false;

    /**
     * Store match result in user session.
     *
     * @var bool
     */
    protected $use_session = true;

    /**
     * Context configuration.
     *
     * @var array
     */
    protected $conf;

    /**
     * List of all context settings.
     *
     * @var array
     */
    private $settings = [];

    /**
     * Constructor - set the values from database row.
     * @var bool
     */
    protected $disabled;

    /**
     * Hide Context in backend
     *
     * @var bool
     */
    protected $bHideInBackend = false;

    /**
     * Constructor - set the values from database row.
     *
     * @param array $arRow Database context row
     */
    public function __construct($arRow = [])
    {
        //check TSFE is set
        //prevent Exceptions in eID
        $this->initTsfe();

        if (!empty($arRow)) {
            $this->uid            = (int)$arRow['uid'];
            $this->type           = $arRow['type'];
            $this->title          = $arRow['title'];
            $this->alias          = $arRow['alias'];
            $this->tstamp         = $arRow['tstamp'];
            $this->invert         = $arRow['invert'];
            $this->use_session    = $arRow['use_session'];
            $this->disabled       = $arRow['disabled'];
            $this->bHideInBackend = (bool)$arRow['hide_in_backend'];

            if ($arRow['type_conf'] !== '') {
                $this->conf = GeneralUtility::xml2array($arRow['type_conf']);
            }
        }
    }

    /**
     * Get a configuration value.
     *
     * @param string $fieldName Name of the field
     * @param string|null $default   The value to use when none was found
     * @param string $sheet     Sheet pointer, eg. "sDEF
     * @param string $lang      Language pointer, eg. "lDEF
     * @param string $value     Value pointer, eg. "vDEF
     *
     * @return string The content
     */
    protected function getConfValue(
        $fieldName,
        $default = null,
        $sheet   = 'sDEF',
        $lang    = 'lDEF',
        $value   = 'vDEF'
    ): ?string {
        if (!isset($this->conf['data'][$sheet][$lang])) {
            return $default;
        }

        $ldata = $this->conf['data'][$sheet][$lang];

        if (!isset($ldata[$fieldName][$value])) {
            return $default;
        }

        return $ldata[$fieldName][$value];
    }

    /**
     * Query a setting record and retrieve the value object
     * if one was found.
     *
     * @param string $table   Database table name
     * @param string $setting Setting name
     * @param string $uid     Record UID
     * @param array|null  $arRow   Database row for the given UID.
     *                        Useful for flat settings.
     *
     * @return Setting|null NULL when not enabled
     *                                          and not disabled
     */
    final public function getSetting($table, $setting, $uid, $arRow = null): ?Setting
    {
        if ($arRow !== null) {
            //if it's a flat column, use the settings directly from the
            // database row instead of relying on the tx_contexts_settings
            // table
            $arFlatColumns = Configuration::getFlatColumns(
                $table,
                $setting
            );
            if (isset($arRow[$arFlatColumns[0]], $arRow[$arFlatColumns[1]])
            ) {
                return Setting::fromFlatData(
                    $this,
                    $table,
                    $setting,
                    $arFlatColumns,
                    $arRow
                );
            }
        }

        $settings = $this->getSettings($table, $uid);

        return $settings[$setting] ?? null;
    }

    /**
     * Get all settings of one record.
     *
     * @param string $table Database table
     * @param int    $uid   Record UID
     *
     * @return array Array of settings
     *               Key is the context column name (e.g. "tx_contexts_nav")
     *               Value is a Netresearch\Contexts\Context\Setting object
     */
    final public function getSettings($table, $uid): array
    {
        $settingsKey = $table . '.' . $uid;

        if (array_key_exists($settingsKey, $this->settings)) {
            return $this->settings[$settingsKey];
        }

        $uids = [$uid];
        if ($uid && !array_key_exists($table . '.0', $this->settings)) {
            $uids[] = 0;
        }

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $qb = $connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from('tx_contexts_settings')
            ->where('context_uid = :context_uid')
            ->andWhere('foreign_table = :foreign_table')
            ->andWhere('foreign_uid IN (:foreign_uid)')
            ->setParameters([
                'context_uid' => $this->uid,
                'foreign_table' => $table,
                'foreign_uid' => implode("','", $uids)
            ]);

        $rows = $qb->execute()->fetchAllAssociative();

        foreach ($uids as $id) {
            $this->settings[$table . '.' . $id] = [];
        }

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->settings[$table . '.' . $row['foreign_uid']][$row['name']]
                    = new Setting($this, $row);
            }
        }

        return $this->settings[$settingsKey];
    }

    /**
     * Determines whether a setting exists for this record.
     *
     * @param string $table   Database table
     * @param string $setting Setting name
     * @param int    $uid     Record UID
     *
     * @return bool
     */
    final public function hasSetting($table, $setting, $uid): bool
    {
        return $this->getSetting($table, $setting, $uid) ? true : false;
    }

    /**
     * This function gets called when the current contexts are determined.
     *
     * @param array $arDependencies Array of context objects that are
     *                              dependencies of this context
     *
     * @return bool True when your context matches, false if not
     */
    abstract public function match(array $arDependencies = []): bool;

    /**
     * Get the uid of this context.
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Get the type of this context.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the title of this context.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Get the alias of this context.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return strtolower($this->alias);
    }

    /**
     * Return all context UIDs this context depends on.
     *
     * @param array $arContexts the available contexts
     *
     * @return array Array of context uids this context depends on.
     *               Key is the UID, value is "true"
     */
    public function getDependencies($arContexts): array
    {
        return [];
    }

    /**
     * Get the disabled status of this context
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Get hide in backend
     *
     * @return bool true if the context not shown in backend
     */
    public function getHideInBackend(): bool
    {
        return $this->bHideInBackend;
    }

    /**
     * Loads match() result from session if the context is configured so.
     *
     * @return array Array with two values:
     *               0: true: Use the second value as return,
     *                  false: calculate it
     *               1: Return value when 0 is true
     */
    protected function getMatchFromSession(): array
    {
        $bUseSession = (bool)$this->use_session;

        if (!$bUseSession) {
            return [false, null];
        }

        $res = $this->getSession();

        if ($res === null) {
            //not set yet
            return [false, null];
        }
        return [true, (bool)$res];
    }

    /**
     * Get the contextsession.
     *
     * @return mixed boolean match or null
     */
    protected function getSession()
    {
        return $GLOBALS['TSFE']->fe_user->getKey(
            'ses',
            'contexts-' . $this->uid . '-' . $this->tstamp
        );
    }

    /**
     * Stores the current match setting in the session if the type
     * is configured that way.
     *
     * @param bool $bMatch If the context matches
     *
     * @return bool $bMatch value
     */
    protected function storeInSession($bMatch): bool
    {
        if (!((bool)$this->use_session)) {
            return $bMatch;
        }

        $GLOBALS['TSFE']->fe_user->setKey(
            'ses',
            'contexts-' . $this->uid . '-' . $this->tstamp,
            $bMatch
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();
        return $bMatch;
    }

    /**
     * Init TSFE with FE user
     */
    protected function initTsfe(): void
    {
        if (!isset($GLOBALS['TSFE']) && TYPO3_MODE === 'FE') {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                Container::get()->getRequest()->getAttribute('site'),
                Container::get()->getRequest()->getAttribute('language')
            );
            $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
            Container::get()->getContext()->setAspect(
                'frontend.user',
                GeneralUtility::makeInstance(UserAspect::class, $feUser, [])
            );
            $GLOBALS['TSFE']->fe_user = $feUser;
        }
    }

    /**
     * Inverts the current match setting if inverting is activated.
     *
     * @param bool $bMatch If the context matches
     *
     * @return bool
     */
    protected function invert($bMatch): bool
    {
        if ((bool)$this->invert) {
            return !$bMatch;
        }

        return $bMatch;
    }

    /**
     * Set invert flag.
     *
     * @param bool $bInvert True or false
     */
    public function setInvert($bInvert): bool
    {
        $this->invert = (bool)$bInvert;
    }

    /**
     * Set use session flag.
     *
     * @param bool $bUseSession True or false
     */
    public function setUseSession($bUseSession): void
    {
        $this->use_session = (bool)$bUseSession;
    }

    /**
     * Returns the value for the passed key
     *
     * @param string $strKey the key, e.g. REMOTE_ADDR
     *
     * @return string
     */
    protected function getIndpEnv($strKey): string
    {
        return GeneralUtility::getIndpEnv(
            $strKey
        );
    }

    /**
     * Returns the clients remote address.
     *
     * @return string
     */
    protected function getRemoteAddress(): string
    {
        return $this->getIndpEnv(
            self::REMOTE_ADDR
        );
    }
}
