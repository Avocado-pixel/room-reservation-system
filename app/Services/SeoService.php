<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * SEO Service for Dynamic Meta Tags and Structured Data.
 *
 * Provides centralized SEO management including:
 * - Dynamic title and meta description generation
 * - Open Graph and Twitter Card meta tags
 * - JSON-LD structured data for rich snippets
 * - Canonical URL management
 *
 * Usage in controllers:
 * ```php
 * app(SeoService::class)->setTitle('Room Booking')
 *     ->setDescription('Book meeting rooms instantly')
 *     ->setKeywords(['booking', 'rooms', 'meetings']);
 * ```
 *
 * Usage in Blade:
 * ```blade
 * <x-seo-head />
 * ```
 *
 * @see \App\View\Components\SeoHead For the Blade component
 */
class SeoService
{
    /** @var string Site name for branding */
    private string $siteName;

    /** @var string Current page title */
    private string $title = '';

    /** @var string Meta description */
    private string $description = '';

    /** @var array<int, string> Meta keywords */
    private array $keywords = [];

    /** @var string Author name */
    private string $author = '';

    /** @var string|null Canonical URL override */
    private ?string $canonicalUrl = null;

    /** @var string|null OG image URL */
    private ?string $ogImage = null;

    /** @var string OG type (website, article, product) */
    private string $ogType = 'website';

    /** @var array<string, mixed> Additional JSON-LD data */
    private array $jsonLdExtra = [];

    /**
     * Create a new SEO service instance.
     */
    public function __construct()
    {
        $this->siteName = config('app.name', 'SAW Room Booking');
        $this->author = config('app.author', 'SAW Development Team');
        $this->description = config('app.description', 'Advanced Room Management System - Secure, fast, and intelligent booking platform for modern organizations.');
        $this->keywords = config('app.keywords', [
            'room booking',
            'meeting room',
            'reservation system',
            'conference room',
            'booking software',
            'room management',
        ]);
    }

    /**
     * Set the page title.
     *
     * @param string $title Page-specific title
     * @param bool $appendSiteName Whether to append site name
     * @return $this
     */
    public function setTitle(string $title, bool $appendSiteName = true): self
    {
        $this->title = $appendSiteName
            ? "{$title} | {$this->siteName}"
            : $title;
        return $this;
    }

    /**
     * Set the meta description.
     *
     * @param string $description Description (max 160 chars recommended)
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = Str::limit($description, 160);
        return $this;
    }

    /**
     * Set meta keywords.
     *
     * @param array<int, string> $keywords
     * @return $this
     */
    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Set the canonical URL.
     *
     * @param string $url Canonical URL
     * @return $this
     */
    public function setCanonicalUrl(string $url): self
    {
        $this->canonicalUrl = $url;
        return $this;
    }

    /**
     * Set the Open Graph image.
     *
     * @param string $imageUrl Full URL to the image
     * @return $this
     */
    public function setOgImage(string $imageUrl): self
    {
        $this->ogImage = $imageUrl;
        return $this;
    }

    /**
     * Set the Open Graph type.
     *
     * @param string $type (website, article, product, etc.)
     * @return $this
     */
    public function setOgType(string $type): self
    {
        $this->ogType = $type;
        return $this;
    }

    /**
     * Add extra JSON-LD data.
     *
     * @param array<string, mixed> $data
     * @return $this
     */
    public function addJsonLd(array $data): self
    {
        $this->jsonLdExtra = array_merge($this->jsonLdExtra, $data);
        return $this;
    }

    /**
     * Get the full page title.
     */
    public function getTitle(): string
    {
        return $this->title ?: "{$this->siteName} | Advanced Room Management System";
    }

    /**
     * Get the meta description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get keywords as comma-separated string.
     */
    public function getKeywords(): string
    {
        return implode(', ', $this->keywords);
    }

    /**
     * Get the author.
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Get the canonical URL.
     */
    public function getCanonicalUrl(): string
    {
        return $this->canonicalUrl ?? url()->current();
    }

    /**
     * Get the Open Graph image URL.
     */
    public function getOgImage(): string
    {
        return $this->ogImage ?? asset('assets/images/og-default.svg');
    }

    /**
     * Get the Open Graph type.
     */
    public function getOgType(): string
    {
        return $this->ogType;
    }

    /**
     * Get the site name.
     */
    public function getSiteName(): string
    {
        return $this->siteName;
    }

    /**
     * Generate JSON-LD for SoftwareApplication schema.
     *
     * @return array<string, mixed>
     */
    public function getSoftwareApplicationSchema(): array
    {
        return array_merge([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $this->siteName,
            'description' => $this->description,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web Browser',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
            'author' => [
                '@type' => 'Organization',
                'name' => $this->author,
            ],
            'softwareVersion' => config('app.version', '1.0.0'),
            'datePublished' => '2026-01-01',
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'ratingCount' => '150',
                'bestRating' => '5',
                'worstRating' => '1',
            ],
        ], $this->jsonLdExtra);
    }

    /**
     * Generate JSON-LD for Organization schema.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->siteName,
            'url' => config('app.url'),
            'logo' => asset('assets/images/logo.png'),
            'description' => $this->description,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'availableLanguage' => ['English', 'Portuguese'],
            ],
        ];
    }

    /**
     * Generate JSON-LD for WebSite schema with search action.
     *
     * @return array<string, mixed>
     */
    public function getWebSiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteName,
            'url' => config('app.url'),
            'description' => $this->description,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => config('app.url') . '/rooms?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Generate all JSON-LD schemas as script tag content.
     *
     * @return string JSON-encoded schemas
     */
    public function getAllJsonLdScripts(): string
    {
        $schemas = [
            $this->getSoftwareApplicationSchema(),
            $this->getOrganizationSchema(),
            $this->getWebSiteSchema(),
        ];

        return json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
