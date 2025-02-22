<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\ThemeBundle\Translation\Provider\Resource;

use Sylius\Bundle\ThemeBundle\HierarchyProvider\ThemeHierarchyProviderInterface;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Sylius\Bundle\ThemeBundle\Translation\Finder\TranslationFilesFinderInterface;
use Sylius\Bundle\ThemeBundle\Translation\Resource\ThemeTranslationResource;
use Sylius\Bundle\ThemeBundle\Translation\Resource\TranslationResourceInterface;

final class ThemeTranslatorResourceProvider implements TranslatorResourceProviderInterface
{
    private TranslationFilesFinderInterface $translationFilesFinder;

    private ThemeRepositoryInterface $themeRepository;

    private ThemeHierarchyProviderInterface $themeHierarchyProvider;

    public function __construct(
        TranslationFilesFinderInterface $translationFilesFinder,
        ThemeRepositoryInterface $themeRepository,
        ThemeHierarchyProviderInterface $themeHierarchyProvider
    ) {
        $this->translationFilesFinder = $translationFilesFinder;
        $this->themeRepository = $themeRepository;
        $this->themeHierarchyProvider = $themeHierarchyProvider;
    }

    public function getResources(): array
    {
        /** @var ThemeInterface[] $themes */
        $themes = $this->themeRepository->findAll();

        $resources = [];
        foreach ($themes as $theme) {
            $resources = array_merge($resources, $this->extractResourcesFromTheme($theme));
        }

        return $resources;
    }

    public function getResourcesLocales(): array
    {
        return array_values(array_unique(array_map(static function (TranslationResourceInterface $translationResource): string {
            return $translationResource->getLocale();
        }, $this->getResources())));
    }

    private function extractResourcesFromTheme(ThemeInterface $mainTheme): array
    {
        /** @var ThemeInterface[] $themes */
        $themes = array_reverse($this->themeHierarchyProvider->getThemeHierarchy($mainTheme));

        $resources = [];
        foreach ($themes as $theme) {
            $paths = $this->translationFilesFinder->findTranslationFiles($theme->getPath());

            foreach ($paths as $path) {
                $resources[] = new ThemeTranslationResource($mainTheme, $path);
            }
        }

        return $resources;
    }
}
