<?php

namespace Netresearch\Contexts\ExpressionLanguage\FunctionsProvider;

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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Class ContextConditionProvider
 */
class ContextFunctionsProvider implements ExpressionFunctionProviderInterface
{

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions(): array
    {
        return [
            $this->getContextMatch()
        ];
    }

    /**
     * @return ExpressionFunction
     */
    protected function getContextMatch(): ExpressionFunction
    {
        return new ExpressionFunction(
            'contextMatch',
            static function () {
                // Not implemented, we only use the evaluator
            },
            static function ($arguments, $strContext) {
                return \Netresearch\Contexts\Api\ContextMatcher::getInstance()->matches($strContext);
            }
        );
    }
}
