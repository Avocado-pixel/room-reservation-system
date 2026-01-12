<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * SEO Controller for Sitemap and Robots.txt.
 *
 * Generates dynamic SEO assets:
 * - sitemap.xml with all public routes and rooms
 * - robots.txt with proper crawling directives
 *
 * Sitemaps are cached for 1 hour to reduce database load.
 */
class SeoController extends Controller
{
    /**
     * Generate the XML sitemap.
     *
     * Includes:
     * - Static pages (home, login, register, rooms, terms, privacy)
     * - Dynamic room pages
     *
     * @return Response XML sitemap response
     */
    public function sitemap(): Response
    {
        $sitemap = Cache::remember('sitemap.xml', 3600, function () {
            return $this->generateSitemap();
        });

        return response($sitemap, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate the robots.txt file.
     *
     * Allows all crawlers with sitemap reference.
     * Blocks admin and private routes.
     *
     * @return Response Text response
     */
    public function robots(): Response
    {
        $sitemapUrl = url('/sitemap.xml');
        $appUrl = config('app.url');

        $robots = <<<ROBOTS
# SAW Room Booking System - robots.txt
# Generated dynamically for optimal SEO

User-agent: *
Allow: /
Allow: /rooms
Allow: /login
Allow: /register

# Block admin and private areas
Disallow: /admin/
Disallow: /client/
Disallow: /user/
Disallow: /api/
Disallow: /livewire/
Disallow: /_debugbar/
Disallow: /storage/
Disallow: /sanctum/

# Block authentication actions
Disallow: /logout
Disallow: /password/
Disallow: /email/
Disallow: /two-factor

# Crawl delay for polite crawling
Crawl-delay: 1

# Sitemap location
Sitemap: {$sitemapUrl}

# Host directive
Host: {$appUrl}
ROBOTS;

        return response($robots, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Generate the sitemap XML content.
     *
     * @return string XML sitemap
     */
    private function generateSitemap(): string
    {
        $urls = [];
        $now = now()->toW3cString();

        // Static pages with priorities
        $staticPages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/rooms', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/login', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/register', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/policy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => url($page['url']),
                'lastmod' => $now,
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
            ];
        }

        // Dynamic room pages
        $rooms = Room::query()
            ->where('status', 'active')
            ->where('record_status', 'active')
            ->select(['id', 'updated_at'])
            ->get();

        foreach ($rooms as $room) {
            $urls[] = [
                'loc' => url("/rooms/{$room->id}"),
                'lastmod' => $room->updated_at->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // Build XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $xml .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 ';
        $xml .= 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= "    <loc>{$url['loc']}</loc>" . PHP_EOL;
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>" . PHP_EOL;
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>" . PHP_EOL;
            $xml .= "    <priority>{$url['priority']}</priority>" . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Clear the sitemap cache.
     *
     * Call this when rooms are created/updated/deleted.
     *
     * @return void
     */
    public static function clearSitemapCache(): void
    {
        Cache::forget('sitemap.xml');
    }
}
