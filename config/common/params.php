<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Web\Shared\Layout\Main\TopbarViewInjection;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key, true);
    if ($value !== false) {
        return $value === '' ? $default : $value;
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value === '' ? $default : $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    return $default;
};

$mediahost = [
    'baseUrl' => 'https://mediahost.ums.edu.my',
    'uploaderId' => '0302',
    'connectTimeout' => 5,
    'timeout' => 60,
];
$mediahostLocal = __DIR__ . '/mediahost.local.php';
if (is_file($mediahostLocal)) {
    $localMediahost = require $mediahostLocal;
    if (is_array($localMediahost)) {
        $mediahost = array_replace_recursive($mediahost, $localMediahost);
    }
}

$bkums = [
    'profilePhoto' => [
        'endpoint' => $env('HRONLINE_BKUMS_PROFILE_PHOTO_ENDPOINT', 'https://bkums.ums.edu.my/imagedepot/web/photo/upload'),
        'apiKey' => $env('HRONLINE_BKUMS_PROFILE_PHOTO_API_KEY'),
        'connectTimeout' => 5,
        'timeout' => 60,
        'dryRun' => false,
    ],
];
$bkumsLocal = __DIR__ . '/bkums.local.php';
if (is_file($bkumsLocal)) {
    $localBkums = require $bkumsLocal;
    if (is_array($localBkums)) {
        $bkums = array_replace_recursive($bkums, $localBkums);
    }
}

return [
    'application' => require __DIR__ . '/application.php',
    'mediahost' => $mediahost,
    'bkums' => $bkums,

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => null,
        'layout' => '@src/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
            Reference::to(TopbarViewInjection::class),
        ],
    ],
];
