# Quick Start Guide - Landing Pages Joomla Component

## Installation (5 minutes)

### Step 1: Install Component

1. Download `com_landingpages.zip` (already built in this directory)
2. Log in to Joomla Administrator
3. Navigate to **Extensions** → **Manage** → **Install**
4. Upload `com_landingpages.zip`
5. Wait for "Installation successful" message

### Step 2: Configure API Connection

1. Go to **Components** → **Landing Pages**
2. Click **Options** button (top right toolbar)
3. Set **API URL** to your backend URL:
   - Example: `http://localhost:8000/api`
   - Production: `https://yourserver.com/api`
4. Set **Enable Cache** to **Yes** (recommended)
5. Set **Show in Joomla Template**:
   - **No** = Standalone landing pages (recommended)
   - **Yes** = Landing pages within Joomla template
6. Click **Save & Close**

### Step 3: Create Menu Item

1. Go to **Menus** → **Main Menu** (or any menu)
2. Click **New**
3. Click **Select** next to "Menu Item Type"
4. Choose **Landing Pages** → **Single Landing Page**
5. Enter **Page Slug** (e.g., `my-landing-page`)
   - This must match the slug from your Landing Page Builder
6. Set menu title (e.g., "My Landing Page")
7. Click **Save & Close**

### Step 4: Test

1. Visit your site
2. Click the menu item you created
3. Your landing page should display!

## Common Configuration

### Standalone Mode (Full Page)

Best for landing pages that should look independent from your main site.

**Settings:**
- Show in Joomla Template: **No**
- Template Override: Not needed

**Result:** Landing page displays without Joomla header/footer/sidebar

### Integrated Mode (Within Joomla)

Best for landing pages that should match your site's look.

**Settings:**
- Show in Joomla Template: **Yes**
- Template Override: Optional (customize in your template)

**Result:** Landing page displays within your Joomla template

## Testing the Connection

### Test API Manually

```bash
# Replace with your backend URL and slug
curl http://localhost:8000/api/page/your-page-slug
```

Expected response: JSON with page data

### Common URLs

- **Direct component access:**
  `https://yoursite.com/index.php?option=com_landingpages&view=page&slug=your-slug`

- **With menu item:**
  `https://yoursite.com/your-menu-alias`

## Troubleshooting

### "API Connection Error"

**Fix:**
1. Verify backend is running: `php -S localhost:8000 -t public` (in backend dir)
2. Check API URL in component options
3. Test manually: `curl http://localhost:8000/api/pages`

### "Page not found"

**Fix:**
1. Verify page exists in Landing Page Builder
2. Check page is published
3. Verify slug matches exactly (case-sensitive)

### Page not updating

**Fix:**
1. Clear Joomla cache: **System** → **Clear Cache**
2. Select `com_landingpages` entries → Click **Delete**

### Styling looks wrong

**Fix:**
- If standalone mode: Check if TailwindCSS CDN is loading (check browser console)
- If integrated mode: May need CSS adjustments in your template

## Next Steps

- **Create multiple landing pages** - Create more menu items with different slugs
- **Customize styling** - Edit `/components/com_landingpages/assets/css/landingpage.css`
- **Template overrides** - Override templates in your Joomla template
- **Monitor forms** - Check form submissions in your Landing Page Builder backend

## Support Files

- **README.md** - Full documentation
- **build.sh** - Rebuild component package
- **com_landingpages.zip** - Installation package

## Development Workflow

1. Make changes to component files in `com_landingpages/`
2. Run `./build.sh` to create new ZIP
3. Uninstall old version in Joomla
4. Install new version

Or use **Discover** feature:
1. Copy `com_landingpages/` to `/components/com_landingpages/` and `/administrator/components/com_landingpages/`
2. Go to **Extensions** → **Manage** → **Discover**
3. Click **Discover** → Install

---

**Need help?** Check README.md for detailed documentation.
