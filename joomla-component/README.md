# Landing Pages Component for Joomla

This Joomla component acts as a proxy to display landing pages created with the Landing Page Builder application.

## Features

- **API Integration**: Fetches landing page data from remote Landing Page Builder backend
- **SEO Optimized**: Full meta tag support (title, description, Open Graph)
- **Caching**: Built-in caching for better performance
- **Flexible Display**: Can display within Joomla template or standalone
- **Block Rendering**: PHP renderers for all block types (Hero, Text, Form, Footer, etc.)
- **Form Submissions**: Handles lead form submissions through the API
- **Menu Integration**: Create menu items pointing to specific landing pages

## Installation

### 1. Package the Component

Create a ZIP file of the component:

```bash
cd joomla-component
zip -r com_landingpages.zip com_landingpages/
```

### 2. Install in Joomla

1. Log in to your Joomla administrator panel
2. Go to **Extensions** → **Manage** → **Install**
3. Upload the `com_landingpages.zip` file
4. Wait for installation to complete

### 3. Configure the Component

1. Go to **Components** → **Landing Pages**
2. Click the **Options** button in the toolbar
3. Configure the following settings:

   - **API URL**: Full URL to your Landing Page Builder API (e.g., `http://yourserver.com:8000/api`)
   - **API Token**: (Optional) Authentication token if required
   - **Enable Cache**: Enable/disable caching (recommended: Yes)
   - **Cache Time**: How long to cache pages in seconds (default: 300)
   - **Show in Joomla Template**:
     - **Yes**: Landing page will be displayed within your Joomla template (header/footer/sidebar)
     - **No**: Landing page will be displayed standalone (full page, no Joomla chrome)

4. Click **Save & Close**

## Usage

### Creating Menu Items

1. Go to **Menus** → Select your menu (e.g., Main Menu)
2. Click **New** to create a new menu item
3. Click **Select** next to Menu Item Type
4. Choose **Landing Pages** → **Single Landing Page**
5. In the **Page Slug** field, enter the slug of your landing page (e.g., `my-landing-page`)
6. Configure other menu settings as needed (title, alias, etc.)
7. Click **Save & Close**

### URL Structure

Once configured, your landing pages will be accessible at URLs like:

- With menu item: `https://yoursite.com/menu-alias`
- Direct component access: `https://yoursite.com/index.php?option=com_landingpages&view=page&slug=your-page-slug`

## Architecture

### Component Structure

```
com_landingpages/
├── site/                           # Frontend files
│   ├── landingpages.php           # Entry point
│   ├── controller.php             # Main controller
│   ├── models/
│   │   └── page.php               # Page model (fetches from API)
│   ├── views/
│   │   └── page/
│   │       ├── view.html.php      # View class (SEO handling)
│   │       ├── tmpl/
│   │       │   └── default.php    # Template for rendering
│   │       └── metadata.xml       # Menu item parameters
│   ├── helpers/
│   │   ├── landingpages.php       # API client
│   │   └── blockrenderer.php     # Block rendering logic
│   └── assets/
│       └── css/
│           └── landingpage.css    # Styling
└── admin/                          # Backend files
    ├── landingpages.php           # Admin entry point
    ├── config.xml                 # Component configuration
    └── language/                  # Language files
```

### API Integration

The component uses the `LandingPagesHelper` class to communicate with your backend:

- **GET** `/api/page/{slug}` - Fetch landing page data
- **POST** `/api/leads` - Submit form leads

### Block Rendering

The `BlockRenderer` class converts block JSON data to HTML. Supported block types:

- **hero** - Hero section with title, subtitle, CTA button
- **text** - Simple text block
- **two-column-text-image** - Text left, image right
- **two-column-image-text** - Image left, text right
- **form** - Lead capture form
- **footer** - Footer with company info

### Caching

When enabled, the component caches API responses using Joomla's cache system:

- Cache key: `page_{md5(slug)}`
- Cache group: `com_landingpages`
- Cache lifetime: Configurable (default: 300 seconds)

To clear cache:
1. Go to **System** → **Clear Cache**
2. Select `com_landingpages` entries
3. Click **Delete**

## Form Submissions

Form blocks automatically submit to the backend API. The flow is:

1. User fills form on landing page
2. Form submits to `index.php?option=com_landingpages&task=submitLead`
3. Controller validates and sends data to API endpoint `/api/leads`
4. User receives success/error message
5. User is redirected back to the landing page

## Customization

### Styling

To customize the appearance:

1. **Using Joomla template**: Edit your Joomla template CSS
2. **Standalone mode**: Edit `/components/com_landingpages/assets/css/landingpage.css`

The component uses TailwindCSS classes. If displaying standalone, Tailwind is loaded from CDN.

### Template Overrides

You can override the component templates in your Joomla template:

1. Copy `/components/com_landingpages/views/page/tmpl/default.php`
2. Paste to `/templates/YOUR_TEMPLATE/html/com_landingpages/page/default.php`
3. Modify as needed

### Custom Block Renderers

To modify how blocks are rendered, edit:
`/components/com_landingpages/helpers/blockrenderer.php`

Each block type has its own `render{BlockType}()` method.

## Troubleshooting

### "Page not found" error

- Check that the slug in your menu item matches exactly the slug in your backend
- Verify the page is published in your Landing Page Builder
- Check API URL configuration in component options

### "API Connection Error"

- Verify the API URL is correct and accessible from your Joomla server
- Check firewall settings if backend is on a different server
- Test API endpoint manually: `curl http://yourserver:8000/api/page/your-slug`

### Pages not updating

- Clear Joomla cache: **System** → **Clear Cache**
- Reduce cache time in component options for development
- Disable cache temporarily in component options

### Styling issues

- If using standalone mode, ensure Tailwind CDN is loading (check browser console)
- If using template mode, check for CSS conflicts with Joomla template
- Use browser developer tools to inspect element styles

## Security

- API token support for authenticated requests
- CSRF protection on form submissions
- Email validation on lead forms
- XSS protection via `htmlspecialchars()` on all rendered content
- SQL injection prevention (no direct database queries)

## Requirements

- Joomla 4.0 or higher
- PHP 7.4 or higher
- Access to Landing Page Builder API
- cURL or allow_url_fopen enabled in PHP

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify your Landing Page Builder backend is running
3. Check Joomla error logs
4. Review API endpoint responses

## License

GNU General Public License version 2 or later
