<?php
namespace App\Controllers;

use App\Models\AppSettings;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\Widget;
use App\ContentBlocks;
use App\CommentRateLimit;
use App\DiscussionSettings;
use App\LlmsTxt;
use App\Permalink;
use App\PublicSeo;
use App\ReadingSettings;
use App\SocialShare;
use Core\Controller;

class PublicController extends Controller
{
    public function home(): void
    {
        $reading = ReadingSettings::get();
        if ($reading->show_on_front === ReadingSettings::FRONT_POSTS) {
            $this->renderBlogListing(1, true);
            return;
        }

        $page = ReadingSettings::resolveFrontPage();
        $branding = AppSettings::getBrandingConfig();
        if (!$page) {
            $seo = PublicSeo::siteContext($branding);
            $this->view('public/home', [
                'branding' => $branding,
                'publicShare' => $seo['share'],
                'publicJsonLd' => $seo['json_ld'],
                'publicJsonUrl' => $seo['json_url'],
                'publicLlmSummary' => $seo['llm_summary'],
                'publicCitationSnippet' => $seo['citation_snippet'] ?? '',
            ]);
            return;
        }
        $isHomepage = true;
        $seo = PublicSeo::pageContext($page, $branding, $isHomepage);
        $this->view('public/page', [
            'page' => $page,
            'branding' => $branding,
            'isHomepage' => $isHomepage,
            'publicNavActive' => 'home',
            'publicShare' => $seo['share'],
            'publicJsonLd' => $seo['json_ld'],
            'publicJsonUrl' => $seo['json_url'],
            'publicRobotsNoindex' => $seo['robots_noindex'],
            'publicLlmSummary' => $seo['llm_summary'],
            'publicCitationSnippet' => $seo['citation_snippet'] ?? '',
        ]);
    }

    public function homeJson(): void
    {
        $reading = ReadingSettings::get();
        if ($reading->show_on_front === ReadingSettings::FRONT_POSTS) {
            $this->outputSiteJson();
            return;
        }
        $page = ReadingSettings::resolveFrontPage();
        if (!$page) {
            $this->outputSiteJson();
            return;
        }
        $this->outputPageJson($page, true);
    }

    public function siteJson(): void
    {
        $this->outputSiteJson();
    }

    private function outputSiteJson(): void
    {
        $seo = AppSettings::getSiteSeoConfig();
        if (!$seo->enable_json_export) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'JSON export disabled'], JSON_UNESCAPED_UNICODE);
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');
        header('X-Robots-Tag: noindex');
        echo json_encode(PublicSeo::siteJsonDocument(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function page(string $slug): void
    {
        $page = Page::findBySlug($slug, true);
        if (!$page) {
            Redirect::applyForRequestPath('/p/' . $slug);
            Redirect::applyForRequestPath('/' . $slug);
            http_response_code(404);
            $this->view('public/not_found', [
                'message' => 'Page not found.',
                'branding' => AppSettings::getBrandingConfig(),
            ]);
            return;
        }
        $branding = AppSettings::getBrandingConfig();
        $front = ReadingSettings::resolveFrontPage();
        $isHomepage = $front && (int) $front->id === (int) $page->id;
        $crumbs = $isHomepage ? [] : Page::ancestors($page);
        $seo = PublicSeo::pageContext($page, $branding, $isHomepage, $crumbs);
        $this->view('public/page', [
            'page' => $page,
            'branding' => $branding,
            'isHomepage' => $isHomepage,
            'publicNavActive' => 'page-' . (int) $page->id,
            'pageBreadcrumbs' => $crumbs,
            'publicShare' => $seo['share'],
            'publicJsonLd' => $seo['json_ld'],
            'publicJsonUrl' => $seo['json_url'],
            'publicRobotsNoindex' => $seo['robots_noindex'],
            'publicLlmSummary' => $seo['llm_summary'],
            'publicCitationSnippet' => $seo['citation_snippet'] ?? '',
        ]);
    }

    public function pageJson(string $slug): void
    {
        $page = Page::findBySlug($slug, true);
        if (!$page) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Page not found'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->outputPageJson($page, Page::isHomepageSlug($page->slug));
    }

    private function outputPageJson(object $page, bool $isHomepage): void
    {
        $seo = AppSettings::getSiteSeoConfig();
        if (!$seo->enable_json_export) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'JSON export disabled'], JSON_UNESCAPED_UNICODE);
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');
        header('X-Robots-Tag: noindex');
        $branding = AppSettings::getBrandingConfig();
        echo json_encode(PublicSeo::jsonDocument($page, $branding, $isHomepage), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function blog(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderBlogListing($page, false);
    }

    public function category(string $slug): void
    {
        $category = Category::findBySlug($slug);
        if (!$category) {
            http_response_code(404);
            $this->view('public/not_found', [
                'message' => 'Category not found.',
                'branding' => AppSettings::getBrandingConfig(),
            ]);
            return;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderBlogListing($page, false, $category, null);
    }

    public function tag(string $slug): void
    {
        $tag = Tag::findBySlug($slug);
        if (!$tag) {
            http_response_code(404);
            $this->view('public/not_found', [
                'message' => 'Tag not found.',
                'branding' => AppSettings::getBrandingConfig(),
            ]);
            return;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderBlogListing($page, false, null, $tag);
    }

    private function renderBlogListing(int $pageNum, bool $isFrontPosts, ?object $category = null, ?object $tag = null): void
    {
        $reading = ReadingSettings::get();
        $perPage = $reading->posts_per_page;
        $categoryId = $category ? (int) $category->id : null;
        $tagId = $tag ? (int) $tag->id : null;
        $search = trim($_GET['q'] ?? '');
        $total = Post::publishedCount($categoryId, $tagId, $search !== '' ? $search : null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $pageNum = min($pageNum, $totalPages);
        $offset = ($pageNum - 1) * $perPage;
        $posts = Post::publishedList($perPage, $offset, $categoryId, $tagId, $search !== '' ? $search : null);
        $branding = AppSettings::getBrandingConfig();

        $listTitle = 'Blog';
        $listLead = 'Articles and updates from ' . ($branding->app_name ?? 'Simple CMS') . '.';
        $paginationBase = '/blog';
        $schemaType = 'Blog';
        if ($category) {
            $listTitle = $category->name;
            $listLead = $category->description ?: ('Posts in ' . $category->name);
            $paginationBase = '/blog/category/' . rawurlencode($category->slug);
            $schemaType = 'CollectionPage';
        } elseif ($tag) {
            $listTitle = 'Tag: ' . $tag->name;
            $listLead = 'Posts tagged “' . $tag->name . '”.';
            $paginationBase = '/blog/tag/' . rawurlencode($tag->slug);
            $schemaType = 'CollectionPage';
        } elseif ($search !== '') {
            $listTitle = 'Search: ' . $search;
            $listLead = $total === 0
                ? 'No posts matched your search.'
                : ($total === 1 ? '1 post found.' : $total . ' posts found.');
            $paginationBase = '/blog?q=' . rawurlencode($search);
            $schemaType = 'SearchResultsPage';
        }

        $archiveSeo = PublicSeo::archiveContext($listTitle, $listLead, $paginationBase, $schemaType, $branding);
        if ($search !== '') {
            $archiveSeo['robots_noindex'] = true;
            $archiveSeo['json_url'] = '';
        }

        $this->view('public/blog', [
            'posts' => $posts,
            'branding' => $branding,
            'publicShare' => $archiveSeo['share'],
            'publicJsonLd' => $archiveSeo['json_ld'],
            'publicJsonUrl' => $archiveSeo['json_url'],
            'publicRobotsNoindex' => $archiveSeo['robots_noindex'],
            'publicLlmSummary' => $archiveSeo['llm_summary'],
            'publicCitationSnippet' => $archiveSeo['citation_snippet'] ?? '',
            'blogListTitle' => $listTitle,
            'blogListLead' => $listLead,
            'blogSearchQuery' => $search,
            'blogPagination' => [
                'page' => $pageNum,
                'total_pages' => $totalPages,
                'total' => $total,
                'base' => $paginationBase,
            ],
            'isFrontPosts' => $isFrontPosts,
            'archiveCategory' => $category,
            'archiveTag' => $tag,
        ]);
    }

    public function post(string $slug): void
    {
        $post = Post::findBySlug($slug, true);
        if (!$post) {
            Redirect::applyForRequestPath('/blog/' . $slug);
            http_response_code(404);
            $this->view('public/not_found', [
                'message' => 'Post not found.',
                'branding' => AppSettings::getBrandingConfig(),
            ]);
            return;
        }
        $branding = AppSettings::getBrandingConfig();
        $postTags = Tag::forPost((int) $post->id);
        $comments = DiscussionSettings::get()->comments_enabled
            ? Comment::forPost((int) $post->id, true)
            : [];
        $commentMessage = $_SESSION['comment_message'] ?? '';
        $commentError = $_SESSION['comment_error'] ?? '';
        unset($_SESSION['comment_message'], $_SESSION['comment_error']);
        $seo = PublicSeo::postContext($post, $branding, $postTags);
        $this->view('public/post', [
            'post' => $post,
            'postTags' => $postTags,
            'comments' => $comments,
            'commentMessage' => $commentMessage,
            'commentError' => $commentError,
            'discussion' => DiscussionSettings::get(),
            'branding' => $branding,
            'publicShare' => $seo['share'],
            'publicJsonLd' => $seo['json_ld'],
            'publicJsonUrl' => $seo['json_url'],
            'publicRobotsNoindex' => $seo['robots_noindex'],
            'publicLlmSummary' => $seo['llm_summary'],
            'publicCitationSnippet' => $seo['citation_snippet'] ?? '',
        ]);
    }

    public function postJson(string $slug): void
    {
        $post = Post::findBySlug($slug, true);
        if (!$post) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Post not found'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $seo = AppSettings::getSiteSeoConfig();
        if (!$seo->enable_json_export) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'JSON export disabled'], JSON_UNESCAPED_UNICODE);
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');
        header('X-Robots-Tag: noindex');
        $branding = AppSettings::getBrandingConfig();
        echo json_encode(PublicSeo::postJsonDocument($post, $branding), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function commentStore(int $postId): void
    {
        $post = Post::findPublished($postId);
        if (!$post) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $this->validateCsrf();
        $id = Comment::createPublic($postId, $_POST);
        if ($id) {
            $_SESSION['comment_message'] = DiscussionSettings::get()->moderation
                ? 'Your comment is awaiting moderation.'
                : 'Thank you for your comment.';
        } else {
            $ip = CommentRateLimit::clientIp();
            $limit = DiscussionSettings::get()->comment_rate_limit_per_hour;
            if ($limit > 0 && CommentRateLimit::isLimited($ip, $limit)) {
                $_SESSION['comment_error'] = 'Too many comments from your address. Please try again later.';
            } else {
                $_SESSION['comment_error'] = 'Could not submit comment. Check your input.';
            }
        }
        $this->redirect(Permalink::urlForPost($post) . '#comments');
    }

    public function permalinkResolve(string $s1, string $s2 = '', string $s3 = ''): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $uri = is_string($uri) ? $uri : '';
        $wantJson = str_ends_with(strtolower($uri), '.json');
        if ($wantJson) {
            $uri = substr($uri, 0, -5);
        }
        $resolved = Permalink::resolvePath((string) $uri);
        if (!$resolved) {
            Redirect::applyForRequestPath($uri);
            http_response_code(404);
            if ($wantJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Not found'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $this->view('public/not_found', [
                'message' => 'Page not found.',
                'branding' => AppSettings::getBrandingConfig(),
            ]);
            return;
        }
        if ($resolved['type'] === 'page') {
            if ($wantJson) {
                $this->pageJson($resolved['slug']);
                return;
            }
            $this->page($resolved['slug']);
            return;
        }
        if ($resolved['type'] === 'post') {
            if ($wantJson) {
                $this->postJson($resolved['slug']);
                return;
            }
            $this->post($resolved['slug']);
        }
    }

    public function sitemap(): void
    {
        $siteSeo = AppSettings::getSiteSeoConfig();
        if (!$siteSeo->enable_sitemap) {
            http_response_code(404);
            echo 'Sitemap disabled';
            return;
        }
        $base = SocialShare::baseUrl();
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $home = ReadingSettings::resolveFrontPage();
        if ($home && empty($home->robots_noindex)) {
            echo $this->sitemapUrl($base . '/', $home->updated_at ?? null, 'daily', '1.0');
        } elseif (!$home) {
            echo $this->sitemapUrl($base . '/', null, 'daily', '1.0');
        }

        foreach (Page::publishedForSitemap() as $row) {
            if ($home && (int) $row->id === (int) $home->id) {
                continue;
            }
            echo $this->sitemapUrl($base . Permalink::urlForPage($row), $row->updated_at ?? null, 'weekly', '0.8');
        }

        echo $this->sitemapUrl($base . '/blog', null, 'daily', '0.7');
        foreach (Post::publishedForSitemap() as $row) {
            $lastmod = $row->updated_at ?? $row->published_at ?? null;
            echo $this->sitemapUrl($base . Permalink::urlForPost($row), $lastmod, 'weekly', '0.6');
        }
        foreach (Category::publishedForSitemap() as $row) {
            echo $this->sitemapUrl($base . '/blog/category/' . rawurlencode((string) $row->slug), null, 'weekly', '0.5');
        }
        foreach (Tag::publishedForSitemap() as $row) {
            echo $this->sitemapUrl($base . '/blog/tag/' . rawurlencode((string) $row->slug), null, 'weekly', '0.4');
        }

        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=600');
        echo LlmsTxt::robotsTxt();
    }

    public function llmsTxt(): void
    {
        $this->outputLlms(false);
    }

    public function llmsFull(): void
    {
        $this->outputLlms(true);
    }

    private function outputLlms(bool $full): void
    {
        if (!LlmsTxt::isEnabled()) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'llms.txt disabled';
            return;
        }
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=300');
        echo $full ? LlmsTxt::fullDocument() : LlmsTxt::indexDocument();
    }

    public function blogJson(): void
    {
        $seo = AppSettings::getSiteSeoConfig();
        if (!$seo->enable_json_export) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'JSON export disabled'], JSON_UNESCAPED_UNICODE);
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');
        header('X-Robots-Tag: noindex');
        echo json_encode(PublicSeo::blogJsonDocument(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function feed(): void
    {
        $siteSeo = AppSettings::getSiteSeoConfig();
        if (!$siteSeo->enable_rss_feed) {
            http_response_code(404);
            echo 'Feed disabled';
            return;
        }
        $branding = AppSettings::getBrandingConfig();
        $base = SocialShare::baseUrl();
        $appName = trim((string) ($branding->app_name ?? 'Simple CMS'));
        $feedUrl = $base . '/feed.xml';
        $channelDesc = SocialShare::siteDescription($branding);
        $reading = ReadingSettings::get();
        $limit = max(1, min(50, (int) ($reading->posts_per_page ?? 10) * 2));

        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Cache-Control: public, max-age=600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo '<channel>' . "\n";
        echo '<title>' . htmlspecialchars($appName . ' — Blog', ENT_XML1) . '</title>' . "\n";
        echo '<link>' . htmlspecialchars($base . '/blog', ENT_XML1) . '</link>' . "\n";
        echo '<description>' . htmlspecialchars($channelDesc, ENT_XML1) . '</description>' . "\n";
        echo '<language>' . htmlspecialchars(PublicSeo::localeLanguage($siteSeo->locale ?? 'en_US'), ENT_XML1) . '</language>' . "\n";
        echo '<atom:link href="' . htmlspecialchars($feedUrl, ENT_XML1) . '" rel="self" type="application/rss+xml"/>' . "\n";

        foreach (Post::publishedForFeed($limit) as $post) {
            $link = $base . Permalink::urlForPost($post);
            $title = htmlspecialchars((string) ($post->title ?? ''), ENT_XML1);
            $desc = SocialShare::descriptionFromText(
                (string) ($post->excerpt ?? ''),
                ContentBlocks::plainTextFromEntity($post)
            );
            $desc = htmlspecialchars($desc, ENT_XML1);
            $pubDate = !empty($post->published_at)
                ? gmdate('D, d M Y H:i:s', strtotime((string) $post->published_at)) . ' GMT'
                : '';
            echo '<item>' . "\n";
            echo '<title>' . $title . '</title>' . "\n";
            echo '<link>' . htmlspecialchars($link, ENT_XML1) . '</link>' . "\n";
            echo '<guid isPermaLink="true">' . htmlspecialchars($link, ENT_XML1) . '</guid>' . "\n";
            if ($pubDate !== '') {
                echo '<pubDate>' . $pubDate . '</pubDate>' . "\n";
            }
            if ($desc !== '') {
                echo '<description>' . $desc . '</description>' . "\n";
            }
            if (!empty($post->author_name)) {
                echo '<author>' . htmlspecialchars((string) $post->author_name, ENT_XML1) . '</author>' . "\n";
            }
            echo '</item>' . "\n";
        }

        echo '</channel>' . "\n";
        echo '</rss>';
    }

    private function sitemapUrl(string $loc, ?string $lastmod, string $changefreq = 'weekly', string $priority = '0.5'): string
    {
        $xml = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>';
        if ($lastmod) {
            $xml .= '<lastmod>' . htmlspecialchars(substr($lastmod, 0, 10), ENT_XML1) . '</lastmod>';
        }
        $xml .= '<changefreq>' . htmlspecialchars($changefreq, ENT_XML1) . '</changefreq>';
        $xml .= '<priority>' . htmlspecialchars($priority, ENT_XML1) . '</priority>';
        $xml .= '</url>' . "\n";
        return $xml;
    }
}
