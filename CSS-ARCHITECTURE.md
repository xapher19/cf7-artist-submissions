# CF7 Artist Submissions - CSS Architecture Documentation

## Overview
The plugin now uses a hierarchical CSS architecture with shared components and specialized files for better maintainability and performance.

## File Structure

### 🏗️ Foundation Layer
- **common.css** (558 lines) - Shared components used across all interfaces
  - Button system (.cf7-btn variants)
  - Modal dialogs (.cf7-modal system)
  - Gradient headers (.cf7-gradient-header)
  - Navigation tabs (.cf7-nav-tab system)
  - Form components (.cf7-form-* classes)
  - Loading animations and responsive breakpoints

### 🎯 Interface-Specific Layers
- **admin.css** (2,686 lines) - Core admin functionality
- **dashboard.css** (3,228 lines) - Dashboard interface
- **settings.css** (544 lines) - Settings interface
- **conversations.css** (1,000 lines) - Conversation system
- **tabs.css** (1,455 lines) - Tabbed artist interface
- **actions.css** (933 lines) - Action management
- **lightbox.css** (125 lines) - Media lightbox

## WordPress Enqueue Strategy

### Dependency Management
All specialized CSS files depend on `common.css` to ensure proper load order:

```php
wp_enqueue_style('cf7-common-css', '...common.css', array(), VERSION);
wp_enqueue_style('cf7-dashboard-css', '...dashboard.css', array('cf7-common-css'), VERSION);
```

### Loading Context

| Page/Context | CSS Files Loaded |
|--------------|-------------------|
| **Settings Page** | common.css → settings.css → admin.css |
| **Dashboard** | common.css → dashboard.css |
| **Submission List** | common.css → admin.css |
| **Single Submission** | common.css → tabs.css, conversations.css, actions.css, lightbox.css, admin.css |

## HTML Class Usage

### Shared Components (from common.css)

#### Buttons
```html
<button class="cf7-btn cf7-btn-primary">Primary Button</button>
<button class="cf7-btn cf7-btn-secondary">Secondary Button</button>
<button class="cf7-btn cf7-btn-ghost">Ghost Button</button>
<button class="cf7-btn cf7-btn-icon">Icon Button</button>
```

#### Headers
```html
<div class="cf7-gradient-header cf7-header-context">
    <div class="cf7-header-content">
        <h1 class="cf7-header-title">Page Title</h1>
        <p class="cf7-header-subtitle">Subtitle</p>
    </div>
    <div class="cf7-header-actions">
        <!-- Buttons here -->
    </div>
</div>
```

#### Navigation Tabs
```html
<nav class="cf7-settings-nav">
    <div class="cf7-nav-tabs">
        <a href="#" class="cf7-nav-tab active">Tab 1</a>
        <a href="#" class="cf7-nav-tab">Tab 2</a>
    </div>
</nav>
```

#### Modals
```html
<div class="cf7-modal" id="modal-id">
    <div class="cf7-modal-content">
        <div class="cf7-modal-header">
            <h3>Modal Title</h3>
            <button class="cf7-modal-close">&times;</button>
        </div>
        <div class="cf7-modal-body">
            <!-- Content -->
        </div>
        <div class="cf7-modal-footer">
            <button class="cf7-modal-btn cf7-btn-primary">Save</button>
            <button class="cf7-modal-btn cf7-btn-secondary">Cancel</button>
        </div>
    </div>
</div>
```

#### Form Components
```html
<div class="cf7-form-group">
    <label class="cf7-form-label">Field Label</label>
    <input type="text" class="cf7-form-input" />
</div>
```

## Benefits of New Architecture

### ✅ Performance
- Reduced duplicate code (500+ lines eliminated)
- Smaller individual files load faster
- Better browser caching with shared common.css

### ✅ Maintainability  
- Single source of truth for shared components
- Clear separation of concerns
- Easy to update design system globally

### ✅ Consistency
- Unified button system across all interfaces
- Consistent modal behavior
- Standardized responsive breakpoints

### ✅ Developer Experience
- Clear documentation of dependencies
- Logical file organization
- Easy to find and modify specific functionality

## Implementation Notes

### For Theme/Plugin Developers
1. Always enqueue `common.css` first when adding custom CSS
2. Use provided CSS classes for consistency
3. Extend common components rather than recreating them

### For Customization
1. Override specific components in your theme CSS
2. Use CSS custom properties for color/spacing changes
3. Maintain the hierarchical loading order

### WordPress Integration
- All files properly registered with WordPress dependency system
- Version strings ensure cache invalidation on updates
- Follows WordPress CSS enqueue best practices

## File Modification Guidelines

### When to Edit common.css
- Adding/modifying shared button variants
- Updating modal system behavior  
- Changing global responsive breakpoints
- Adding new shared form components

### When to Edit Specialized Files
- Interface-specific styling (dashboard metrics, settings toggles, etc.)
- Page-specific responsive adjustments
- Feature-specific components

## Migration Notes

### From Previous Version
- No breaking changes to existing HTML structure
- All existing CSS classes continue to work
- Templates automatically benefit from shared components
- Performance improvements are automatic

### Future Updates
- New features should use common.css components when possible
- Add specialized CSS to appropriate interface-specific file
- Maintain dependency declarations in PHP enqueue calls
