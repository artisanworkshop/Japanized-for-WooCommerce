import os
import re
import requests
import json
import sys
import argparse

from utils import parse_po, parse_pot, get_localization_source_dir, get_language_files_dir, get_language_files_prefix
from dotenv import load_dotenv
from datetime import datetime
from const import *

load_dotenv()

GOOGLE_TRANSLATE_API_KEY = os.getenv("GOOGLE_TRANSLATE_API_KEY", None)

def generate_pot(localization_type, display_strings):
    header = f'msgid ""\n\
msgstr ""\n\
"MIME-Version: 1.0\\n"\n\
"Content-Type: text/plain; charset=UTF-8\\n"\n\
"Content-Transfer-Encoding: 8bit\\n"\n\
"POT-Creation-Date: {datetime.now().isoformat()}\\n"\n\
"X-Domain: peachpay-for-woocommerce\\n"'

    language_file_dir, localization_type_prefix = get_language_files_dir(localization_type), get_language_files_prefix(localization_type)

    with open(language_file_dir + f'/{localization_type_prefix}.pot', "w") as output_file:
        output_file.writelines(header + "\n\n")

        for msgid in sorted(list(display_strings)):
            escaped_msgid = re.sub('"', r'\"', msgid)

            output_file.writelines(f'msgid "{escaped_msgid}"\n')
            output_file.writelines("msgstr \"\"\n\n")

def get_translation(strings, target_lang):
    url = f'https://translation.googleapis.com/language/translate/v2?key={GOOGLE_TRANSLATE_API_KEY}'
    data = {
        "q": strings if type(strings) == list else [strings],
        "source": "en",
        "target": target_lang,
        "format": "text"
    }

    try:
        response = requests.post(url, data).text
        translations = []

        for phrase in json.loads(response)["data"]["translations"]:
            translations.append(phrase["translatedText"])

    except Exception as e:
        print("Problem with auto translation response: " + response)
        exit()

    return translations

def generate_po(localization_type, display_strings, lang):
    header = f'msgid ""\n\
msgstr ""\n\
"MIME-Version: 1.0\\n"\n\
"Content-Type: text/plain; charset=UTF-8\\n"\n\
"Content-Transfer-Encoding: 8bit\\n"\n\
"Language: {lang}\\n"\n'

    language_file_dir, localization_type_prefix = get_language_files_dir(localization_type), get_language_files_prefix(localization_type)

    po_file_path = language_file_dir + f'/{localization_type_prefix}-{lang}.po'

    translations = {}
    
    if not os.path.isfile(po_file_path): # if .po does not exist, then do a batch translation of all display strings
        translated_terms = get_translation(display_strings, lang)

        for msgid, msgstr in zip(display_strings, translated_terms):
            translations[msgid] = msgstr
    else: # else grab already translated terms from .po file and only auto translate as needed
        translated_terms = parse_po(po_file_path)

        for msgid in display_strings:
            translations[msgid] = translated_terms[msgid] if (msgid in translated_terms) else get_translation(msgid, lang)[0]

    with open(language_file_dir + f'/{localization_type_prefix}-{lang}.po', "w") as output_file:
        output_file.writelines(header + "\n\n")

        for msgid, msgstr in sorted(translations.items()):
            escaped_msgid = re.sub('"', r'\"', msgid)
            escaped_msgstr = re.sub('"', r'\"', msgstr)

            output_file.writelines(f'msgid "{escaped_msgid}"\n')
            output_file.writelines(f'msgstr "{escaped_msgstr}"\n\n')

def process_extracted_display_strings(localization_type, display_strings_found):
        language_file_dir, localization_type_prefix = get_language_files_dir(localization_type), get_language_files_prefix(localization_type)

        current_pot_path = language_file_dir + f'/{localization_type_prefix}.pot'
        current_pot_msgid_strings = set() if not os.path.isfile(current_pot_path) else parse_pot(current_pot_path)

        if display_strings_found != current_pot_msgid_strings:
            strings_added = display_strings_found.difference(current_pot_msgid_strings)
                
            print("added " + str(len(strings_added)) + " strings: ")
            print(str(strings_added))

            strings_removed = current_pot_msgid_strings.difference(display_strings_found)
            print("removed " + str(len(strings_removed)) + " strings: ")
            print(str(strings_removed))

            if len(strings_added) > 0 and not GOOGLE_TRANSLATE_API_KEY:
                sys.exit("ERROR: need GOOGLE_TRANSLATE_API_KEY to add modal strings")

            generate_pot(localization_type, display_strings_found)
        else:
            print("no modifications to the display strings")

        # want to respect order when generating language files (especially when requesting/writing batch translations)
        display_strings_found = list(display_strings_found)

        print("generating .po files")

        if localization_type == LOCALIZATION_TYPES.PLUGIN_PHP:
            generate_po(localization_type, display_strings_found, 'ja')
        else:
            for lang in LANGUAGES:
                generate_po(localization_type, display_strings_found, lang)

def extract_displayed_strings(localization_type, dir_path):
    """Recursively searches all files in the
    directory and retuns a set of strings that will be
    displayed and thus need translations

    Parameters
    ----------
    localization_type : enum
        the type of location that is being done

    dir_path : str
        the curreny path being searched

    Returns
    -------
    set
        the set of strings that are displayed for a given localization type
    """

    print("searching " + os.path.relpath(dir_path))

    display_strings_found = set()    

    if localization_type == LOCALIZATION_TYPES.CHECKOUT_MODAL:
        gettext_match_pattern = [TYPESCRIPT_PATTERN, HTML_PATTERN]
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_JS:
        gettext_match_pattern = [PLUGIN_JS_PATTERN]
    else:
        gettext_match_pattern = [PLUGIN_PHP_PATTERN_1, PLUGIN_PHP_PATTERN_2, PLUGIN_PHP_PATTERN_3]

    dir_contents = os.listdir(dir_path)
    
    for entry in dir_contents:
        entry_path = dir_path + "/" + entry

        if os.path.isdir(entry_path) and entry not in DIR_TO_IGNORE:
            display_strings_found = display_strings_found.union(extract_displayed_strings(localization_type, entry_path))
        else:

            try:
                textfile = open(entry_path, 'r')
                filetext = textfile.read()
                textfile.close()

                matches = []
                for pattern in gettext_match_pattern:
                    matches += re.findall(pattern, filetext)

                display_strings = [group[1] for group in matches] # grab the 2nd subgroup from the matches
                display_strings_found = display_strings_found.union(set(display_strings))
            except:
                continue

    return display_strings_found    

def update_checkout_modal():
    print("updating checkout modal language files")

    display_strings_found = extract_displayed_strings(LOCALIZATION_TYPES.CHECKOUT_MODAL, get_localization_source_dir(LOCALIZATION_TYPES.CHECKOUT_MODAL))
    process_extracted_display_strings(LOCALIZATION_TYPES.CHECKOUT_MODAL, display_strings_found)

def update_plugin_js():
    print("updating plugin js language files")

    display_strings_found = extract_displayed_strings(LOCALIZATION_TYPES.PLUGIN_JS, get_localization_source_dir(LOCALIZATION_TYPES.PLUGIN_JS))
    process_extracted_display_strings(LOCALIZATION_TYPES.PLUGIN_JS, display_strings_found)

def update_plugin_php():
    print("updating plugin php language files")

    display_strings_found = extract_displayed_strings(LOCALIZATION_TYPES.PLUGIN_PHP, get_localization_source_dir(LOCALIZATION_TYPES.PLUGIN_PHP))
    process_extracted_display_strings(LOCALIZATION_TYPES.PLUGIN_PHP, display_strings_found)

if __name__ == "__main__":
    parser = argparse.ArgumentParser()

    parser.add_argument("--checkout-modal", help="update language files for the checkout modal", action="store_true")
    parser.add_argument("--plugin-js", help="update language files for the plugin js", action="store_true")
    parser.add_argument("--plugin-php", help="update language files for the plugin php", action="store_true")
    parser.add_argument("--all", help="update language files for checkout moda, plugin js, and plugin php", action="store_true")

    args = parser.parse_args()

    if args.checkout_modal or args.all:
        update_checkout_modal()

    if args.plugin_js or args.all:
        update_plugin_js()
    
    if args.plugin_php or args.all:
        update_plugin_php()