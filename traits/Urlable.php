<?php

namespace Winter\Blog\Traits;

use Cms\Classes\Controller;
use Cms\Classes\Page as CmsPage;
use Winter\Storm\Router\Router;
use Winter\Storm\Support\Facades\URL;
use Winter\Translate\Classes\Translator;
use Winter\Translate\Models\Locale;

/**
 * Urlable trait
 */
trait Urlable
{
    /**
     * @var string|null The URL to this record as set by setUrl()
     */
    public $url = null;

    /**
     * Set the URL for this record instance
     */
    public function setUrl(string $pageName, Controller $controller, array $extraParams = []): ?string
    {
        $cmsPage = CmsPage::loadCached($controller->getTheme(), $pageName);
        if (!$cmsPage) {
            return null;
        }

        $params = array_merge($this->getUrlParams($cmsPage), $extraParams);

        return $this->url = $controller->pageUrl($pageName, $params, false);
    }

    /**
     * Get the URL parameters for this record, optionally using the provided CMS page.
     */
    public function getUrlParams(?CmsPage $page = null): array
    {
        return $this->toArray();
    }

    /**
     * Get the URL to this record, optionally using the provided CMS page.
     */
    public function getUrl(?CmsPage $page = null): ?string
    {
        $params = $this->getUrlParams($page);

        return CmsPage::url($page->getBaseFileName(), $params);
    }

    /**
     * Get the localized URL to this record, optionally using the provided CMS page.
     */
    public function getLocalizedUrl(string $locale, ?CmsPage $page = null): ?string
    {
        $translator = Translator::instance();

        $localRecord = clone $this;
        $localRecord->translateContext($locale);

        $localeUrl = $page->getViewBagUrlAttributeTranslated($locale) ?: $page->url;

        $params = $localRecord->getUrlParams($page);
        $url = $translator->getPathInLocale($localeUrl, $locale);

        return (new Router())->urlFromPattern($url, $params);
    }

    /**
     * Get the localized URLs to this record
     */
    public function getLocalizedUrls(?CmsPage $page = null, bool $absolute = true): array
    {
        $localizedUrls = [];
        $enabledLocales = class_exists(Locale::class) ? Locale::listEnabled() : [];

        foreach ($enabledLocales as $locale => $name) {
            $url = $this->getLocalizedUrl($locale, $page);

            if ($absolute) {
                $url = Url::to($url);
            }

            $localizedUrls[$locale] = $url;
        }

        return $localizedUrls;
    }

    /**
     * Helper method to get a URL parameter name from a component property on a CMS page.
     */
    protected function getParamNameFromComponentProperty(CmsPage $page, string $componentName, string $propertyName): ?string
    {
        $properties = $page->getComponentProperties($componentName);
        if (!isset($properties[$propertyName])) {
            return null;
        }

        /*
         * Extract the routing parameter name from the category filter
         * eg: {{ :someRouteParam }}
         */
        if (!preg_match('/^\{\{([^\}]+)\}\}$/', $properties[$propertyName], $matches)) {
            return null;
        }

        $paramName = substr(trim($matches[1]), 1) ?? null;

        return $paramName;
    }
}
