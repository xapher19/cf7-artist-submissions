# Form Takeover Feature

The CF7 Artist Submissions plugin now includes a **Form Takeover** feature that provides a guided, multi-step submission experience.

## How to Enable

Add the `takeover` option to your uploader shortcode in Contact Form 7:

```
[uploader your-work takeover]
```

## What It Does

When form takeover is enabled:

1. **Replaces the entire form** with a single "Submit My Work" button
2. **Opens a full-screen modal** when clicked with a 3-step process:
   - **Step 1: Your Details** - Form fields extracted from your original CF7 form
   - **Step 2: Upload Works** - Drag-and-drop file uploader interface  
   - **Step 3: Review & Submit** - Summary of details and files before final submission

## User Experience

- Modern, gradient-styled interface
- Progress indicators showing current step
- Form validation before proceeding to next step
- File upload monitoring (continue button only appears when all files are uploaded)
- Success confirmation popup after submission

## Technical Notes

- All original form fields are preserved and submitted
- Files are uploaded to S3 as normal
- Form submission follows standard CF7 processing
- Modal is fully responsive and accessible
- Clean CSS animations and transitions

## Example CF7 Form

```
<label> Your Name (required) [text* your-name] </label>
<label> Your Email (required) [email* your-email] </label>
<label> Artist Statement [textarea artist-statement] </label>
[uploader your-work takeover]
[submit "Submit"]
```

The submit button will be replaced automatically when takeover mode is enabled.
