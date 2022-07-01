import argparse
import json
from utils import parse_po, ISO_639_1_to_wp_locale

from const import *

def po2json_checkout_modal():
    translations = {}

    for lang in LANGUAGES:
        translations[ISO_639_1_to_wp_locale(lang)] = parse_po(CHECKOUT_MODAL_LANGUAGE_FILES_PATH + f'/{CHECKOUT_MODAL_LANGUAGE_FILES_PREFIX}-{lang}.po') 

    json_object = json.dumps(translations, indent=4, sort_keys=True, ensure_ascii=False)

    translated_terms_header = """import { IDictionary } from \'../models/types.ts\';

type ILanguageTranslation = Record<string, IDictionary>;

const peachpayi18n: ILanguageTranslation = \n"""

    translated_terms_footer = "\n\nexport { peachpayi18n };"

    with open(CHECKOUT_MODAL_TRANSLATED_TERMS_PATH, "w") as output_file:
        output_file.write(translated_terms_header)
        output_file.write(json_object)
        output_file.write(translated_terms_footer)

    print("created peachpay-for-woocommerce/lib/checkout-modal/src/ts/util/translated-terms.ts")


def po2json_plugin_js():
    translations = {}

    for lang in LANGUAGES:
        translations[ISO_639_1_to_wp_locale(lang)] = parse_po(PLUGIN_JS_LANGUAGE_FILES_PATH + f'/{PLUGIN_JS_LANGUAGE_FILES_PREFIX}-{lang}.po') 

    json_object = json.dumps(translations, indent=4, sort_keys=True, ensure_ascii=False)

    translated_terms_header = """// deno-fmt-ignore-file
    // deno-lint-ignore-file
    const PEACHPAY_PLUGIN_i18n = """

    with open(PLUGIN_JS_TRANSLATED_TERMS_PATH, "w") as output_file:
        output_file.write(translated_terms_header)
        output_file.write(json_object)

    print("created peachpay-for-woocommerce/public/js/translated-terms.js")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()

    parser.add_argument("--checkout-modal", help="converts .po file to json for the checkout modal",
                        action="store_true")
    parser.add_argument("--plugin-js", help="converts .po file to json for the plugin js",
                        action="store_true")
    parser.add_argument("--all", help="converts .po file to json for the checkout modal and plugin js",
                        action="store_true")

    args = parser.parse_args()

    if args.checkout_modal or args.all:
        po2json_checkout_modal()

    if args.plugin_js or args.all:
        po2json_plugin_js()