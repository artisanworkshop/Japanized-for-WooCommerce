import argparse
import sys

from update_language_files import update_checkout_modal, update_plugin_js, update_plugin_php
from po2json import po2json_checkout_modal, po2json_plugin_js
from po2mo import po2mo_plugin_php

def localize_checkout_modal():
    update_checkout_modal()
    po2json_checkout_modal()

def localize_plugin_js():
    update_plugin_js()
    po2json_plugin_js()

def localize_plugin_php():
    update_plugin_php()
    po2mo_plugin_php()

if __name__ == "__main__":
    parser = argparse.ArgumentParser()

    parser.add_argument("--checkout-modal", help="run localization for strings displayed in checkout modal",
                        action="store_true")
    parser.add_argument("--plugin-js", help="run localization for displayed strings in plugin javascript (alerts, admin dashboard js)",
                        action="store_true")
    parser.add_argument("--plugin-php", help="run localization for displayed strings in plugin php",
                        action="store_true")
    parser.add_argument("--all", help="run localization for displayed strings in checkout modal, plugin js, and plugin php",
                        action="store_true")

    args = parser.parse_args()

    # no argument  was given
    if len(sys.argv) == 1:
        print("""1) checkout modal \n2) plugin javascript (alerts, admin dashboard js) \n3) plugin php\n4) all\n""")
        resp = int(input("choose localization type (quit q):"))

        if resp == 1:
            localize_checkout_modal()
        elif resp == 2:
            localize_plugin_js()
        elif resp == 3:
            localize_plugin_php()
        elif resp == 4:
            localize_checkout_modal()
            localize_plugin_js()
            localize_plugin_php()
    else:
        if args.checkout_modal or args.all:
            localize_checkout_modal()

        if args.plugin_js or args.all:
            localize_plugin_js()

        if args.plugin_php or args.all:
            localize_plugin_php()