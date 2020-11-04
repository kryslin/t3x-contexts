<?php

namespace Netresearch\Contexts\Service;

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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for TCEmain-hooks: Capture incoming default and record settings
 * and save them to the settings table and the enabled fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class DataHandlerService
{
    protected $currentSettings;

    /**
     * Extract the context settings from the field array and set them in
     * currentSettings. This function is called by TYPO each time a record
     * is saved in the backend.
     *
     * @param array         &$incomingFieldArray
     * @param string        $table
     * @param string        $id
     * @param DataHandler   &$reference
     */
    public function processDatamap_preProcessFieldArray(
        &$incomingFieldArray,
        $table,
        $id,
        &$reference
    ): void {
        if (!is_array($incomingFieldArray)) {
            // some strange DB situation
            return;
        }

        if ($table === 'tx_contexts_contexts'
            && isset($incomingFieldArray['default_settings'])
            && is_array($incomingFieldArray['default_settings'])
        ) {
            $this->currentSettings = $incomingFieldArray['default_settings'];
            unset($incomingFieldArray['default_settings']);
            return;
        }

        if (isset($incomingFieldArray[Configuration::RECORD_SETTINGS_COLUMN])) {
            $this->currentSettings = $incomingFieldArray[Configuration::RECORD_SETTINGS_COLUMN];
            unset($incomingFieldArray[Configuration::RECORD_SETTINGS_COLUMN]);
        }
    }

    /**
     * Finally save the settings
     *
     * @param string        $status
     * @param string        $table
     * @param string        $id
     * @param array         $fieldArray
     * @param DataHandler   $reference
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        $fieldArray,
        $reference
    ): void {
        if (is_array($this->currentSettings)) {
            if (!is_numeric($id)) {
                $id = $reference->substNEWwithIDs[$id];
            }
            if ($table === 'tx_contexts_contexts') {
                $this->saveDefaultSettings($id, $this->currentSettings);
            } else {
                $this->saveRecordSettings($table, $id, $this->currentSettings);
                $this->saveFlatSettings($table, $id, $this->currentSettings);
            }

            unset($this->currentSettings);
        }
    }

    /**
     * Save the settings for a specific record: For each context and field
     * there will be a setting record if the setting is Yes or No. If its
     * blank (n/a) eventually existing records will be deleted.
     *
     * @param string $table
     * @param int    $uid
     * @param array  $contextsAndSettings
     */
    protected function saveRecordSettings($table, $uid, $contextsAndSettings): void
    {
        $flatSettingColumns = Configuration::getFlatColumns(
            $table
        );

        foreach ($contextsAndSettings as $contextId => $settings) {
            foreach ($settings as $field => $setting) {
                if (isset($flatSettingColumns[$field])) {
                    continue;
                }
                $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
                $queryBuilder
                    ->select('uid')
                    ->from('tx_contexts_settings')
                    ->where('context_uid = :context_uid')
                    ->andWhere('foreign_table = :foreign_table')
                    ->andWhere('name = :name')
                    ->andWhere('foreign_uid = :foreign_uid')
                    ->setParameters([
                        'context_uid' => (int)$contextId,
                        'foreign_table' => $table,
                        'name' => $field,
                        'foreign_uid' => (int)$uid
                    ]);
                $row = $queryBuilder->execute()->fetchAssociative();
                if ($setting === '0' || $setting === '1') {
                    $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
                    if ($row) {
                        $queryBuilder
                            ->update('tx_contexts_settings')
                            ->set('enabled', $setting)
                            ->where('uid = :uid')
                            ->setParameter('uid', (int)$row['uid'])
                            ->execute();
                    } else {
                        $queryBuilder
                            ->insert('tx_contexts_settings')
                            ->values([
                                'context_uid' => $contextId,
                                'foreign_table' => $table,
                                'name' => $field,
                                'foreign_uid' => $uid,
                                'enabled' => $setting
                            ])
                            ->execute();
                    }
                } elseif ($row) {
                    $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
                    $queryBuilder
                        ->delete('tx_contexts_settings')
                        ->where('uid = :uid')
                        ->setParameter('uid', (int)$row['uid'])
                        ->execute();
                }
            }
        }
    }

    /**
     * Saves the settings which were configured to be flattened into theyr flat
     * columns on the table to allow quicker queries in enableField hook and to
     * save queries for already fetched rows
     * hook.
     *
     * @param string $table
     * @param int    $uid
     * @param array  $contextsAndSettings Array of settings.
     *                                    Key is the context UID.
     *                                    Value is an array of setting names
     *                                    and their value, e.g.
     *                                    tx_contexts_visibility => '',
     *                                    menu_visibility => '0'
     *                                    '' = undecided, 1 - on, 0 - off
     * @see FrontendControllerService::enableFields()
     */
    protected function saveFlatSettings($table, $uid, $contextsAndSettings): void
    {
        $values = [];

        $flatSettingColumns = Configuration::getFlatColumns($table);
        foreach ($flatSettingColumns as $setting => $flatColumns) {
            $values[$flatColumns[0]] = [];
            $values[$flatColumns[1]] = [];
            foreach ($contextsAndSettings as $contextId => $settings) {
                if ($settings[$setting] === '0' || $settings[$setting] === '1') {
                    $values[$flatColumns[$settings[$setting]]][] = $contextId;
                }
            }
        }

        if (count($values)) {
            $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
            $queryBuilder->update($table);
            foreach ($values as $colname => $val) {
                $queryBuilder->set($colname, implode(',', $val));
            }
            $queryBuilder
                ->where('uid = :uid')
                ->setParameter('uid', (int)$uid)
                ->execute();
        }
    }

    /**
     * Save the default settings to the settings table - default
     * settings will have a foreign_uid of 0
     *
     * @param int $contextId
     * @param array $settings
     */
    protected function saveDefaultSettings($contextId, $settings): void
    {
        $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('tx_contexts_settings')
            ->where('context_uid = :context_uid')
            ->where('foreign_uid = 0')
            ->setParameter('uid', (int)$contextId);

        $existingSettings = $queryBuilder->execute()->fetchAllAssociative();

        foreach ($settings as $table => $fields) {
            $fieldSettings = [];
            foreach ($existingSettings as $setting) {
                if ($setting['foreign_table'] === $table) {
                    $fieldSettings[$setting['name']] = $setting['uid'];
                }
            }
            foreach ($fields as $field => $enabled) {
                if (array_key_exists($field, $fieldSettings)) {
                    $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
                    $queryBuilder
                        ->update('tx_contexts_settings')
                        ->set('enabled', (int)$enabled)
                        ->where('uid = :uid')
                        ->setParameter('uid', (int)$fieldSettings[$field])
                        ->execute();
                } else {
                    $queryBuilder = $this->getDatabaseConnection()->createQueryBuilder();
                    $queryBuilder
                        ->insert('tx_contexts_settings')
                        ->values([
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => 0,
                            'enabled' => (int)$enabled
                        ])
                        ->execute();
                }
            }
        }
    }

    protected function getDatabaseConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
    }
}
