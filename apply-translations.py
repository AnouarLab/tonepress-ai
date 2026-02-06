import os
import re
import datetime
import subprocess

# Dictionary of English -> French translations
translations = {
    "AI Content Engine": "TonePress AI",
    "TonePress AI": "TonePress AI",
    "Generate Article": "Générer un article",
    "Bulk Generation": "Génération en masse",
    "Templates": "Modèles",
    "History": "Historique",
    "Settings": "Réglages",
    "Statistics": "Statistiques",
    "Generate AI Article": "Générer un article IA",
    "Quick Presets": "Préréglages rapides",
    "Blog Post": "Article de blog",
    "How-To Guide": "Guide pratique",
    "Product Review": "Avis produit",
    "Estimated Cost:": "Coût estimé :",
    "Article Topic": "Sujet de l'article",
    "Enter the topic or title for your article...": "Entrez le sujet ou le titre de votre article...",
    "Describe the topic or subject you want the AI to write about.": "Décrivez le sujet que vous souhaitez que l'IA traite.",
    "Target Keywords": "Mots-clés cibles",
    "Article Length": "Longueur de l'article",
    "Short (800-1200 words)": "Court (800-1200 mots)",
    "Medium (1200-1800 words)": "Moyen (1200-1800 mots)",
    "Long (1800-2500+ words)": "Long (1800-2500+ mots)",
    "Writing Tone": "Ton de rédaction",
    "Professional": "Professionnel",
    "Conversational": "Conversationnel",
    "Authoritative": "Autoritaire",
    "Friendly": "Amical",
    "Academic": "Académique",
    "Output Language": "Langue de sortie",
    "English": "Anglais",
    "Spanish (Español)": "Espagnol",
    "French (Français)": "Français",
    "Custom Word Count": "Nombre de mots personnalisé",
    "Include Features": "Inclure des fonctionnalités",
    "Include Tables": "Inclure des tableaux",
    "Include Charts": "Inclure des graphiques",
    "Generate Featured Image (DALL-E)": "Générer une image à la une (DALL-E)",
    "Generate Inline Images (DALL-E)": "Générer des images intégrées (DALL-E)",
    "Advanced Options": "Options avancées",
    "Creativity (Temperature)": "Créativité (Température)",
    "Max Tokens": "Tokens max",
    "Post Type": "Type de publication",
    "Post": "Article",
    "Page": "Page",
    "Categories": "Catégories",
    "Auto-assign based on keywords": "Assignation auto basée sur les mots-clés",
    "Auto-Generate Tags": "Générer automatiquement les étiquettes",
    "Custom Instructions": "Instructions personnalisées",
    "Post Status": "Statut de la publication",
    "Save as Draft": "Enregistrer comme brouillon",
    "Publish Immediately": "Publier immédiatement",
    "Schedule for Later": "Planifier pour plus tard",
    "Publish Date": "Date de publication",
    "Welcome to TonePress AI!": "Bienvenue sur TonePress AI !",
    "Create professional blog posts in minutes with the power of AI.": "Créez des articles de blog professionnels en quelques minutes avec la puissance de l'IA.",
    "AI-Powered": "Propulsé par l'IA",
    "Choose from OpenAI, Claude, or Gemini": "Choisissez parmi OpenAI, Claude ou Gemini",
    "Brand Voice": "Voix de la marque",
    "Maintain consistent tone across all content": "Maintenez un ton cohérent sur tout le contenu",
    "SEO Optimized": "Optimisé SEO",
    "Built-in optimization for search engines": "Optimisation intégrée pour les moteurs de recherche",
    "Pre-built templates for common article types": "Modèles prédéfinis pour les types d'articles courants",
    "Setup takes approximately 3 minutes": "La configuration prend environ 3 minutes",
    "API Configuration": "Configuration API",
    "API Key": "Clé API",
    "Connection successful!": "Connexion réussie !",
    "Company Profile": "Profil de l'entreprise",
    "Company Name": "Nom de l'entreprise",
    "Industry": "Industrie",
    "Content Preferences": "Préférences de contenu",
    "First Article": "Premier article",
    "Complete": "Terminé",
    "TonePress AI - Setup Wizard": "TonePress AI - Assistant de configuration",
    "Step %1$d of %2$d": "Étape %1$d sur %2$d",
    "← Back": "← Retour",
    "Get Started →": "Commencer →",
    "Continue →": "Continuer →",
    "Skip setup": "Passer la configuration",
    "Go to Dashboard →": "Aller au tableau de bord →",
    "Go to Dashboard": "Aller au tableau de bord",
    "Save Settings": "Enregistrer les réglages",
    "Upload CSV File": "Télécharger un fichier CSV",
    "Download Template": "Télécharger le modèle",
    "Queue ID": "ID de file",
    "Status": "Statut",
    "Progress": "Progression",
    "Actions": "Actions",
    "Pause": "Pause",
    "Resume": "Reprendre",
    "Delete": "Supprimer",
    "Export Results": "Exporter les résultats",
    "Title": "Titre",
    "Type": "Type",
    "Date": "Date",
    "Tokens": "Tokens",
    "Cost": "Coût",
    "View": "Voir",
    "Edit": "Modifier",
    "Template Name": "Nom du modèle",
    "Topic Pattern": "Modèle de sujet",
    "Saved Templates": "Modèles enregistrés",
    "Welcome to AI Content Engine!": "Bienvenue sur TonePress AI !", # Fallback
}

def apply_translations(pot_file, po_file):
    with open(pot_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace header info
    content = content.replace('Content-Type: text/plain; charset=UTF-8', 'Content-Type: text/plain; charset=UTF-8')
    content = content.replace('Language: \n', 'Language: fr_FR\n')

    # Iterate over translations
    # Simple replace is dangerous because msgid might match parts of other strings
    # We need to parse msgid blocks.
    
    # Python's re matching for msgid "..."
    # pattern: msgid "(.*?)"\nmsgstr ""
    
    def replace_func(match):
        msgid = match.group(1)
        if msgid in translations:
            return f'msgid "{msgid}"\nmsgstr "{translations[msgid]}"'
        return match.group(0)

    # Regex to handle multiline msgid is complex.
    # We'll stick to single line or simple cases for now as our POT generator likely outputs single lines for simple strings.
    new_content = re.sub(r'msgid "(.*?)"\nmsgstr ""', replace_func, content)
    
    with open(po_file, 'w', encoding='utf-8') as f:
        f.write(new_content)

if __name__ == "__main__":
    pot_path = 'languages/tonepress-ai.pot'
    po_path = 'languages/tonepress-ai-fr_FR.po'
    
    print("Applying translations...")
    apply_translations(pot_path, po_path)
    print(f"Created {po_path}")
    
    # Compile
    mo_path = 'languages/tonepress-ai-fr_FR.mo'
    try:
        subprocess.run(['msgfmt', po_path, '-o', mo_path], check=True)
        print(f"Compiled to {mo_path}")
    except Exception as e:
        print(f"Error compiling MO: {e}")
