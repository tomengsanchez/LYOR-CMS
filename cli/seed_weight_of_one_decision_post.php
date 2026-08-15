<?php
/**
 * One-off: create "The Weight of One Decision" sermon post with free image + SEO/LLM.
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\MediaImageSizes;
use Core\Auth;
use Core\Database;

$db = Database::getInstance();

$admin = $db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_OBJ);
if (!$admin) {
    fwrite(STDERR, "No users found.\n");
    exit(1);
}
Auth::login((int) $admin->id);

$existing = $db->prepare("SELECT id, slug FROM cms_posts WHERE deleted_at IS NULL AND slug = ? LIMIT 1");
$existing->execute(['the-weight-of-one-decision']);
$found = $existing->fetch(PDO::FETCH_OBJ);
if ($found) {
    echo "Post already exists id={$found->id} slug={$found->slug}\n";
    exit(0);
}

$cat = Category::findBySlug('general') ?? Category::findBySlug('sermons');
if (!$cat) {
    $cats = Category::all();
    $cat = $cats[0] ?? null;
}
if (!$cat) {
    $catId = Category::create([
        'name' => 'Sermons',
        'description' => 'Messages and preachings',
    ]);
    $cat = Category::find($catId);
}
echo "Category: {$cat->name} (#{$cat->id})\n";

// Free image: Wikimedia Commons — open Bible (public domain / free reuse).
// File: "Open bible" on Commons; JPEG suitable for featured/OG (1200-ish).
$imageUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/01/Bible.malmesbury.arp.jpg/1280px-Bible.malmesbury.arp.jpg';
$altText = 'Open Bible on a wooden surface — free Wikimedia Commons image (Bible.malmesbury.arp.jpg)';
$tmp = sys_get_temp_dir() . '/cms-weight-decision-' . bin2hex(random_bytes(4)) . '.jpg';

echo "Downloading free image…\n";
$ctx = stream_context_create([
    'http' => [
        'timeout' => 60,
        'header' => "User-Agent: SimpleCMS/1.0 (local content seed)\r\n",
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);
$bytes = @file_get_contents($imageUrl, false, $ctx);
if ($bytes === false || strlen($bytes) < 1000) {
    // Fallback: Unsplash License free photo — fork / path metaphor (decision).
    $imageUrl = 'https://images.unsplash.com/photo-1470116945706-e6bf5d5a53ca?auto=format&fit=crop&w=1200&h=630&q=80';
    $altText = 'Forest path stretching ahead — free Unsplash photo (decision / journey metaphor)';
    $bytes = @file_get_contents($imageUrl, false, $ctx);
}
if ($bytes === false || strlen($bytes) < 1000) {
    fwrite(STDERR, "Failed to download image.\n");
    exit(1);
}
file_put_contents($tmp, $bytes);
echo "Saved temp image (" . strlen($bytes) . " bytes) from {$imageUrl}\n";

$dir = dirname(__DIR__) . '/public/uploads/media';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$filename = bin2hex(random_bytes(16)) . '.jpg';
$dest = $dir . '/' . $filename;
if (!copy($tmp, $dest)) {
    fwrite(STDERR, "Failed to copy image to media dir.\n");
    @unlink($tmp);
    exit(1);
}
@unlink($tmp);

$mime = mime_content_type($dest) ?: 'image/jpeg';
$size = @getimagesize($dest);
$width = is_array($size) ? (int) ($size[0] ?? 0) : null;
$height = is_array($size) ? (int) ($size[1] ?? 0) : null;
$fileSize = (int) filesize($dest);

$stmt = $db->prepare('
    INSERT INTO cms_media (filename, original_name, file_path, mime_type, file_size, width, height, alt_text, uploaded_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
');
$stmt->execute([
    $filename,
    'the-weight-of-one-decision.jpg',
    'media/' . $filename,
    $mime,
    $fileSize,
    $width ?: null,
    $height ?: null,
    $altText,
    Auth::id(),
]);
$mediaId = (int) $db->lastInsertId();
if ($mediaId > 0 && MediaImageSizes::canResizeMime($mime)) {
    MediaImageSizes::generateForMedia($mediaId);
}
echo "Media id={$mediaId}\n";

$body = <<<'HTML'
<p><em>Text: Romans 5:12, 19 (NIV)</em></p>

<h2>Introduction: Ang Bigat ng Isang Desisyon</h2>
<p>Brothers, gusto kong mag-share ng ilang facts that might change how you see your daily choices.</p>
<p>Alam niyo ba? Most of us think that the big decisions are the ones that matter. Yung mga malalaking choices—kung sino ang asawahin mo, kung anong trabaho ang kukunin mo, kung saan ka maninirahan. Those are important, no question about it. But don’t you know that sometimes hindi naman yung malalaking decision lang ang may bigat—pati rin ang maliliit.</p>

<h2>Fact #1: One Small Decision Can Lead to Destruction</h2>
<p>According to research from Duke University, more than 55% of deaths among people aged 15 to 64 are caused by personal decisions that had alternatives.</p>
<p>Isipin natin ’yan:</p>
<ul>
<li>Choosing to smoke even though you know it’s bad for your health.</li>
<li>Choosing to drink even though you know your family depends on you.</li>
<li>Choosing to drive drunk even though you know you have children at home.</li>
</ul>
<p>Hindi lang ’yan statistics. ’Yan ay mga buhay. Mga ama. Mga asawa. Mga anak.</p>
<p>Kaya kapag may nagsasabi na “konti lang ’yan” or “isang beses lang,” remember: most bad endings started with small choices.</p>

<h2>Fact #2: Small Decisions Become Habits. Habits Become Character. Character Becomes Destiny.</h2>
<p>According to psychology, the small decisions we make every day—if repeated—become habits.</p>
<p>Halimbawa:</p>
<ul>
<li>One day of skipping exercise = no problem. But 365 days of skipping = 20 pounds heavier, may diabetes, may high blood pressure.</li>
<li>One time of lying to your boss = “walang mangyayari.” But repeated lying = you become known as unreliable, opportunities disappear.</li>
<li>One time of cheating on your wife = “accident lang.” But repeated cheating = broken family, children who lost trust, ruined reputation.</li>
</ul>
<p>As experts say: “One day of drug use does not mean addiction. But as the days accumulate, the characteristics of addiction emerge.”</p>
<p>Kaya nga sinasabi: “We don’t choose our destiny in one big moment. We choose it in small moments, every single day.”</p>
<p>Ang totoo: Your future self is being created by the choices you make today.</p>

<h2>Fact #3: One Decision Can Change Your Whole Life</h2>
<p>There was a study that asked hundreds of people about their biggest life decisions. Ano ang natuklasan nila? Decisions that had effects for years or decades often started from one simple choice.</p>
<p>Ang pagpili kung sino ang kakasama mo (asawa, kaibigan, business partner) ay nagdidikta ng kung saan ka pupunta sa buhay. The choice to not give up during a difficult time opened doors to blessings you never expected.</p>
<p>Another study showed: people who chose to change (quit a toxic job, ended a toxic relationship) were happier and more satisfied after 6 months compared to those who stayed in the status quo.</p>
<p>Ano ang lesson? Huwag matakot pumili ng tama, kahit mahirap.</p>
<p>Think about it:</p>
<ul>
<li>Yung lalaking pumili na mag-aral kahit mahirap = years later, may degree, may trabaho, may pamilyang pinakakain.</li>
<li>Yung lalaking pumili na maging tapat sa trabaho kahit may chance na hindi mahuli = years later, may pangalan, may trust ang iba, may opportunities.</li>
<li>Yung lalaking pumili na magpatawad kahit nasaktan = years later, may peace, may healing, may family na buo.</li>
</ul>
<p>Pero ang reverse din ay totoo:</p>
<ul>
<li>Yung lalaking pumili na manloko kahit maliit lang = years later, nawalan ng trust ang lahat.</li>
<li>Yung lalaking pumili na magrebelde kahit “isang beses lang” = years later, naligaw ng landas, nasira ang pamilya.</li>
</ul>

<h2>3 Practical Ways to Make Better Decisions as a Man</h2>

<h3>1. Think Before You Decide</h3>
<p>Huwag puro “gusto ko kasi” or “bahala na.” Take a moment and think about the situation before acting.</p>
<p>Ask yourself: “Tama ba talaga ito, or gusto ko lang dahil convenient?”</p>
<p>A mature man doesn’t react immediately. He thinks first, then acts.</p>

<h3>2. Think About the Consequences</h3>
<p>Huwag lang isipin kung ano ang mararamdaman mo ngayon. Isipin mo kung ano ang magiging epekto bukas, next month, or next year.</p>
<p>Ask: “Kung paulit-ulit kong gagawin ito, saan ako dadalhin?”</p>
<p>One small decision can become a habit, and a habit can eventually affect your career, relationships, money, and future.</p>

<h3>3. Choose What Is Right, Not What Is Easy</h3>
<p>Minsan alam naman natin kung ano ang tama—but we choose the easy option because it’s more common.</p>
<p>Being a man means having the courage to say: “Mahirap, pero ito ang tamang gawin.”</p>
<p>Choosing honesty over cheating, discipline over laziness, responsibility over excuses, and long-term growth over temporary pleasure.</p>
<p>A strong man doesn’t always choose what’s easy. He chooses what will make him or people around him better.</p>

<h2>Closing: The Weight of One Decision</h2>
<p>Men, sabi sa Romans 5:19b:</p>
<blockquote><p>“Through the obedience of the one man the many will be made righteous.”</p></blockquote>
<p>One man’s decision brought destruction. One man’s obedience brought hope.</p>
<p>Kaya huwag mong isipin na maliit lang ang decision mo. Every choice is shaping the man you are becoming—and affecting the people around you. I hope one day when you look back, there will be one decision in your life na sasabihin mo “buti na lang nag-decide ako.”</p>
<p>So ask yourself: “What kind of man will I become if I keep making this choice?”</p>
<p><strong>Choose wisely. Because one decision can change your direction—and sometimes, your generation.</strong></p>
HTML;

$excerpt = 'Preaching from Romans 5:12, 19 — one small decision can shape habits, character, destiny, and even a generation. Choose wisely.';

$metaTitle = 'The Weight of One Decision | Romans 5:12, 19';
$metaDescription = 'Romans 5:12, 19 preaching: one small decision can destroy or bring hope. Think first, weigh consequences, and choose what is right—not what is easy.';
$llmSummary = 'This Taglish preaching, “The Weight of One Decision,” unpacks Romans 5:12 and 5:19 (NIV). It argues that everyday choices—not only big life decisions—carry lasting weight. Fact 1 cites research that many early deaths relate to personal decisions with alternatives, warning that “just once” choices can destroy lives and families. Fact 2 explains how repeated small decisions become habits, then character, then destiny (examples: exercise, honesty at work, faithfulness in marriage). Fact 3 notes that consequential life outcomes often begin with one simple choice of companions, perseverance, or leaving toxic situations. It offers three practices for men: think before deciding, consider long-term consequences, and choose what is right over what is easy. The closing contrasts Adam’s disobedience and Christ’s obedience, urging listeners to ask what kind of man they are becoming through today’s choices.';

$postId = Post::create([
    'title' => 'Preaching: The Weight of One Decision',
    'excerpt' => $excerpt,
    'body' => $body,
    'category_id' => (int) $cat->id,
    'featured_image_id' => $mediaId,
    'meta_title' => $metaTitle,
    'meta_description' => $metaDescription,
    'llm_summary' => $llmSummary,
    'robots_noindex' => 0,
    'content_layout' => 'normal',
    'status' => 'published',
    'tags' => 'preaching, romans, decisions, faith, men',
]);

if ($postId < 1) {
    fwrite(STDERR, "Post::create failed (slug conflict?).\n");
    exit(1);
}

$post = Post::find($postId);
echo "Created post id={$postId} slug={$post->slug}\n";
echo "Public URL: /blog/{$post->slug}\n";
echo "JSON: /blog/{$post->slug}.json\n";
