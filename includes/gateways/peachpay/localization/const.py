import re

from pathlib import Path
from enum import Enum

# ISO 639-1 language codes for Peachpay supported languages
LANGUAGES = [
        'de', # German
        'es', # Spanish
        'fr', # French
        'it', # Italy
        'ja', # Japanese
        'ro', # Romanian
        'ar', # Arabic
        'ca', # Catalan
        'cs', # Czech
        'da', # Danish
        'el', # Greek
        'hi', # Hindi
        'ko', # Korean
        'lb', # Luxembourgish
        'nl', # Dutch
        'pt', # Portuguese
        'ru', # Russian
        'sl', # Slovenian
        'sv', # Swedish
        'th', # Thai
        'uk', # Ukranian
        'zh-CN', # Chinese (Simplified)
        'zh-TW', # Chinese (Traditional)
        'bg', # Bulgarian
]

LOCALIZATION_TYPES = Enum("LOCALIZATION_TYPES", "CHECKOUT_MODAL PLUGIN_JS PLUGIN_PHP")

DIR_TO_IGNORE = {"node_modules", "cov_profile", "dist", "localization"}

################# checkout modal #####################

CHECKOUT_MODAL_SOURCE_DIR_PATH = str((Path(__file__).parent.parent / "lib/checkout-modal/src"))
CHECKOUT_MODAL_LANGUAGE_FILES_PATH = str((Path(__file__).parent / "./checkout-modal-language-files"))
CHECKOUT_MODAL_LANGUAGE_FILES_PREFIX = "checkout-modal"
CHECKOUT_MODAL_TRANSLATED_TERMS_PATH = str((Path(__file__).parent.parent / "lib/checkout-modal/src/ts/util/translated-terms.ts"))

TYPESCRIPT_PATTERN = re.compile(r"getLocaleText\(\s*([\'\"])(.*?)\1\s*\)")
HTML_PATTERN = re.compile(r"data-i18n\s*=\s*([\'\"])(.*?)\1")

################# plugin js #####################

PLUGIN_JS_SOURCE_DIR_PATH = str((Path(__file__).parent.parent)) # PeachPay plugin directory
PLUGIN_JS_LANGUAGE_FILES_PATH = str((Path(__file__).parent / "./plugin-js-language-files"))
PLUGIN_JS_LANGUAGE_FILES_PREFIX = "plugin-js"
PLUGIN_JS_TRANSLATED_TERMS_PATH = str((Path(__file__).parent.parent / "public/js/translated-terms.js"))

PLUGIN_JS_PATTERN = re.compile(r"getPluginLocaleText\(\s*([\'\"])(.*?)\1\s*\)")

################# plugin js #####################
 # WordPress has specific rules for localization names/file paths
 
PLUGIN_PHP_SOURCE_DIR_PATH = str((Path(__file__).parent.parent)) # PeachPay plugin directory
PLUGIN_PHP_LANGUAGE_FILES_PATH = str((Path(__file__).parent.parent / "languages"))
PLUGIN_PHP_LANGUAGE_FILES_PREFIX = "peachpay-for-woocommerce"
PLUGIN_PHP_PATTERN_1 = re.compile(r"__\(\s*([\'\"])(.*?)\1\s*,\s*([\'\"])peachpay-for-woocommerce\3\s*\)")
PLUGIN_PHP_PATTERN_2 = re.compile(r"esc_html_e\(\s*([\'\"])(.*?)\1\s*,\s*([\'\"])peachpay-for-woocommerce\3\s*\)")
PLUGIN_PHP_PATTERN_3 = re.compile(r"esc_attr_e\(\s*([\'\"])(.*?)\1\s*,\s*([\'\"])peachpay-for-woocommerce\3\s*\)")