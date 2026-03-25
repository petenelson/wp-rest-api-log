# Font Optimization Reference

Comprehensive guide to optimizing web fonts for performance.

## Font Display Strategy

### font-display Values

| Value | Behavior | Use Case |
|-------|----------|----------|
| `swap` | Immediate fallback, swap when loaded | Body text (recommended) |
| `optional` | Brief block, may not swap | Non-critical fonts |
| `fallback` | Short block (100ms), short swap (3s) | Important but not critical |
| `block` | Long block period | Icons, decorative |
| `auto` | Browser decides | Not recommended |

### Implementation

```css
@font-face {
    font-family: 'Primary Font';
    font-display: swap;
    src: url('primary.woff2') format('woff2'),
         url('primary.woff') format('woff');
    font-weight: 400;
    font-style: normal;
}

@font-face {
    font-family: 'Primary Font';
    font-display: swap;
    src: url('primary-bold.woff2') format('woff2'),
         url('primary-bold.woff') format('woff');
    font-weight: 700;
    font-style: normal;
}
```

## Preconnect and Preload

### Google Fonts

```html
<!-- Preconnect to Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Load fonts with display=swap -->
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
```

### Self-Hosted Fonts

```html
<!-- Preload critical font files -->
<link rel="preload"
      href="/fonts/primary-regular.woff2"
      as="font"
      type="font/woff2"
      crossorigin>
<link rel="preload"
      href="/fonts/primary-bold.woff2"
      as="font"
      type="font/woff2"
      crossorigin>
```

### WordPress Implementation

```php
add_action('wp_head', function() {
    // Preconnect to font CDN
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';

    // Preload local fonts
    $font_dir = get_template_directory_uri() . '/fonts/';
    echo '<link rel="preload" href="' . $font_dir . 'primary.woff2" as="font" type="font/woff2" crossorigin>';
}, 1);
```

## Self-Hosting Fonts

### Local Font Check

```css
@font-face {
    font-family: 'System Font';
    src: local('Segoe UI'),
         local('SF Pro Display'),
         local('Roboto'),
         local('Helvetica Neue'),
         url('system-fallback.woff2') format('woff2');
    font-display: swap;
}
```

### Complete Self-Hosted Setup

```css
/* Regular weight */
@font-face {
    font-family: 'Brand Font';
    font-display: swap;
    font-weight: 400;
    font-style: normal;
    src: local('Brand Font'),
         local('BrandFont-Regular'),
         url('/fonts/brand-regular.woff2') format('woff2'),
         url('/fonts/brand-regular.woff') format('woff');
}

/* Bold weight */
@font-face {
    font-family: 'Brand Font';
    font-display: swap;
    font-weight: 700;
    font-style: normal;
    src: local('Brand Font Bold'),
         local('BrandFont-Bold'),
         url('/fonts/brand-bold.woff2') format('woff2'),
         url('/fonts/brand-bold.woff') format('woff');
}

/* Italic */
@font-face {
    font-family: 'Brand Font';
    font-display: swap;
    font-weight: 400;
    font-style: italic;
    src: local('Brand Font Italic'),
         local('BrandFont-Italic'),
         url('/fonts/brand-italic.woff2') format('woff2'),
         url('/fonts/brand-italic.woff') format('woff');
}
```

## Font Subsetting

### Unicode Range

```css
/* Latin subset */
@font-face {
    font-family: 'Primary Font';
    font-display: swap;
    src: url('primary-latin.woff2') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA,
                   U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193,
                   U+2212, U+2215, U+FEFF, U+FFFD;
}

/* Latin Extended */
@font-face {
    font-family: 'Primary Font';
    font-display: swap;
    src: url('primary-latin-ext.woff2') format('woff2');
    unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB,
                   U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
```

### Subsetting Tools

Use glyphhanger or fonttools to create subsets:

```bash
# Using glyphhanger
npx glyphhanger https://example.com --subset=fonts/brand.ttf

# Using fonttools
pyftsubset brand.ttf --unicodes="U+0000-00FF" --output-file="brand-latin.woff2" --flavor=woff2
```

## Variable Fonts

### Basic Variable Font

```css
@font-face {
    font-family: 'Variable Font';
    font-display: swap;
    src: url('variable.woff2') format('woff2-variations');
    font-weight: 100 900;
    font-stretch: 75% 125%;
}

/* Usage */
.light-text {
    font-weight: 300;
}

.heavy-text {
    font-weight: 800;
}
```

### Feature Settings

```css
.stylistic {
    font-feature-settings: 'ss01' on; /* Stylistic set 1 */
}

.tabular-numbers {
    font-feature-settings: 'tnum' on; /* Tabular numbers */
}

.small-caps {
    font-feature-settings: 'smcp' on; /* Small caps */
}
```

## Reducing CLS with size-adjust

### Matching Fallback Metrics

```css
/* Fallback font with size-adjust to match web font */
@font-face {
    font-family: 'Fallback Sans';
    src: local('Arial');
    size-adjust: 105%;
    ascent-override: 90%;
    descent-override: 20%;
    line-gap-override: 0%;
}

/* Web font */
@font-face {
    font-family: 'Primary Sans';
    src: url('primary-sans.woff2') format('woff2');
    font-display: swap;
}

/* Usage with fallback stack */
body {
    font-family: 'Primary Sans', 'Fallback Sans', sans-serif;
}
```

### Using @font-face Descriptors

```css
@font-face {
    font-family: 'Adjusted System';
    src: local('Times New Roman');
    size-adjust: 110%;
    ascent-override: 85%;
    descent-override: 22%;
    line-gap-override: normal;
}

body {
    font-family: 'Custom Serif', 'Adjusted System', serif;
}
```

## Font Loading Strategies

### Critical Font Inlining

```php
function inline_critical_fonts() {
    $font_path = get_template_directory() . '/fonts/primary.woff2';
    $font_data = base64_encode(file_get_contents($font_path));

    echo '<style>
    @font-face {
        font-family: "Primary Font";
        font-display: swap;
        src: url(data:font/woff2;base64,' . $font_data . ') format("woff2");
    }
    </style>';
}
add_action('wp_head', 'inline_critical_fonts', 1);
```

Note: Only inline very small critical fonts (icons, etc.). Large fonts should be loaded normally.

### Font Loading API

```javascript
// Check if fonts are loaded
document.fonts.ready.then(() => {
    document.body.classList.add('fonts-loaded');
});

// Load specific fonts
const font = new FontFace('Primary Font', 'url(/fonts/primary.woff2)');
font.load().then((loadedFont) => {
    document.fonts.add(loadedFont);
    document.body.classList.add('fonts-loaded');
});
```

### Progressive Font Loading

```css
/* Initial styles with system fonts */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Enhanced styles when fonts are loaded */
.fonts-loaded body {
    font-family: 'Primary Font', sans-serif;
}
```

## WordPress Theme.json

### Block Editor Font Configuration

```json
{
    "version": 2,
    "settings": {
        "typography": {
            "fontFamilies": [
                {
                    "fontFamily": "'Primary Font', sans-serif",
                    "name": "Primary",
                    "slug": "primary",
                    "fontFace": [
                        {
                            "fontFamily": "Primary Font",
                            "fontWeight": "400",
                            "fontStyle": "normal",
                            "fontDisplay": "swap",
                            "src": ["file:./fonts/primary-regular.woff2"]
                        },
                        {
                            "fontFamily": "Primary Font",
                            "fontWeight": "700",
                            "fontStyle": "normal",
                            "fontDisplay": "swap",
                            "src": ["file:./fonts/primary-bold.woff2"]
                        }
                    ]
                },
                {
                    "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                    "name": "System",
                    "slug": "system"
                }
            ]
        }
    }
}
```

## Font Formats

### Format Priority

| Format | Support | Use Case |
|--------|---------|----------|
| WOFF2 | Modern browsers | Primary format |
| WOFF | Older browsers | Fallback |
| TTF/OTF | Legacy | Not recommended for web |
| EOT | IE only | Deprecated |

### Recommended Stack

```css
@font-face {
    font-family: 'Primary Font';
    src: url('font.woff2') format('woff2'),
         url('font.woff') format('woff');
    font-display: swap;
}
```

## Performance Guidelines

### Font Loading Budget

- Maximum 2 font families
- Maximum 4 font files (weights/styles)
- Target < 100KB total font weight

### Checklist

1. Use `font-display: swap` for text fonts
2. Preconnect to font origins
3. Preload critical font files
4. Self-host when possible
5. Use WOFF2 format
6. Subset fonts to needed characters
7. Consider variable fonts for multiple weights
8. Use size-adjust to reduce CLS
9. Limit font families and weights
10. Use system fonts where appropriate

### System Font Stack

```css
/* Modern system font stack */
body {
    font-family:
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        Roboto,
        'Helvetica Neue',
        Arial,
        'Noto Sans',
        sans-serif,
        'Apple Color Emoji',
        'Segoe UI Emoji',
        'Segoe UI Symbol',
        'Noto Color Emoji';
}

/* Monospace system font stack */
code, pre {
    font-family:
        ui-monospace,
        SFMono-Regular,
        'SF Mono',
        Menlo,
        Consolas,
        'Liberation Mono',
        monospace;
}
```
