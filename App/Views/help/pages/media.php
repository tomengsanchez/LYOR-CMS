<h5>Media</h5>
<p>Upload JPG, PNG, GIF, WebP, SVG, or PDF files. Admin: <code>/admin/media</code>.</p>
<p><strong>While editing pages/posts:</strong> You can upload images for featured image and the block builder without visiting this screen first. Those uploads still land in the Media library.</p>
<p><strong>Responsive images (WordPress-style):</strong> On upload, JPG/PNG/WebP automatically get smaller copies — <code>thumbnail</code> (150×150 crop), <code>medium</code> (300), <code>medium_large</code> (768), <code>large</code> (1024), plus 1536 and 2048 when the original is big enough. The public site uses <code>srcset</code> / <code>sizes</code> so phones download a smaller file and desktops can use a larger one.</p>
<p><strong>URLs:</strong></p>
<ul>
    <li>Admin (login required): <code>/serve/media/{id}</code> or <code>/serve/media/{id}/{size}</code></li>
    <li>Public (crawlers &amp; visitors): <code>/share/media/{id}</code> or <code>/share/media/{id}/{size}</code> — e.g. <code>/share/media/12/medium</code></li>
</ul>
<p>Open Graph / social previews still use the full original for quality. Existing images: regenerate sizes with <code>php cli/regenerate_media_sizes.php</code>.</p>
<p>Featured images on pages/posts and block-builder images use responsive markup automatically.</p>
