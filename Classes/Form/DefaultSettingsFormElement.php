<?php

namespace Netresearch\Contexts\Form;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

/**
 * USER functions to render the defaults fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class DefaultSettingsFormElement extends AbstractFormElement
{
    /**
     * Render a checkbox for the default settings of records in
     * this table
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        $table = $this->data['tableName'];

        $content = '';

        $namePre = str_replace('[default_settings_', '[default_settings][', $this->data['parameterArray']['itemFormElName']);

        /* @var $context AbstractContext */
        $uid = (int)$this->data['databaseRow']['uid'];
        $context = $uid
            ? Container::get()->initAll()->find($uid)
            : null;

        foreach ($this->data['parameterArray']['fieldConf']['config']['settings'] as $setting => $config) {
            $id = $this->data['parameterArray']['itemFormElID'] . '-' . $setting;
            $name = $namePre . '[' . $setting . ']';
            $content .= '<input type="hidden" name="' . $name . '" value="0"/>';
            $content .= '<input class="checkbox" type="checkbox" name="' . $name . '" ';
            if (
                !$context ||
                !$context->hasSetting($table, $setting, 0) ||
                $context->getSetting($table, $setting, 0)->getEnabled()
            ) {
                $content .= 'checked="checked" ';
            }
            $content .= 'value="1" id="' . $id . '" /> ';
            $content .= '<label for="' . $id . '">';
            $content .= $GLOBALS['LANG']->sL($config['label']);
            $content .= '</label><br/>';
        }

        $result['html'] = $content;

        return $result;
    }
}
