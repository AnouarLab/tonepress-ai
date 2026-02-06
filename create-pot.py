import os
import re
import datetime

def scan_files(root_dir):
    php_files = []
    for root, dirs, files in os.walk(root_dir):
        if 'node_modules' in dirs:
            dirs.remove('node_modules')
        if '.git' in dirs:
            dirs.remove('.git')
        for file in files:
            if file.endswith('.php'):
                php_files.append(os.path.join(root, file))
    return php_files

def extract_strings(files):
    # Pattern to capture: function_name( 'string', 'domain' ) or "string"
    # Handling: __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e
    # Note: 'domain' must be 'tonepress-ai'
    
    # This regex is simplified and might miss complex cases (e.g. variables), but good for standard usage
    pattern = re.compile(r"""
        \b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)  # Function name
        \s*\(\s*                                                  # Opening paren and whitespace
        (['"])(.*?)(?<!\\)\1                                      # Quote, String, specific Quote matching
        \s*,\s*                                                   # Comma
        ['"]tonepress-ai['"]                                      # Domain
    """, re.VERBOSE | re.DOTALL)

    strings = {} # "msgid": [ (file, line) ]

    for file_path in files:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            # We iterate line by line for line numbers, but regex needs full content for multiline?
            # Actually, let's keep it simple: read line by line.
            # But multiline strings exist.
            # Let's read full content and manually find line numbers if needed, or just list file.
            
            # Re-reading line by line for simpler regex application per line (assuming no multiline function calls for now)
            # Refined approach: Read whole file, find iter matches.
            
            for match in pattern.finditer(content):
                msgid = match.group(2)
                # Unescape php single quotes if needed
                msgid = msgid.replace(r"\'", "'").replace(r'\"', '"')
                
                # Find line number
                line_number = content.count('\n', 0, match.start()) + 1
                
                # Relative path
                rel_path = os.path.relpath(file_path, os.getcwd())
                
                if msgid not in strings:
                    strings[msgid] = []
                strings[msgid].append(f"{rel_path}:{line_number}")

    return strings

def write_pot(strings, output_file):
    with open(output_file, 'w', encoding='utf-8') as f:
        # Header
        f.write('msgid ""\n')
        f.write('msgstr ""\n')
        f.write('"Project-Id-Version: TonePress AI 2.1.0\\n"\n')
        f.write('"MIME-Version: 1.0\\n"\n')
        f.write('"Content-Type: text/plain; charset=UTF-8\\n"\n')
        f.write('"Content-Transfer-Encoding: 8bit\\n"\n')
        f.write(f'"POT-Creation-Date: {datetime.datetime.now().strftime("%Y-%m-%d %H:%M%z")}\\n"\n')
        f.write('"X-Generator: Custom Python Script\\n"\n')
        f.write('\n')

        for msgid in sorted(strings.keys()):
            for location in strings[msgid]:
                f.write(f"#: {location}\n")
            f.write(f'msgid "{msgid}"\n')
            f.write('msgstr ""\n')
            f.write('\n')

if __name__ == "__main__":
    files = scan_files('.')
    print(f"Scanning {len(files)} PHP files...")
    strings = extract_strings(files)
    print(f"Found {len(strings)} unique translatable strings.")
    
    output_dir = 'languages'
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)
        
    write_pot(strings, os.path.join(output_dir, 'tonepress-ai.pot'))
    print("POT file generated.")
