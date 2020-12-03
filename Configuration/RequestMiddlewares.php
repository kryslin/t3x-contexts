<?php
return [
    'frontend' => [
        'netresearch/context/container-initialization' => [
            'target' => \Netresearch\Contexts\Middleware\ContainerInitialization::class,
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ]
        ]
    ]
];
