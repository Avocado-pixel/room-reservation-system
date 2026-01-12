<?php

namespace App\View\Components;

use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * SEO Head Component.
 *
 * Renders all SEO-related meta tags in the document head:
 * - Title tag
 * - Meta description, keywords, author
 * - Canonical URL
 * - Open Graph tags (Facebook/LinkedIn)
 * - Twitter Card tags
 * - JSON-LD structured data
 *
 * Usage:
 * ```blade
 * <head>
 *     <x-seo-head />
 *     <!-- other head elements -->
 * </head>
 * ```
 */
class SeoHead extends Component
{
    public string $title;
    public string $description;
    public string $keywords;
    public string $author;
    public string $canonicalUrl;
    public string $ogImage;
    public string $ogType;
    public string $siteName;
    public string $jsonLd;
    public string $locale;
    public string $twitterCard;

    /**
     * Create a new component instance.
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $keywords = null,
        ?string $ogImage = null,
        string $ogType = 'website',
        string $twitterCard = 'summary_large_image',
    ) {
        $seo = app(SeoService::class);

        // Allow override via component props
        if ($title) {
            $seo->setTitle($title);
        }
        if ($description) {
            $seo->setDescription($description);
        }
        if ($keywords) {
            $seo->setKeywords(explode(',', $keywords));
        }
        if ($ogImage) {
            $seo->setOgImage($ogImage);
        }
        $seo->setOgType($ogType);

        $this->title = $seo->getTitle();
        $this->description = $seo->getDescription();
        $this->keywords = $seo->getKeywords();
        $this->author = $seo->getAuthor();
        $this->canonicalUrl = $seo->getCanonicalUrl();
        $this->ogImage = $seo->getOgImage();
        $this->ogType = $seo->getOgType();
        $this->siteName = $seo->getSiteName();
        $this->jsonLd = $seo->getAllJsonLdScripts();
        $this->locale = str_replace('_', '-', app()->getLocale());
        $this->twitterCard = $twitterCard;
    }

    /**
     * Get the view that represents the component.
     */
    public function render(): View
    {
        return view('components.seo-head');
    }
}
