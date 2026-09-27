<?php

namespace App\Serializer;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Turns the local image paths stored on entities ("/images/animes/1_cover.jpg")
 * into absolute URLs the Nuxt app can use directly from another origin.
 */
final readonly class ImageUrlGenerator
{
    public function __construct(
        private CacheManager $cacheManager,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Returns the cached Liip variant if it exists, otherwise the resolve URL
     * that generates it on first hit (same behaviour as Twig's imagine_filter).
     *
     * @return array{default: string, webp: string}|null
     */
    public function filtered(?string $path, string $filter): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        return [
            'default' => $this->cacheManager->getBrowserPath($path, $filter),
            'webp' => $this->cacheManager->getBrowserPath($path, $filter . '_webp'),
        ];
    }

    /**
     * Absolute URL for an unfiltered local path; remote URLs pass through.
     */
    public function absolute(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $request = $this->requestStack->getMainRequest();

        return ($request?->getSchemeAndHttpHost() ?? '') . $request?->getBasePath() . '/' . ltrim($path, '/');
    }
}
