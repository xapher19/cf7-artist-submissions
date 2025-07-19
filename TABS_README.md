# CF7 Artist Submissions - Tabbed Interface

## Overview
The CF7 Artist Submissions plugin now features a modern tabbed interface for managing artist submissions. This improves organization and reduces clutter on the admin pages.

## Tab Structure

### 1. Profile Tab
- **Submission Details**: All form field data from the artist
- **Curator Notes**: Private notes visible only to administrators
- Editable fields (click to edit inline)
- Two-column layout for better organization

### 2. Submitted Works Tab
- **File Gallery**: All uploaded artwork and portfolio files
- **Thumbnail Preview**: Images display with lightbox functionality
- **File Management**: View, download, and organize submitted files
- **File Status**: Shows file size and availability

### 3. Conversations Tab
- **Email Thread**: Complete conversation history with the artist
- **Template Integration**: Send templated emails directly from the interface
- **Message Management**: Two-way email communication
- **Visual Differentiation**: Template messages appear in green

## Features

### Tab Navigation
- Persistent tab state (remembers last active tab)
- URL hash support for direct tab linking
- Responsive design for mobile devices
- Icon-based navigation with clear labels

### AJAX Loading
- Dynamic content loading for better performance
- Smooth transitions between tabs
- Loading states and error handling
- Content caching for improved speed

### Integration
- Maintains all existing functionality
- Backwards compatible with existing data
- Preserves saving and validation logic
- Works with existing JavaScript features

## Technical Implementation

### Files Added/Modified
- `includes/class-cf7-artist-submissions-tabs.php` - Main tabbed interface class
- `assets/css/tabs.css` - Tab styling and responsive design
- `assets/js/tabs.js` - Tab functionality and state management
- Modified main plugin file to initialize tabs system

### Dependencies
- jQuery (WordPress core)
- Existing CF7 Artist Submissions classes
- WordPress meta box system
- AJAX handlers for dynamic loading

### Customization
The tabbed interface can be customized through:
- CSS modifications in `assets/css/tabs.css`
- JavaScript enhancements in `assets/js/tabs.js`
- PHP hooks and filters in the tabs class
- WordPress admin styling overrides

## Browser Support
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design for tablets and mobile
- Graceful degradation for older browsers
- Touch-friendly interface for mobile devices

## Performance Notes
- Lazy loading of tab content reduces initial page load
- Content is cached after first load
- Minimal JavaScript footprint
- CSS optimized for fast rendering
