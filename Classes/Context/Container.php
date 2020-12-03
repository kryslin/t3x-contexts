<?php

namespace Netresearch\Contexts\Context;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads contexts and provides access to them
 */
class Container extends \ArrayObject
{
    /**
     * @var Container
     */
    protected static $instance;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Singleton accessor
     *
     * @return Container
     */
    public static function get(): self
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getContext(): Context
    {
        return GeneralUtility::makeInstance(Context::class);
    }

    /**
     * Loads all contexts and checks if they match
     *
     * @return Container
     */
    public function initMatching(): self
    {
        $this->setActive($this->match($this->loadAvailable()));
        return $this;
    }

    /**
     * Loads all contexts.
     *
     * @return Container
     */
    public function initAll(): self
    {
        $this->setActive($this->loadAvailable());
        return $this;
    }

    /**
     * Make the given contexts active (available in this container)
     *
     * @param array $arContexts Array of context objects
     *
     * @return Container
     */
    protected function setActive($arContexts): self
    {
        $this->exchangeArray($arContexts);
        $aliases = [];
        /** @var AbstractContext $context */
        foreach ($arContexts as $context) {
            $aliases[] = $context->getAlias() ?: $context->getTitle();
        }
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $logger->info(
            count($this) . ' active contexts: ' . implode(', ', $aliases)
        );

        return $this;
    }

    /**
     * Loads all available contexts from database and instantiates them
     * and checks if they match.
     *
     * @return array Array of available Netresearch\Contexts\Context\AbstractContext objects,
     *               key is their uid
     */
    protected function loadAvailable(): array
    {
        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $qb = $connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from('tx_contexts_contexts');

        $arRows = $qb->execute()->fetchAllAssociative();

        $contexts = [];
        foreach ($arRows as $arRow) {
            $context = Factory::createFromDb($arRow);
            if ($context !== null) {
                $contexts[$arRow['uid']] = $context;
            }
        }

        return $contexts;
    }

    /**
     * Matches all context objects. Resolves dependencies.
     *
     * @param array $arContexts Array of available context objects
     *
     * @return array Array of matched Netresearch\Contexts\Context\AbstractContext objects,
     *               key is their uid
     */
    protected function match($arContexts): array
    {
        $matched          = [];
        $notMatched       = [];
        $arContextsHelper = $arContexts;

        $loops = 0;
        do {
            foreach (array_keys($arContexts) as $uid) {
                /* @var $context AbstractContext */
                $context = $arContexts[$uid];

                if ($context->getDisabled()) {
                    continue;
                }

                // resolve dependencies
                $arDeps = $context->getDependencies($arContextsHelper);
                $unresolvedDeps = count($arDeps);
                foreach ($arDeps as $depUid => $enabled) {
                    if ($enabled) {
                        if (isset($matched[$depUid])) {
                            $arDeps[$depUid] = (object)[
                                'context' => $matched[$depUid],
                                'matched' => true
                            ];
                            $unresolvedDeps--;
                        } elseif (isset($notMatched[$depUid])) {
                            $arDeps[$depUid] = (object)[
                                'context' => $notMatched[$depUid],
                                'matched' => false
                            ];
                            $unresolvedDeps--;
                        }
                    } else {
                        $arDeps[$depUid] = (object)[
                            'context' => $arContextsHelper[$depUid],
                            'matched' => 'disabled'
                        ];
                        $unresolvedDeps--;
                    }
                    // FIXME: what happens when dependency context is not
                    // available at all (e.g. deleted)?
                }
                if ($unresolvedDeps > 0) {
                    // not all dependencies available yet, so skip this
                    // one for now
                    continue;
                }

                if ($context->match($arDeps)) {
                    $matched[$uid] = $context;
                } else {
                    $notMatched[$uid] = $context;
                }
                unset($arContexts[$uid]);
            }
        } while (count($arContexts) > 0 && ++$loops < 10);

        return $matched;
    }

    /**
     * Find context by uid or alias
     *
     * @param int|string $uidOrAlias
     *
     * @return AbstractContext
     */
    public function find($uidOrAlias): ?AbstractContext
    {
        if (is_numeric($uidOrAlias) && isset($this[$uidOrAlias])) {
            return $this[$uidOrAlias];
        }

        foreach ($this as $context) {
            if ($context->getUid() === $uidOrAlias || $context->getAlias() === strtolower($uidOrAlias)) {
                return $context;
            }
        }

        return null;
    }
}
