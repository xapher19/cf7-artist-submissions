# CF7 Artist Submissions - Update System Setup

## GitHub Repository Configuration

The automatic update system is now configured to work with the GitHub repository:
**https://github.com/xapher19/cf7-artist-submissions**

## How to Create Releases for Updates

### 1. Prepare for Release
- Ensure all changes are committed and pushed to the main branch
- Update version number in `cf7-artist-submissions.php` header
- Update version constant `CF7_ARTIST_SUBMISSIONS_VERSION` in main plugin file
- Update README.md changelog if needed

### 2. Create GitHub Release
1. Go to: https://github.com/xapher19/cf7-artist-submissions/releases
2. Click "Create a new release"
3. **Tag version**: Use format `v1.0.1`, `v1.0.2`, etc. (must start with 'v')
4. **Release title**: Use format like "Version 1.0.1 - Bug Fixes"
5. **Description**: Add changelog/release notes
6. Click "Publish release"

### 3. Automatic Update Process
- WordPress sites will check for updates every 12 hours
- Users will see update notifications in their WordPress dashboard
- Updates can be installed through standard WordPress update interface
- Manual update checks available in plugin settings

## Update System Features

### For Site Administrators:
- **Automatic Checks**: Updates checked every 12 hours
- **Dashboard Integration**: Updates appear in WordPress admin like other plugins
- **Manual Checks**: Force update check in plugin settings
- **Version Display**: Current and available versions shown in settings
- **One-Click Updates**: Standard WordPress update process

### For Developers:
- **GitHub Integration**: Pulls releases from GitHub API
- **Version Comparison**: Semantic version comparison
- **Caching**: 12-hour cache to prevent excessive API calls
- **Error Handling**: Graceful fallbacks if GitHub is unavailable
- **Security**: WordPress nonce verification for all update operations

## Settings Page Integration

The update system includes a dedicated "Updates" tab in the plugin settings:

**Location**: WordPress Admin → Artist Submissions → Settings → Updates

**Features**:
- Current version display
- Update availability status
- Manual update checking
- Repository information
- System status monitoring

## Troubleshooting

### Updates Not Showing
1. Check GitHub repository has releases with proper version tags
2. Verify version tags start with 'v' (e.g., v1.0.1)
3. Clear update cache in plugin settings
4. Check WordPress site can access GitHub API

### Manual Update Check
1. Go to plugin Settings → Updates tab
2. Click "Check for Updates" button
3. System will clear cache and check immediately
4. Refresh page to see results

## Version Tag Format

**Required Format**: `v[MAJOR].[MINOR].[PATCH]`

**Examples**:
- ✅ `v1.0.0` - Initial release
- ✅ `v1.0.1` - Patch release
- ✅ `v1.1.0` - Minor release
- ✅ `v2.0.0` - Major release
- ❌ `1.0.1` - Missing 'v' prefix
- ❌ `version-1.0.1` - Wrong format

## Security

- All update checks use WordPress nonce verification
- Updates go through standard WordPress security validation
- No custom file modification outside WordPress core processes
- Plugin updates maintain all user data and settings

---

**The update system is now fully integrated and ready for use!** 🎉
