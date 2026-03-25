# Third-Party Script Management Reference

Comprehensive guide to managing third-party scripts for optimal performance.

## Identifying Performance Impact

### Chrome DevTools Analysis

**Lighthouse Audits:**
- "Reduce JavaScript execution time"
- "Avoid enormous network payloads"
- "Minimize third-party usage"

**Coverage Tool:**
1. Open DevTools > Coverage tab
2. Reload page
3. Identify unused code from third-party scripts

**Network Request Blocking:**
1. Open DevTools > Network tab
2. Right-click request > "Block request URL"
3. Measure performance impact

### Query Monitor (WordPress)

```bash
# Install Query Monitor
wp plugin install query-monitor --activate
```

Check the HTTP API panel for external requests and their timing.

## Script Loading Strategies

### Defer vs Async

```html
<!-- Defer: Execute after DOM parsing, in order -->
<script defer src="analytics.js"></script>

<!-- Async: Execute when available, not in order -->
<script async src="tracking.js"></script>
```

### WordPress Implementation

```php
add_filter('script_loader_tag', function($tag, $handle, $src) {
    // Defer non-critical scripts
    $defer_scripts = [
        'analytics',
        'social-share',
        'comments',
        'livechat',
    ];

    if (in_array($handle, $defer_scripts, true)) {
        return str_replace(' src', ' defer src', $tag);
    }

    // Async for independent scripts
    $async_scripts = [
        'tracking-pixel',
        'remarketing',
    ];

    if (in_array($handle, $async_scripts, true)) {
        return str_replace(' src', ' async src', $tag);
    }

    return $tag;
}, 10, 3);
```

### Script Priority Order

1. **Immediate (no defer/async):** Consent managers, critical analytics
2. **Async:** Independent tracking pixels
3. **Defer:** Feature scripts (chat, social, comments)
4. **Lazy:** Heavy embeds (videos, maps, carousels)

## Facade Pattern

### YouTube Facade

```html
<!-- Lightweight preview -->
<div class="youtube-facade" data-video-id="VIDEO_ID">
    <img src="https://img.youtube.com/vi/VIDEO_ID/hqdefault.jpg"
         alt="Video thumbnail"
         loading="lazy">
    <button class="play-button" aria-label="Play video">
        <svg><!-- Play icon --></svg>
    </button>
</div>
```

```javascript
class YouTubeFacade {
    constructor(element) {
        this.element = element;
        this.videoId = element.dataset.videoId;
        this.element.addEventListener('click', () => this.loadVideo());
    }

    loadVideo() {
        const iframe = document.createElement('iframe');
        iframe.src = `https://www.youtube.com/embed/${this.videoId}?autoplay=1`;
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        this.element.innerHTML = '';
        this.element.appendChild(iframe);
    }
}

document.querySelectorAll('.youtube-facade').forEach(el => {
    new YouTubeFacade(el);
});
```

### Chat Widget Facade

```javascript
// Load chat widget on user interaction
const chatTriggers = ['mousemove', 'scroll', 'keydown', 'touchstart'];

function loadChatWidget() {
    // Remove listeners
    chatTriggers.forEach(event => {
        document.removeEventListener(event, loadChatWidget);
    });

    // Load actual chat script
    const script = document.createElement('script');
    script.src = 'https://chat-provider.com/widget.js';
    document.body.appendChild(script);
}

// Add listeners
chatTriggers.forEach(event => {
    document.addEventListener(event, loadChatWidget, { once: true, passive: true });
});
```

## Intersection Observer Loading

### Load Script on Visibility

```javascript
function loadOnVisible(selector, loadCallback) {
    const element = document.querySelector(selector);
    if (!element) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadCallback();
                observer.disconnect();
            }
        });
    }, {
        rootMargin: '100px' // Load slightly before visible
    });

    observer.observe(element);
}

// Usage
loadOnVisible('#comments', () => {
    // Load Disqus
    const script = document.createElement('script');
    script.src = 'https://disqus.com/embed.js';
    document.body.appendChild(script);
});

loadOnVisible('.social-share', () => {
    // Load social share scripts
});
```

### WordPress Implementation

```php
// Enqueue script with lazy loading data
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'lazy-comments',
        get_template_directory_uri() . '/js/lazy-comments.js',
        [],
        '1.0',
        true
    );

    wp_localize_script('lazy-comments', 'lazyCommentsConfig', [
        'disqusUrl' => 'https://your-site.disqus.com/embed.js',
        'selector' => '#comments',
    ]);
});
```

## Service Workers

### Google Workbox Setup

```javascript
// sw.js
import { registerRoute } from 'workbox-routing';
import { CacheFirst, NetworkFirst, StaleWhileRevalidate } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';

// Cache third-party scripts with network-first strategy
registerRoute(
    ({ url }) => url.origin === 'https://www.google-analytics.com',
    new NetworkFirst({
        cacheName: 'analytics-cache',
        plugins: [
            new ExpirationPlugin({
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24, // 1 day
            }),
        ],
    })
);

// Cache fonts with cache-first strategy
registerRoute(
    ({ request }) => request.destination === 'font',
    new CacheFirst({
        cacheName: 'font-cache',
        plugins: [
            new ExpirationPlugin({
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
            }),
        ],
    })
);

// Stale-while-revalidate for third-party images
registerRoute(
    ({ url }) => url.origin.includes('cdn'),
    new StaleWhileRevalidate({
        cacheName: 'cdn-cache',
    })
);
```

### WordPress Service Worker Registration

```php
add_action('wp_footer', function() {
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .catch(err => console.log('SW registration failed:', err));
    }
    </script>
    <?php
});
```

## Google Tag Manager

### Performance Best Practices

```html
<!-- Load GTM efficiently -->
<script>
(function(w,d,s,l,i){
    w[l]=w[l]||[];
    w[l].push({'gtm.start': new Date().getTime(), event:'gtm.js'});
    var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),
        dl=l!='dataLayer'?'&l='+l:'';
    j.async=true;
    j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
    f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-XXXXX');
</script>
```

### GTM Container Optimization

**Do:**
- Use image tags for simple tracking (minimal impact)
- Use Custom HTML sparingly
- Trigger tags on specific events, not All Pages
- Use trigger exceptions to limit firing

**Don't:**
- Inject scripts that modify the DOM visually
- Use synchronous tags
- Fire tags on DOM Ready if not needed
- Duplicate tracking across containers

### Auditing GTM Tags

```javascript
// Console script to audit GTM tags
if (window.google_tag_manager) {
    const container = Object.keys(window.google_tag_manager)[0];
    const gtm = window.google_tag_manager[container];
    console.log('GTM Container:', container);
    console.log('Data Layer:', window.dataLayer);
}
```

## Ad Script Optimization

### Prevent CLS from Ads

```css
/* Reserve space for ad slots */
.ad-container {
    min-height: 250px; /* Standard ad height */
    background-color: #f5f5f5;
}

.ad-container--leaderboard {
    min-height: 90px;
}

.ad-container--sidebar {
    min-height: 600px;
}
```

### Ad Script Loading

```html
<!-- Preload ad script -->
<link rel="preload" href="https://ad-provider.com/tag.js" as="script">

<!-- Load ad script in head -->
<script async src="https://ad-provider.com/tag.js"></script>
```

```javascript
// Define ad slots before script loads
window.adConfig = window.adConfig || [];
window.adConfig.push({
    slot: 'header-leaderboard',
    sizes: [[728, 90], [970, 90]],
});
```

### Lazy Load Below-Fold Ads

```javascript
const adObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const slot = entry.target;
            loadAdSlot(slot.dataset.adSlot);
            adObserver.unobserve(slot);
        }
    });
}, {
    rootMargin: '200px'
});

document.querySelectorAll('.ad-container[data-ad-slot]').forEach(ad => {
    adObserver.observe(ad);
});
```

## Analytics Optimization

### Google Analytics 4

```html
<!-- Async loading -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXX"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-XXXXXXXX', {
    send_page_view: true,
    // Optimize for performance
    transport_type: 'beacon'
});
</script>
```

### Consent-Based Loading

```javascript
// Load analytics only after consent
function loadAnalytics() {
    if (hasConsent('analytics')) {
        const script = document.createElement('script');
        script.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXX';
        script.async = true;
        document.head.appendChild(script);

        script.onload = () => {
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-XXXXXXXX');
        };
    }
}

// Listen for consent
document.addEventListener('consent-granted', loadAnalytics);
```

## Social Embeds

### Twitter/X Optimization

```html
<!-- Lazy load Twitter embed -->
<blockquote class="twitter-tweet" data-lazy="true">
    <a href="https://twitter.com/user/status/123">Tweet</a>
</blockquote>

<script>
document.querySelectorAll('.twitter-tweet[data-lazy]').forEach(tweet => {
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            // Load Twitter widget
            const script = document.createElement('script');
            script.src = 'https://platform.twitter.com/widgets.js';
            document.body.appendChild(script);
            observer.disconnect();
        }
    });
    observer.observe(tweet);
});
</script>
```

### Instagram Optimization

```javascript
// Load Instagram embed on visibility
function loadInstagram(container) {
    const script = document.createElement('script');
    script.src = 'https://www.instagram.com/embed.js';
    script.async = true;
    document.body.appendChild(script);

    script.onload = () => {
        if (window.instgrm) {
            window.instgrm.Embeds.process();
        }
    };
}
```

## Monitoring Third-Party Performance

### Real User Monitoring

```javascript
// Monitor third-party script performance
const observer = new PerformanceObserver((list) => {
    list.getEntries().forEach(entry => {
        if (entry.initiatorType === 'script') {
            const isThirdParty = !entry.name.includes(window.location.hostname);
            if (isThirdParty) {
                console.log('Third-party script:', {
                    url: entry.name,
                    duration: entry.duration,
                    transferSize: entry.transferSize,
                });
            }
        }
    });
});

observer.observe({ entryTypes: ['resource'] });
```

### Long Task Detection

```javascript
// Detect long tasks from third-party scripts
const longTaskObserver = new PerformanceObserver((list) => {
    list.getEntries().forEach(entry => {
        if (entry.duration > 50) {
            console.warn('Long task detected:', {
                duration: entry.duration,
                attribution: entry.attribution,
            });
        }
    });
});

longTaskObserver.observe({ entryTypes: ['longtask'] });
```

## Performance Checklist

1. Audit all third-party scripts with Chrome DevTools
2. Remove unused scripts
3. Defer non-critical scripts
4. Use facade pattern for heavy embeds
5. Lazy load scripts below the fold
6. Reserve space for dynamic content (ads)
7. Use service workers for caching
8. Optimize GTM container
9. Load analytics with consent
10. Monitor third-party impact continuously
