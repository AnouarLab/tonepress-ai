# AI Content Engine - Quick Start Guide

**Get started with AI-powered content in 3 steps!**

---

## Step 1: Install & Activate

### Option A: Upload ZIP File (Recommended)
1. Go to **WordPress Dashboard** → **Plugins** → **Add New**
2. Click **Upload Plugin**
3. Choose `ai-content-engine-v2.1.0.zip`
4. Click **Install Now**
5. Click **Activate**

### Option B: Manual FTP Upload
1. Unzip `ai-content-engine-v2.1.0.zip`
2. Upload the `ai-content-engine` folder to `/wp-content/plugins/`
3. Go to **Plugins** → **Activate** AI Content Engine

---

## Step 2: Configure API Keys

1. Go to **Dashboard** → **AI Content Engine** → **Settings**

2. **Choose your AI provider** (pick one or use multiple):

   ### OpenAI (Recommended for beginners)
   - Sign up at [platform.openai.com](https://platform.openai.com)
   - Go to API Keys section
   - Create new secret key
   - Paste it into **OpenAI API Key** field
   - **Model**: Select `gpt-4o` or `gpt-4o-mini`

   ### Claude (Good for long content)
   - Sign up at [console.anthropic.com](https://console.anthropic.com)
   - Generate API key
   - Paste into **Claude API Key** field
   - **Model**: Select `claude-3-5-sonnet-20241022`

   ### Google Gemini (Free tier available)
   - Get key from [aistudio.google.com](https://aistudio.google.com)
   - Paste into **Gemini API Key** field
   - **Model**: Select `gemini-1.5-pro`

3. Click **Save Changes**

4. Click **Test API Connection** to verify it works ✅

---

## Step 3: Create Content!

You now have **3 ways** to generate AI content:

---

## 🎨 Method 1: AI Content Generator Block

**Best for**: Creating full articles from scratch

### How to Use:

1. **Create/Edit a Post or Page**
   - Click **+ Add Block**
   - Search for **"AI Content Generator"**
   - Click to insert the block

2. **Fill in the Form:**
   ```
   Topic: "10 Benefits of Regular Exercise"
   Keywords: health, fitness, wellness
   Length: Medium (1200-1800 words)
   Tone: Professional
   ```

3. **Customize in Sidebar** (optional):
   - Include Data Tables: ✓
   - Include Charts: ✓

4. **Click "Generate Content"**
   - Wait 10-30 seconds (depending on length)
   - Content appears with preview

5. **Review & Use:**
   - Check the suggested title
   - Review the generated content
   - Click **Regenerate** if you want a different version
   - Content saves automatically when you save the post

### Tips:
- Be specific with your topic for better results
- Add 3-5 keywords for SEO optimization
- Use "Long" length for comprehensive guides
- Include tables/charts for data-heavy topics

---

## 💬 Method 2: AI Chat Builder Sidebar

**Best for**: Conversational content creation with back-and-forth refinement

### How to Use:

1. **Open the Editor**
   - Create or edit any post/page
   
2. **Open Chat Sidebar:**
   - Click the **three dots (⋯)** in top right
   - Select **"AI Chat Builder"**
   - Or look for the **star icon** ⭐ in the sidebar

3. **Start a Conversation:**
   ```
   You: "Write an introduction about sustainable living"
   AI: [Generates intro]
   
   You: "Make it more engaging and add statistics"
   AI: [Refines content]
   
   You: "Now write 3 main points about reducing waste"
   AI: [Generates bullet points]
   ```

4. **Insert Content:**
   - Click **"Insert into Post"** button
   - Content is added at your cursor position

### Tips:
- Start broad, then get specific
- Ask for revisions: "Make it shorter", "Add examples", "Change tone"
- Build content iteratively
- Perfect for outlining articles step-by-step

---

## ⚡ Method 3: Inline AI Copilot

**Best for**: Quick text improvements and refinements

### How to Use:

1. **Select Text** in your post:
   - Highlight any paragraph or sentence

2. **Click the Star Icon** ⭐ in the toolbar
   - A dropdown menu appears

3. **Choose an Action:**
   - **Rewrite** - Different phrasing, same meaning
   - **Expand** - Add more details and depth
   - **Summarize** - Condense to key points
   - **Change Tone** - Make it more formal/casual
   - **Translate** - Convert to another language

4. **AI Processes:**
   - Your text is improved instantly
   - Result replaces the selected text

### Tips:
- Use "Expand" to hit word count targets
- Use "Summarize" for meta descriptions
- Use "Rewrite" to improve clarity
- Use "Change Tone" to match your audience

---

## 📊 Admin Dashboard Method

**Best for**: Bulk content generation or creating drafts outside the editor

### How to Use:

1. Go to **Dashboard** → **AI Content Engine**

2. Fill in the **Generate Article** form:
   ```
   Article Topic: Your topic here
   Keywords: keyword1, keyword2, keyword3
   Length: Short/Medium/Long
   Writing Tone: Professional/Conversational/etc.
   ```

3. **Advanced Options** (expand section):
   - Post Status: Draft/Publish
   - Include Featured Image: ✓
   - Auto-generate tags: ✓
   - Custom instructions: (optional)

4. Click **Generate Article**
   - Progress bar shows AI is working
   - Article is created as a draft
   - You're redirected to edit it

### Tips:
- This creates a complete post in WordPress
- Perfect for batch content creation
- Review and edit before publishing
- Add your own images and personal touches

---

## 🎯 Best Practices

### ✅ Do's:
- **Be specific** with topics and keywords
- **Review all content** before publishing
- **Add your own voice** - edit generated content
- **Include personal examples** and insights
- **Check facts** - AI can make mistakes
- **Use SEO keywords** naturally
- **Test different AI providers** for your needs

### ❌ Don'ts:
- Don't publish without reading
- Don't ignore factual accuracy
- Don't over-rely on AI - add human touch
- Don't forget to add images manually
- Don't skip meta descriptions (use Summarize!)
- Don't use generic topics - be specific

---

## 🔧 Troubleshooting

### "Block won't generate content"
**Check:**
1. API key is saved and tested ✓
2. You have internet connection
3. You filled in the Topic field
4. Check browser console for errors (F12)

### "Content is generic/low quality"
**Improve by:**
1. Being more specific with topic
2. Adding detailed keywords
3. Trying a different AI provider
4. Using longer length setting
5. Adding custom instructions

### "API error / Rate limit"
**Solution:**
1. Check your AI provider's usage limits
2. Wait a few minutes and try again
3. Upgrade your AI provider plan if needed
4. Rate limits reset hourly/daily

### "Content is in wrong language"
**Fix:**
1. Add to topic: "Write in [Language]"
2. Or in custom instructions: "IMPORTANT: Write entire article in English"

---

## 💡 Pro Tips

### Get the Best Results:

1. **Combine Methods:**
   - Use block to generate main content
   - Use chat to add specific sections
   - Use copilot to polish final draft

2. **Iterate:**
   - Generate → Review → Refine → Regenerate
   - Use chat to ask for specific improvements
   - Don't settle for first draft

3. **SEO Optimization:**
   - Include target keywords in topic
   - Use "Professional" or "Authoritative" tone for SEO
   - Enable "Include Tables" for featured snippets
   - Use summarize for meta descriptions

4. **Save Time:**
   - Create content templates in custom instructions
   - Save frequently used keywords
   - Batch generate multiple articles from dashboard

5. **Quality Over Quantity:**
   - Longer articles ≠ better articles
   - Focus on value and accuracy
   - Add your expertise and personality

---

## 📈 Example Workflow

**Creating a Complete Blog Post:**

1. **Research** (you do this)
   - Identify topic: "How to Start a Podcast"
   - Research keywords: podcasting, audio, equipment

2. **Generate Outline** (Chat Sidebar)
   ```
   "Create an outline for a beginner's guide to starting a podcast"
   ```

3. **Generate Main Content** (AI Block)
   - Topic: "Complete Beginner's Guide to Starting a Podcast in 2026"
   - Keywords: podcast, podcasting, microphone, hosting
   - Length: Long
   - Include: Tables ✓

4. **Refine Sections** (Chat Sidebar)
   ```
   "Expand the equipment section with specific product recommendations"
   "Add a FAQ section with 5 common questions"
   ```

5. **Polish** (Inline Copilot)
   - Select intro → "Make it more engaging"
   - Select technical parts → "Simplify for beginners"

6. **Add Your Touch** (Manual)
   - Add personal experiences
   - Insert images and screenshots
   - Add affiliate links (if relevant)
   - Write custom conclusion

7. **Publish!** ✅

---

## 🆘 Need Help?

- **Documentation**: Check `GUTENBERG_SETUP.md`
- **Examples**: See `EXAMPLES.php` for code usage
- **Support**: Contact plugin author
- **Debug**: Enable WordPress debug mode to see detailed errors

---

## 🚀 Next Steps

Now that you know how to use the tools:

1. ✅ Generate your first article
2. ✅ Experiment with different tones and lengths
3. ✅ Try all three methods to see what fits your workflow
4. ✅ Compare AI providers (OpenAI vs Claude vs Gemini)
5. ✅ Create your content calendar and start generating!

**Happy Content Creating!** 🎉
