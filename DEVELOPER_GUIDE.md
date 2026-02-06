# AI Content Engine - Developer Guide

**For developers working on the AI Content Engine codebase**

---

## 🚀 Quick Start for Developers

### 1. Clone & Setup
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone [your-repo] tonepress-ai
cd tonepress-ai
npm install
```

### 2. Build Assets
```bash
# Development mode (watch for changes)
npm run start

# Production build
npm run build
```

### 3. Activate Plugin
- Go to WordPress Dashboard → Plugins
- Activate "AI Content Engine"

---

## 📁 Project Structure

```
tonepress-ai/
├── tonepress-ai.php          # Main plugin file
├── includes/                       # PHP backend classes
│   ├── class-admin-ui.php         # Admin interface & REST API
│   ├── class-article-generator.php # Content generation logic
│   ├── class-chat-builder.php     # Chat system
│   ├── class-ai-provider.php      # Abstract provider base
│   ├── class-provider-*.php       # AI provider implementations
│   └── Chat/                      # Chat system components
├── src/                           # Frontend source (React/JS)
│   ├── index.js                   # Entry point
│   ├── blocks/                    # Gutenberg blocks
│   └── plugins/                   # Editor plugins
├── build/                         # Compiled assets (generated)
├── assets/                        # Admin CSS/JS
└── templates/                     # PHP templates
```

---

## 🛠️ Development Tools

### NPM Scripts

```bash
# Development
npm run start        # Watch mode with hot reload
npm run build        # Production build
npm run packages-update  # Update @wordpress packages

# Linting (if configured)
# npm run lint:js
# npm run lint:css
```

### Build Process

**Webpack Configuration**: `webpack.config.js`
- Extends `@wordpress/scripts` default config
- Entry point: `src/index.js`
- Output: `build/blocks/ai-content-generator/`

**What Gets Built**:
```
src/blocks/ai-content-generator/
  ├── index.js       →  build/blocks/.../index.js
  ├── edit.js        →  (bundled)
  ├── save.js        →  (bundled)
  └── editor.css     →  build/blocks/.../index.css
```

---

## 🏗️ Architecture

### Backend (PHP)

#### Provider System (NEW - Use This!)

**Abstract Base**:
```php
// includes/class-ai-provider.php
abstract class AI_Provider {
    abstract public function generate_content($system, $user, $options);
    abstract public function test_connection();
    abstract public function estimate_cost($tokens, $model);
}
```

**Implementations**:
```php
// includes/class-provider-openai.php
class Provider_OpenAI extends AI_Provider { }

// includes/class-provider-claude.php  
class Provider_Claude extends AI_Provider { }

// includes/class-provider-gemini.php
class Provider_Gemini extends AI_Provider { }
```

**Usage**:
```php
use ACE\Provider_Factory;

// Get active provider from settings
$provider = Provider_Factory::get_active();

// Get specific provider
$provider = Provider_Factory::get('openai');

// Generate content
$result = $provider->generate_content($sys_prompt, $user_prompt, $options);
```

#### REST API Endpoints

Defined in `class-admin-ui.php::register_rest_routes()`:

```php
POST /wp-json/ace/v1/generate
  - Generate article content
  - Body: { topic, keywords, length, tone, ... }

POST /wp-json/ace/v1/chat/start
  - Start new chat session
  - Body: { model }

POST /wp-json/ace/v1/chat/message
  - Send message to chat
  - Body: { session_id, message }

POST /wp-json/ace/v1/actions
  - Inline text actions (placeholder)
  - Body: { action, text }
```

**Permission**: All require `edit_posts` capability

### Frontend (React)

#### Block Structure

**Block Registration** (`src/blocks/ai-content-generator/index.js`):
```javascript
import { registerBlockType } from '@wordpress/blocks';
import { starFilled as icon } from '@wordpress/icons';
import edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType(metadata.name, {
    icon,
    edit,
    save,
});
```

**Edit Component** (`src/blocks/ai-content-generator/edit.js`):
- Uses `@wordpress/components` for UI
- Calls REST API via `@wordpress/api-fetch`
- Manages state with `useState`
- Stores generated content in block attributes

**Save Component** (`src/blocks/ai-content-generator/save.js`):
```javascript
// Returns null for dynamic block (rendered server-side)
export default function Save() {
    return null;
}
```

#### Editor Plugins

**Chat Sidebar** (`src/plugins/chat-sidebar/index.js`):
```javascript
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';

registerPlugin('ace-chat-sidebar', {
    icon: starFilled,
    render: () => (
        <PluginSidebar name="ace-chat-sidebar" title="AI Chat">
            <ChatInterface />
        </PluginSidebar>
    ),
});
```

**Inline Copilot** (`src/plugins/inline-copilot/index.js`):
```javascript
import { registerFormatType } from '@wordpress/rich-text';

registerFormatType('ace/inline-copilot', {
    title: 'AI Copilot',
    edit: AICopilotButton,  // Dropdown with actions
});
```

---

## 🔧 Common Development Tasks

### Add New AI Provider

1. **Create Provider Class**:
```php
// includes/class-provider-newai.php
namespace ACE;

class Provider_NewAI extends AI_Provider {
    protected $provider_id = 'newai';
    protected $name = 'New AI';
    protected $api_endpoint = 'https://api.newai.com/v1/generate';
    
    protected function get_api_key_option() {
        return 'ace_newai_api_key';
    }
    
    public function get_available_models() {
        return [
            'model-1' => 'Model 1',
            'model-2' => 'Model 2',
        ];
    }
    
    public function generate_content($sys, $usr, $opts = []) {
        // Implementation
    }
    
    public function test_connection() {
        // Implementation
    }
    
    public function estimate_cost($tokens, $model = null) {
        // Implementation
    }
}
```

2. **Register in Factory** (`includes/class-provider-factory.php`):
```php
public static function get_available_providers() {
    return [
        'openai' => 'OpenAI',
        'claude' => 'Claude (Anthropic)',
        'gemini' => 'Google Gemini',
        'newai' => 'New AI',  // Add this
    ];
}
```

3. **Add to Admin UI** (`includes/class-admin-ui.php`):
```php
// Add settings fields for API key and models
```

### Add New Gutenberg Block

1. **Create Block Directory**:
```bash
mkdir -p src/blocks/my-new-block
```

2. **Create Files**:
```javascript
// src/blocks/my-new-block/index.js
import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import save from './save';

registerBlockType('ace/my-new-block', {
    title: 'My New Block',
    category: 'widgets',
    icon: 'star-filled',
    edit,
    save,
});

// src/blocks/my-new-block/edit.js
export default function Edit({ attributes, setAttributes }) {
    return <div>Edit view</div>;
}

// src/blocks/my-new-block/save.js
export default function Save() {
    return null;  // or JSX for static save
}
```

3. **Import in Main Entry**:
```javascript
// src/index.js
import './blocks/my-new-block';
```

4. **Build**:
```bash
npm run build
```

### Add REST API Endpoint

1. **Register Route** (`includes/class-admin-ui.php`):
```php
public function register_rest_routes() {
    register_rest_route('ace/v1', '/my-endpoint', [
        'methods'             => 'POST',
        'callback'            => [$this, 'rest_my_endpoint'],
        'permission_callback' => [$this, 'rest_permission_check'],
    ]);
}
```

2. **Implement Callback**:
```php
public function rest_my_endpoint($request) {
    $params = $request->get_json_params();
    
    // Your logic here
    
    return rest_ensure_response([
        'success' => true,
        'data'    => $result,
    ]);
}
```

3. **Call from Frontend**:
```javascript
import apiFetch from '@wordpress/api-fetch';

const result = await apiFetch({
    path: '/ace/v1/my-endpoint',
    method: 'POST',
    data: { /* your data */ },
});
```

---

## 🧪 Testing

### Manual Testing Checklist

**After Code Changes**:
```bash
# 1. PHP syntax check
php -l includes/class-*.php

# 2. Rebuild assets
npm run build

# 3. Test in WordPress
- Activate plugin
- Check PHP errors (debug.log)
- Test in Gutenberg editor
- Check browser console (F12)
```

**Block Testing**:
- [ ] Block appears in inserter
- [ ] Block UI renders correctly
- [ ] API calls work
- [ ] Content saves properly
- [ ] No JavaScript errors

**REST API Testing**:
```bash
# Using curl
curl -X POST http://localhost/wp-json/ace/v1/generate \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{"topic": "Test"}'
```

---

## 🐛 Debugging

### Enable WordPress Debug Mode

**wp-config.php**:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Plugin-Specific Debug

**wp-config.php**:
```php
define('ACE_DEBUG', true);
```

This enables additional logging in `OpenAI_Client` and other classes.

### Check Logs

```bash
# WordPress debug log
tail -f wp-content/debug.log

# PHP error log (location varies)
tail -f /var/log/php_errors.log
```

### Browser Console

**JavaScript Errors**:
- Open DevTools (F12)
- Check Console tab
- Look for red errors

**Network Requests**:
- Network tab
- Filter by `wp-json`
- Check request/response

### Verify Nonce

**In Browser Console**:
```javascript
// Check if nonce is available
console.log(aceEditorData.nonce);

// Check REST URL
console.log(aceEditorData.apiUrl);
```

---

## 📦 Building for Distribution

### Create Distribution Zip

```bash
./create-zip.sh
```

**What it does**:
1. Runs `npm run build`
2. Creates zip with:
   - All PHP files ✅
   - `build/` directory ✅
   - `assets/` directory ✅
   - Excludes: `src/`, `node_modules/`, dev files ❌

**Output**: `tonepress-ai-v2.1.0.zip`

### Manual Build Process

```bash
# 1. Install dependencies
npm install

# 2. Build production assets
npm run build

# 3. Test plugin
# - Upload to test site
# - Activate and test all features

# 4. Create clean zip
cd ..
zip -r tonepress-ai.zip tonepress-ai \
    -x "*node_modules/*" \
    -x "*src/*" \
    -x "*.git/*"
```

---

## 🔐 Security Guidelines

### Input Sanitization

**Always sanitize user input**:
```php
// Text fields
$value = sanitize_text_field($_POST['field']);

// Textareas
$value = sanitize_textarea_field($_POST['field']);

// Arrays
$value = array_map('sanitize_text_field', $_POST['array']);
```

### Nonce Verification

**AJAX Requests**:
```php
check_ajax_referer('ace_generate_nonce', 'nonce');
```

**REST API**:
```php
public function rest_permission_check() {
    return current_user_can('edit_posts');
}
```

**Forms**:
```php
// Generate
wp_nonce_field('ace_save_settings', 'ace_nonce');

// Verify
if (!wp_verify_nonce($_POST['ace_nonce'], 'ace_save_settings')) {
    wp_die('Invalid nonce');
}
```

### API Key Storage

**Always encrypt**:
```php
use ACE\Security;

// Save
$encrypted = Security::encrypt_api_key($api_key);
update_option('ace_api_key', $encrypted);

// Retrieve
$encrypted = get_option('ace_api_key');
$api_key = Security::decrypt_api_key($encrypted);
```

---

## 📚 Code Standards

### PHP

- **PSR-4 Autoloading**: `namespace ACE;`
- **WordPress Coding Standards**: Use tabs, follow WP conventions
- **DocBlocks**: All classes and public methods
- **Type Hints**: Use when possible (PHP 7.0+)

**Example**:
```php
/**
 * Generate article content.
 *
 * @param string $topic   Article topic.
 * @param array  $options Generation options.
 * @return int|WP_Error Post ID or error.
 */
public function generate_article($topic, $options = []) {
    // Implementation
}
```

### JavaScript

- **ES6+**: Use modern syntax
- **WordPress Components**: When in Gutenberg
- **React Hooks**: `useState`, `useEffect`
- **Type Safety**: Consider PropTypes or TypeScript

**Example**:
```javascript
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

export default function MyComponent({ initialValue }) {
    const [value, setValue] = useState(initialValue);
    
    return (
        <Button onClick={() => setValue(value + 1)}>
            Count: {value}
        </Button>
    );
}
```

---

## 🔄 Git Workflow

### Branches

```bash
main          # Production-ready code
develop       # Integration branch
feature/*     # New features
bugfix/*      # Bug fixes
```

### Commit Messages

```
feat: Add new provider support for X
fix: Correct API endpoint path
docs: Update developer guide
refactor: Migrate to provider architecture
chore: Update dependencies
```

### Before Committing

```bash
# 1. Run build
npm run build

# 2. Check PHP syntax
find includes -name "*.php" -exec php -l {} \;

# 3. Git add
git add .

# 4. Commit
git commit -m "feat: Add feature X"
```

---

## 🆘 Common Issues

### "Block not found"

**Fix**: Rebuild assets
```bash
npm run build
# Clear browser cache
```

### "API request failed"

**Check**:
1. Nonce is valid
2. User has `edit_posts` capability
3. Endpoint registered correctly
4. Check browser Network tab

### "Syntax error in PHP"

**Debug**:
```bash
php -l includes/problematic-file.php
```

### "Webpack build fails"

**Solutions**:
```bash
# Clear cache
rm -rf node_modules package-lock.json
npm install

# Check Node version (need v18+)
node --version

# Try clean build
npm run build
```

---

## 📖 Further Reading

### WordPress Development

- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [REST API Handbook](https://developer.wordpress.org/rest-api/)

### React/Gutenberg

- [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts)
- [Gutenberg Components](https://wordpress.github.io/gutenberg/)
- [Block API Reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/)

### Project Files

- `README.md` - Plugin overview
- `QUICK_START.md` - User guide
- `EXAMPLES.php` - Code examples
- `DEPRECATED_CODE_AUDIT.md` - Migration guide

---

## ✅ Development Checklist

Before pushing code:

- [ ] PHP files have no syntax errors
- [ ] JavaScript builds without warnings
- [ ] All new REST endpoints have permission checks
- [ ] User input is sanitized
- [ ] API keys are encrypted
- [ ] Code follows WordPress standards
- [ ] Tested in actual WordPress environment
- [ ] Browser console has no errors
- [ ] Documentation updated if needed

---

**Ready to Develop!** 🚀

For user-facing documentation, see `QUICK_START.md`.
