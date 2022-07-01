import polib
from const import *


def po2mo_plugin_php():
    po = polib.pofile(PLUGIN_PHP_LANGUAGE_FILES_PATH + f'/{PLUGIN_PHP_LANGUAGE_FILES_PREFIX}-ja.po')
    po.save_as_mofile(PLUGIN_PHP_LANGUAGE_FILES_PATH + f'/{PLUGIN_PHP_LANGUAGE_FILES_PREFIX}-ja.mo')

    print("created peachpay-for-woocommerce/languages/peachpay-for-woocommerce.mo")

if __name__ == "__main__":
    po2mo_plugin_php()