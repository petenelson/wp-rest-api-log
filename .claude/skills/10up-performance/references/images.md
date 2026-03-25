# Image Optimization Reference

Comprehensive guide to optimizing images for web performance.

## Responsive Images

### srcset and sizes

```html
<img
  srcset="image-320w.jpg 320w,
          image-480w.jpg 480w,
          image-800w.jpg 800w,
          image-1200w.jpg 1200w"
  sizes="(max-width: 320px) 280px,
         (max-width: 480px) 440px,
         (max-width: 800px) 760px,
         1200px"
  src="image-800w.jpg"
  alt="Description"
  width="800"
  height="600"
/>
```

### WordPress Responsive Images

WordPress automatically generates responsive images. To customize:

```php
// Add custom image sizes
add_image_size('hero', 1920, 1080, true);
add_image_size('card', 400, 300, true);

// Filter srcset output
add_filter('wp_calculate_image_srcset', function($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    // Remove images smaller than 200px
    foreach ($sources as $width => $source) {
        if ($width < 200) {
            unset($sources[$width]);
        }
    }
    return $sources;
}, 10, 5);

// Filter sizes attribute
add_filter('wp_calculate_image_sizes', function($sizes, $size, $image_src, $image_meta, $attachment_id) {
    // Custom sizes for specific contexts
    return '(max-width: 768px) 100vw, 50vw';
}, 10, 5);
```

## Loading Attributes

### loading Attribute

```html
<!-- Above-the-fold: eager loading -->
<img src="hero.jpg" loading="eager" />

<!-- Below-the-fold: lazy loading -->
<img src="gallery.jpg" loading="lazy" />
```

### decoding Attribute

```html
<!-- Above-the-fold: synchronous decoding -->
<img src="hero.jpg" decoding="sync" />

<!-- Below-the-fold: asynchronous decoding -->
<img src="gallery.jpg" decoding="async" />
```

### fetchpriority Attribute

```html
<!-- LCP image: high priority -->
<img src="hero.jpg" fetchpriority="high" loading="eager" />

<!-- Non-critical image: low priority -->
<img src="decoration.jpg" fetchpriority="low" loading="lazy" />
```

### WordPress Implementation

```php
// Add fetchpriority to hero images
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    // Check if this is a hero image context
    if (doing_filter('render_block_core/cover') || is_singular() && in_the_loop()) {
        $attr['fetchpriority'] = 'high';
        $attr['loading'] = 'eager';
        $attr['decoding'] = 'sync';
    }
    return $attr;
}, 10, 3);

// Remove lazy loading from first images
add_filter('wp_lazy_loading_enabled', function($default, $tag_name, $context) {
    // Disable for images in specific contexts
    if ($context === 'the_content' && did_action('the_content') === 0) {
        return false;
    }
    return $default;
}, 10, 3);
```

## Width and Height Attributes

### Preventing CLS

Always include width and height to prevent layout shifts:

```html
<img
  src="image.jpg"
  alt="Description"
  width="800"
  height="600"
/>
```

### Aspect Ratio CSS

Use CSS aspect-ratio for flexible sizing:

```css
.responsive-image {
    width: 100%;
    height: auto;
    aspect-ratio: 16 / 9;
    object-fit: cover;
}
```

### WordPress Enforcement

```php
// Ensure all images have dimensions
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment) {
    if (empty($attr['width']) || empty($attr['height'])) {
        $meta = wp_get_attachment_metadata($attachment->ID);
        if ($meta && isset($meta['width'], $meta['height'])) {
            $attr['width'] = $meta['width'];
            $attr['height'] = $meta['height'];
        }
    }
    return $attr;
}, 10, 2);

// Add dimensions to content images
add_filter('the_content', function($content) {
    if (!preg_match_all('/<img[^>]+>/i', $content, $matches)) {
        return $content;
    }

    foreach ($matches[0] as $img) {
        // Skip if already has dimensions
        if (preg_match('/width=|height=/i', $img)) {
            continue;
        }

        // Extract src and get dimensions
        if (preg_match('/src=["\']([^"\']+)["\']/i', $img, $src_match)) {
            $attachment_id = attachment_url_to_postid($src_match[1]);
            if ($attachment_id) {
                $meta = wp_get_attachment_metadata($attachment_id);
                if ($meta) {
                    $new_img = str_replace('<img', sprintf('<img width="%d" height="%d"', $meta['width'], $meta['height']), $img);
                    $content = str_replace($img, $new_img, $content);
                }
            }
        }
    }

    return $content;
});
```

## Modern Image Formats

### WebP Support

```php
// Enable WebP uploads in WordPress
add_filter('upload_mimes', function($mimes) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

// Enable WebP output (Performance Lab plugin recommended)
add_filter('image_editor_output_format', function($formats) {
    $formats['image/jpeg'] = 'image/webp';
    $formats['image/png'] = 'image/webp';
    return $formats;
});
```

### Picture Element

```html
<picture>
    <source srcset="image.avif" type="image/avif">
    <source srcset="image.webp" type="image/webp">
    <img src="image.jpg" alt="Description" width="800" height="600">
</picture>
```

### CDN-Based Format Conversion

Most CDNs can automatically convert images. Example with Cloudflare:

```
https://example.com/image.jpg?format=webp
```

## Image CDN Integration

### Cloudinary

```php
function cloudinary_url($attachment_id, $options = []) {
    $url = wp_get_attachment_url($attachment_id);
    $cloud_name = 'your-cloud-name';

    $transforms = [];
    if (isset($options['width'])) {
        $transforms[] = 'w_' . $options['width'];
    }
    if (isset($options['height'])) {
        $transforms[] = 'h_' . $options['height'];
    }
    if (isset($options['crop'])) {
        $transforms[] = 'c_' . $options['crop'];
    }
    $transforms[] = 'f_auto'; // Auto format
    $transforms[] = 'q_auto'; // Auto quality

    $transform_string = implode(',', $transforms);

    return "https://res.cloudinary.com/{$cloud_name}/image/fetch/{$transform_string}/{$url}";
}
```

### WordPress VIP File Service

```php
// VIP automatically handles responsive images
// Use standard WordPress functions
the_post_thumbnail('large');
wp_get_attachment_image($id, 'medium');
```

## SVG Optimization

### Safe SVG Uploads

```php
// Allow SVG uploads (with sanitization)
add_filter('upload_mimes', function($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

// Sanitize SVG on upload
add_filter('wp_handle_upload_prefilter', function($file) {
    if ($file['type'] === 'image/svg+xml') {
        // Use a sanitization library like SVG Sanitizer
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();
        $content = file_get_contents($file['tmp_name']);
        $clean = $sanitizer->sanitize($content);
        file_put_contents($file['tmp_name'], $clean);
    }
    return $file;
});
```

### Inline SVG

```php
// Function to inline SVG
function inline_svg($path) {
    $svg = file_get_contents($path);
    // Remove XML declaration
    $svg = preg_replace('/<\?xml.*?\?>/', '', $svg);
    return $svg;
}
```

### SVGOMG Optimization

Optimize SVGs before upload using SVGOMG tool:
- Remove metadata
- Optimize paths
- Remove hidden elements
- Minify

## Preloading Images

### Preload LCP Image

```php
add_action('wp_head', function() {
    if (is_singular() && has_post_thumbnail()) {
        $thumbnail_id = get_post_thumbnail_id();
        $image = wp_get_attachment_image_src($thumbnail_id, 'large');

        if ($image) {
            printf(
                '<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="%s">',
                esc_url($image[0]),
                esc_attr(wp_get_attachment_image_srcset($thumbnail_id, 'large')),
                esc_attr(wp_get_attachment_image_sizes($thumbnail_id, 'large'))
            );
        }
    }
}, 1);
```

### Preload with Media Queries

```html
<link rel="preload" as="image"
      href="hero-desktop.jpg"
      media="(min-width: 1024px)">
<link rel="preload" as="image"
      href="hero-mobile.jpg"
      media="(max-width: 1023px)">
```

## Background Images

### CSS Background Images

```css
/* Responsive background images */
.hero {
    background-image: url('hero-mobile.jpg');
    background-size: cover;
}

@media (min-width: 768px) {
    .hero {
        background-image: url('hero-tablet.jpg');
    }
}

@media (min-width: 1024px) {
    .hero {
        background-image: url('hero-desktop.jpg');
    }
}

/* WebP with fallback */
.hero {
    background-image: url('hero.jpg');
}

@supports (background-image: url('hero.webp')) {
    .hero {
        background-image: url('hero.webp');
    }
}
```

### Lazy Loading Background Images

```javascript
// Use Intersection Observer for background images
const lazyBackgrounds = document.querySelectorAll('[data-bg]');

const bgObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;
            el.style.backgroundImage = `url('${el.dataset.bg}')`;
            bgObserver.unobserve(el);
        }
    });
});

lazyBackgrounds.forEach(bg => bgObserver.observe(bg));
```

## Image Compression

### Quality Settings

```php
// Set JPEG quality
add_filter('jpeg_quality', function() {
    return 82; // Good balance of quality and size
});

// Set WebP quality
add_filter('wp_editor_set_quality', function($quality, $mime_type) {
    if ($mime_type === 'image/webp') {
        return 80;
    }
    return $quality;
}, 10, 2);
```

### Compression Guidelines

| Format | Quality | Use Case |
|--------|---------|----------|
| JPEG | 80-85 | Photos, complex images |
| WebP | 75-80 | Modern browsers, all images |
| PNG | Lossless | Graphics with transparency |
| AVIF | 60-70 | Cutting-edge browsers |

## Performance Checklist

1. Use responsive images with srcset and sizes
2. Add width and height to all images
3. Use loading="lazy" for below-the-fold images
4. Use fetchpriority="high" for LCP images
5. Serve WebP format when possible
6. Use a CDN for image delivery
7. Preload critical images
8. Optimize SVGs before upload
9. Set appropriate compression quality
10. Avoid base64-encoded images
