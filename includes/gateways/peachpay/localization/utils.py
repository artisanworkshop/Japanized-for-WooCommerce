import re

from const import *

def get_localization_source_dir(localization_type):
    if localization_type == LOCALIZATION_TYPES.CHECKOUT_MODAL:
        return CHECKOUT_MODAL_SOURCE_DIR_PATH
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_JS:
        return PLUGIN_JS_SOURCE_DIR_PATH
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_PHP:
        return PLUGIN_PHP_SOURCE_DIR_PATH
    else:
        raise Exception("not valid localization type")

def get_language_files_dir(localization_type):
    if localization_type == LOCALIZATION_TYPES.CHECKOUT_MODAL:
        return CHECKOUT_MODAL_LANGUAGE_FILES_PATH
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_JS:
        return PLUGIN_JS_LANGUAGE_FILES_PATH
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_PHP:
        return PLUGIN_PHP_LANGUAGE_FILES_PATH
    else:
        raise Exception("not valid localization type")

def get_language_files_prefix(localization_type):
    if localization_type == LOCALIZATION_TYPES.CHECKOUT_MODAL:
        return CHECKOUT_MODAL_LANGUAGE_FILES_PREFIX
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_JS:
        return PLUGIN_JS_LANGUAGE_FILES_PREFIX
    elif localization_type == LOCALIZATION_TYPES.PLUGIN_PHP:
        return PLUGIN_PHP_LANGUAGE_FILES_PREFIX
    else:
        raise Exception("not valid localization type")

# google translate uses ISO 639-1 code, different from Wordpress locale
def ISO_639_1_to_wp_locale(language_code):
    if language_code == "de":
        return "de-DE"
    elif language_code == "es":
        return "es-ES"
    elif language_code == "fr":
        return "fr-FR"
    elif language_code == "ro":
        return "ro-RO"
    elif language_code == "cs":
        return "cs-CZ"
    elif language_code == "da":
        return "da-DK"
    elif language_code == "hi":
        return "hi-IN"
    elif language_code == "ko":
        return "ko-KR"
    elif language_code == "lb":
        return "lb-LU"
    elif language_code == "nl":
        return "nl-NL"
    elif language_code == "pt":
        return "pt-PT"
    elif language_code == "ru":
        return "ru-RU"
    elif language_code == "sl":
        return "sl-SI"
    elif language_code == "sv":
        return "sv-SE"
    elif language_code == "bg":
        return "bg-BG"
    else: # wp locale is same as ISO 639-1
        return language_code


def parse_pot(pot_file_path):
    """Reads a .pot file and returns a set
    containing all msgid mapped to
    their corresponding msgstr

    Parameters
    ----------
    pot_file_path : str
        the location of the .po file

    Returns
    -------
    set
        contains msgid
    """

    pot_msgid_pattern = re.compile(r"msgid \"(.*)\"")

    msgids = set()

    textfile = open(pot_file_path, 'r')
    filetext = textfile.read()
    textfile.close()

    msgids = [re.sub(r'\\"', '"', msgid) for msgid in re.findall(pot_msgid_pattern, filetext)]

    msgids = set(msgids)
    msgids.discard("")

    return msgids

def parse_po(po_file_path):
    """Reads a .po file and returns a dictionary
    containing all msgid mapped to
    their corresponding msgstr

    Parameters
    ----------
    po_file_path : str
        the location of the .po file

    Returns
    -------
    dict
        contains msgid mapped to msgtr
    """

    terms = {}
    with open(po_file_path) as file:
        for line in file:
            line1 = re.match(r"msgid \"(.*)\"", line)

            if line1:
                line2 = re.match(r"msgstr \"(.*)\"", next(file))

                unescaped_msgid = re.sub(r'\\"', '"', line1.group(1))
                unescaped_msgstr = re.sub(r'\\"', '"', line2.group(1))

                terms[unescaped_msgid] = unescaped_msgstr

    terms.pop("")
    return terms