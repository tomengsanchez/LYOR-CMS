<?php
/** Shared AI citation / FAQ fields for page & post editors. Expects $entity (page|post object). */
$siteSeoTips = \App\Models\AppSettings::getSiteSeoConfig();
$showAiTips = !empty($siteSeoTips->show_ai_writing_tips);
$faqItems = \App\PublicSeo::parseFaqItems($entity->faq_json ?? null);
$faqJsonValue = $faqItems !== [] ? (string) json_encode($faqItems, JSON_UNESCAPED_UNICODE) : '';
?>
<?php if ($showAiTips): ?>
<div class="alert alert-light border small mb-3" role="note">
    <strong>AI search writing tips:</strong>
    use clear H1→H2→H3 hierarchy; lead sections with a one-sentence bottom line (BLUF);
    prefer short paragraphs, lists, and Q&amp;A; add original data or firsthand notes for E-E-A-T;
    link related pillar/cluster pages instead of isolated keywords.
</div>
<?php endif; ?>
<div class="mb-3">
    <label class="form-label" for="citationSnippet">Citation snippet (BLUF)</label>
    <textarea name="citation_snippet" id="citationSnippet" class="form-control" rows="2" maxlength="500"
        placeholder="One declarative sentence AI tools can quote as the direct answer"><?= htmlspecialchars($entity->citation_snippet ?? '') ?></textarea>
    <small class="text-muted"><span id="citationSnippetCount">0</span>/500 · Emitted as <code>meta name=&quot;citation&quot;</code>, Schema.org speakable/abstract, JSON, and a public lead paragraph.</small>
</div>
<div class="mb-0">
    <label class="form-label">FAQ pairs (Q&amp;A for AI clipping)</label>
    <div id="faqEditor" class="mb-2"></div>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="faqAddRow">Add question</button>
    <input type="hidden" name="faq_json" id="faqJsonInput" value="<?= htmlspecialchars($faqJsonValue) ?>">
    <small class="text-muted d-block mt-1">Up to 20 pairs. Emits Schema.org <code>FAQPage</code> when enabled in General → SEO.</small>
</div>
