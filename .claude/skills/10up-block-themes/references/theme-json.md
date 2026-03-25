# theme.json Reference

Complete reference for WordPress theme.json configuration.

## Schema and Version

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3
}
```

Always use version 3 for WordPress 6.4+.

## Settings

### Color Settings

```json
{
  "settings": {
    "color": {
      "background": true,
      "custom": true,
      "customDuotone": true,
      "customGradient": true,
      "defaultDuotone": true,
      "defaultGradients": true,
      "defaultPalette": true,
      "duotone": [],
      "gradients": [],
      "link": true,
      "palette": [
        {
          "slug": "primary",
          "color": "#0073aa",
          "name": "Primary"
        },
        {
          "slug": "secondary",
          "color": "#23282d",
          "name": "Secondary"
        },
        {
          "slug": "base",
          "color": "#ffffff",
          "name": "Base"
        },
        {
          "slug": "contrast",
          "color": "#1a1a1a",
          "name": "Contrast"
        }
      ],
      "text": true
    }
  }
}
```

### Typography Settings

```json
{
  "settings": {
    "typography": {
      "customFontSize": true,
      "dropCap": true,
      "fluid": true,
      "fontFamilies": [
        {
          "slug": "system",
          "name": "System",
          "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
        },
        {
          "slug": "heading",
          "name": "Heading",
          "fontFamily": "Georgia, serif"
        }
      ],
      "fontSizes": [
        {
          "slug": "small",
          "size": "0.875rem",
          "name": "Small"
        },
        {
          "slug": "medium",
          "size": "1rem",
          "name": "Medium"
        },
        {
          "slug": "large",
          "size": "1.25rem",
          "name": "Large"
        },
        {
          "slug": "x-large",
          "size": "1.5rem",
          "name": "Extra Large"
        }
      ],
      "fontStyle": true,
      "fontWeight": true,
      "letterSpacing": true,
      "lineHeight": true,
      "textColumns": true,
      "textDecoration": true,
      "textTransform": true,
      "writingMode": true
    }
  }
}
```

### Fluid Typography

```json
{
  "settings": {
    "typography": {
      "fluid": true,
      "fontSizes": [
        {
          "slug": "medium",
          "size": "1rem",
          "fluid": {
            "min": "0.875rem",
            "max": "1rem"
          }
        },
        {
          "slug": "large",
          "size": "1.5rem",
          "fluid": {
            "min": "1.25rem",
            "max": "1.75rem"
          }
        }
      ]
    }
  }
}
```

### Spacing Settings

```json
{
  "settings": {
    "spacing": {
      "blockGap": true,
      "margin": true,
      "padding": true,
      "units": ["px", "em", "rem", "%", "vw", "vh"],
      "customSpacingSize": true,
      "spacingScale": {
        "steps": 7
      },
      "spacingSizes": [
        {
          "slug": "10",
          "size": "0.625rem",
          "name": "1"
        },
        {
          "slug": "20",
          "size": "1rem",
          "name": "2"
        },
        {
          "slug": "30",
          "size": "1.5rem",
          "name": "3"
        },
        {
          "slug": "40",
          "size": "2rem",
          "name": "4"
        },
        {
          "slug": "50",
          "size": "3rem",
          "name": "5"
        }
      ]
    }
  }
}
```

### Layout Settings

```json
{
  "settings": {
    "layout": {
      "contentSize": "800px",
      "wideSize": "1200px",
      "allowEditing": true,
      "allowCustomContentAndWideSize": true
    }
  }
}
```

### Block-Specific Settings

```json
{
  "settings": {
    "blocks": {
      "core/paragraph": {
        "typography": {
          "fontSizes": [],
          "customFontSize": false
        }
      },
      "core/heading": {
        "typography": {
          "fontFamilies": []
        }
      }
    }
  }
}
```

### Custom Settings (10up Pattern)

```json
{
  "settings": {
    "custom": {
      "tenup": {
        "headerHeight": "80px",
        "sidebarWidth": "300px",
        "transition": {
          "duration": "0.3s",
          "easing": "ease-in-out"
        }
      }
    }
  }
}
```

Access in CSS: `var(--wp--custom--tenup--header-height)`

## Styles

### Global Styles

```json
{
  "styles": {
    "color": {
      "background": "var(--wp--preset--color--base)",
      "text": "var(--wp--preset--color--contrast)"
    },
    "typography": {
      "fontFamily": "var(--wp--preset--font-family--system)",
      "fontSize": "var(--wp--preset--font-size--medium)",
      "lineHeight": "1.6"
    },
    "spacing": {
      "blockGap": "var(--wp--preset--spacing--30)"
    }
  }
}
```

### Element Styles

```json
{
  "styles": {
    "elements": {
      "heading": {
        "typography": {
          "fontFamily": "var(--wp--preset--font-family--heading)",
          "fontWeight": "700"
        }
      },
      "h1": {
        "typography": {
          "fontSize": "var(--wp--preset--font-size--x-large)"
        }
      },
      "link": {
        "color": {
          "text": "var(--wp--preset--color--primary)"
        },
        ":hover": {
          "color": {
            "text": "var(--wp--preset--color--secondary)"
          }
        }
      },
      "button": {
        "color": {
          "background": "var(--wp--preset--color--primary)",
          "text": "var(--wp--preset--color--base)"
        }
      }
    }
  }
}
```

### Block Styles

```json
{
  "styles": {
    "blocks": {
      "core/group": {
        "spacing": {
          "padding": {
            "top": "var(--wp--preset--spacing--30)",
            "bottom": "var(--wp--preset--spacing--30)"
          }
        }
      },
      "core/navigation": {
        "typography": {
          "fontSize": "var(--wp--preset--font-size--small)"
        }
      },
      "core/post-title": {
        "typography": {
          "fontSize": "var(--wp--preset--font-size--x-large)"
        },
        "elements": {
          "link": {
            "color": {
              "text": "inherit"
            },
            ":hover": {
              "typography": {
                "textDecoration": "underline"
              }
            }
          }
        }
      }
    }
  }
}
```

### Custom CSS (Per Block)

```json
{
  "styles": {
    "blocks": {
      "core/group": {
        "css": "& .custom-class { display: grid; gap: 1rem; }"
      }
    },
    "css": ".site-header { position: sticky; top: 0; }"
  }
}
```

## Template and Template Part Registration

```json
{
  "customTemplates": [
    {
      "name": "page-full-width",
      "title": "Full Width Page",
      "postTypes": ["page"]
    },
    {
      "name": "single-product",
      "title": "Single Product",
      "postTypes": ["product"]
    }
  ],
  "templateParts": [
    {
      "name": "header",
      "title": "Header",
      "area": "header"
    },
    {
      "name": "footer",
      "title": "Footer",
      "area": "footer"
    },
    {
      "name": "sidebar",
      "title": "Sidebar",
      "area": "uncategorized"
    }
  ]
}
```

## Patterns

```json
{
  "patterns": [
    "theme-name/hero",
    "theme-name/card"
  ]
}
```

## Complete Example

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "color": {
      "defaultPalette": false,
      "palette": [
        { "slug": "primary", "color": "#0073aa", "name": "Primary" },
        { "slug": "secondary", "color": "#23282d", "name": "Secondary" },
        { "slug": "base", "color": "#ffffff", "name": "Base" },
        { "slug": "contrast", "color": "#1a1a1a", "name": "Contrast" }
      ]
    },
    "typography": {
      "fluid": true,
      "fontFamilies": [
        {
          "slug": "system",
          "name": "System",
          "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
        }
      ],
      "fontSizes": [
        { "slug": "small", "size": "0.875rem", "name": "Small" },
        { "slug": "medium", "size": "1rem", "name": "Medium" },
        { "slug": "large", "size": "1.25rem", "name": "Large" }
      ]
    },
    "spacing": {
      "units": ["px", "rem", "%"],
      "spacingSizes": [
        { "slug": "10", "size": "0.5rem", "name": "1" },
        { "slug": "20", "size": "1rem", "name": "2" },
        { "slug": "30", "size": "1.5rem", "name": "3" },
        { "slug": "40", "size": "2rem", "name": "4" }
      ]
    },
    "layout": {
      "contentSize": "800px",
      "wideSize": "1200px"
    }
  },
  "styles": {
    "color": {
      "background": "var(--wp--preset--color--base)",
      "text": "var(--wp--preset--color--contrast)"
    },
    "typography": {
      "fontFamily": "var(--wp--preset--font-family--system)",
      "fontSize": "var(--wp--preset--font-size--medium)",
      "lineHeight": "1.6"
    },
    "elements": {
      "link": {
        "color": { "text": "var(--wp--preset--color--primary)" }
      }
    },
    "blocks": {
      "core/site-title": {
        "typography": { "fontSize": "var(--wp--preset--font-size--large)" }
      }
    }
  },
  "templateParts": [
    { "name": "header", "title": "Header", "area": "header" },
    { "name": "footer", "title": "Footer", "area": "footer" }
  ]
}
```
