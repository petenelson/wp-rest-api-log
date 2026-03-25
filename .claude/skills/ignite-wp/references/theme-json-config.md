# theme.json Configuration Reference

Complete reference for configuring Ignite blocks via theme.json.

## Configuration Structure

All Ignite block configuration uses this path:

```json
{
  "settings": {
    "blocks": {
      "tenup/block-name": {
        "custom": {
          "tenup": {
            // Block-specific settings
          }
        }
      }
    }
  }
}
```

## Accordion

### tenup/accordion-header

```json
{
  "settings": {
    "blocks": {
      "tenup/accordion-header": {
        "custom": {
          "tenup": {
            "icon": {
              "iconSet": "ignite-wp",
              "iconName": "chevron-down"
            },
            ":expanded": {
              "icon": {
                "iconSet": "ignite-wp",
                "iconName": "chevron-up"
              }
            },
            "iconPosition": "right"
          }
        }
      }
    }
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `icon` | object | chevron-down | Icon when collapsed |
| `icon.iconSet` | string | ignite-wp | Icon set name |
| `icon.iconName` | string | chevron-down | Icon name |
| `:expanded.icon` | object | - | Icon when expanded |
| `iconPosition` | string | right | Icon position: "left" or "right" |

## Animate Blocks

### Global Animation Setting

```json
{
  "settings": {
    "custom": {
      "ignite-wp": {
        "animation": false
      }
    },
    "blocks": {
      "core/paragraph": {
        "custom": {
          "ignite-wp": {
            "animation": true
          }
        }
      }
    }
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `animation` | boolean | true | Enable/disable scroll animations |

## Carousel

### tenup/carousel

```json
{
  "settings": {
    "blocks": {
      "tenup/carousel": {
        "custom": {
          "tenup": {
            "showDots": true,
            "showArrows": true,
            "perPage": 1,
            "slideType": "slide",
            "icons": {
              "arrowNext": {
                "iconSet": "ignite-wp",
                "iconName": "chevron-right"
              },
              "arrowPrevious": {
                "iconSet": "ignite-wp",
                "iconName": "chevron-left"
              }
            }
          }
        }
      }
    }
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `showDots` | boolean | true | Show pagination dots |
| `showArrows` | boolean | true | Show navigation arrows |
| `perPage` | number | 1 | Slides visible at once |
| `slideType` | string | slide | Animation: "slide", "fade", "loop" |
| `icons.arrowNext` | object | chevron-right | Next arrow icon |
| `icons.arrowPrevious` | object | chevron-left | Previous arrow icon |

## Core Features

### Fluid Typography

```json
{
  "settings": {
    "custom": {
      "font": {
        "size": {
          "display": {
            "fluid": "true",
            "max": "var(--wp--custom--font--size--90)",
            "min": "var(--wp--custom--font--size--58)"
          }
        }
      }
    }
  }
}
```

### Fluid Spacing

```json
{
  "settings": {
    "custom": {
      "spacing": {
        "lg": {
          "fluid": "true",
          "max": "var(--wp--custom--spacing--80)",
          "min": "var(--wp--custom--spacing--40)"
        }
      }
    }
  }
}
```

### Typography Design Presets

```json
{
  "settings": {
    "custom": {
      "typography": {
        "presets": [
          {
            "slug": "display-lg",
            "name": "Display (lg)",
            "fontSize": "var(--wp--preset--font-size--display-lg)",
            "fontWeight": "var(--wp--custom--font--weight--bold)",
            "lineHeight": "1",
            "fontFamily": "var(--wp--preset--font-family--headings)"
          }
        ]
      }
    }
  }
}
```

### Enhanced Section Styles

```json
{
  "version": 3,
  "title": "Inverted",
  "slug": "section-inverted",
  "blockTypes": ["core/group"],
  "settings": {
    "custom": {
      "color": {
        "button": {
          "background": "var(--wp--custom--color--surface--primary)"
        }
      }
    }
  },
  "styles": {
    "color": {
      "background": "var(--wp--custom--color--surface--inverted)"
    }
  }
}
```

### Video Cover Controls

```json
{
  "settings": {
    "custom": {
      "tenup": {
        "enableVideoCoverControls": true
      }
    },
    "blocks": {
      "core/cover": {
        "custom": {
          "tenup": {
            "playIcon": {
              "iconSet": "ignite-wp",
              "iconName": "play"
            },
            "pauseIcon": {
              "iconSet": "ignite-wp",
              "iconName": "pause"
            }
          }
        }
      }
    }
  }
}
```

### Separator Height

```json
{
  "settings": {
    "custom": {
      "ignite-wp": {
        "separatorHeight": true
      }
    }
  }
}
```

## Modal

### tenup/modal

```json
{
  "settings": {
    "blocks": {
      "tenup/modal": {
        "custom": {
          "tenup": {
            "supportsOverlayColorPalette": true,
            "overlayColorPalette": [
              {
                "color": "#000000",
                "name": "Black",
                "slug": "black"
              },
              {
                "color": "#1a1a1a",
                "name": "Dark Gray",
                "slug": "dark-gray"
              }
            ]
          }
        }
      }
    }
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `supportsOverlayColorPalette` | boolean | false | Enable custom overlay colors |
| `overlayColorPalette` | array | - | Custom color options |

## Navigation

### tenup/site-header

```json
{
  "settings": {
    "blocks": {
      "tenup/site-header": {
        "custom": {
          "tenup": {
            "enableBackdrop": true,
            "navigationBreakpoint": "768px",
            "enableHeadroom": true,
            "headroomOptions": {
              "offset": 100,
              "tolerance": 5
            },
            "focusableSelectors": [
              "a[href]",
              "button:not([disabled])",
              "input:not([disabled])"
            ],
            "debug": false
          }
        }
      }
    }
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `enableBackdrop` | boolean | false | Show backdrop when region expanded |
| `navigationBreakpoint` | string | 768px | Mobile navigation breakpoint |
| `enableHeadroom` | boolean | false | Auto-hide header on scroll |
| `headroomOptions` | object | - | Headroom.js configuration |
| `focusableSelectors` | array | - | Focusable elements in regions |
| `debug` | boolean | false | Enable console logging |

## Tabs

### tenup/tabs

```json
{
  "settings": {
    "blocks": {
      "tenup/tabs": {
        "custom": {
          "tenup": {
            "maxNumberOfTabs": 5
          }
        }
      }
    }
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `maxNumberOfTabs` | number | - | Maximum tabs allowed |

## Timeline

### tenup/timeline-milestone

Default styling via theme.json:

```json
{
  "version": 2,
  "styles": {
    "blocks": {
      "tenup/timeline-milestone": {
        "color": {
          "background": "var(--wp--custom--color--neutrals--100)"
        }
      }
    }
  }
}
```

### CSS Custom Properties

```css
.wp-block-tenup-timeline {
  --c-timeline-line-color: var(--wp--custom--color--neutrals--700, #5f6368);
}
```
