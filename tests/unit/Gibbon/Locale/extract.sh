#!/bin/bash

# This script generates / update
# the translation files for tests within this folder.

# ensure that realpath exists in the environment
# (compatibility with macos)
if ! which realpath 2>&1 >/dev/null; then
    # declare a bash function which functions as
    # normal realpath in bash
    function realpath() {
        [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
    }
fi

# generates a pot file from the given information
function genTemplate {

    # $1    is the target POT file.
    # $2... is the pattern to all source code file(s) (e.g. *.php).
    PACKAGE_NAME=$1
    shift
    PACKAGE_VERSION=$1
    shift
    POT=$1
    shift

    # extract raw text from source file
    xgettext \
        --package-name="$PACKAGE_NAME" \
        --package-version="$PACKAGE_VERSION" \
        --language=PHP \
        --from-code=UTF-8 \
        --add-comments=L10N \
        --keyword=translate:1 \
        --keyword=translateN:1,2 \
        --keyword=__:1 \
        --keyword=__n:1,2 \
        -o "$POT" \
        "$@"

    # set UTF-8 as default charset
    sed -i \
        's/charset=CHARSET/charset=UTF-8/' \
        "$POT"
}

# generates a locale from the given pot
function genLocale {

    # $1 is the POT file to use.
    # $2 is the base folder to generate in (without trailing slash).
    # $3 is the locale code of locale to generate.
    # $4 is the domain of translation to generate.

    # create the locale folder, if not exists
    if [ ! -d "$2/$3/LC_MESSAGES" ]; then
        mkdir -p "$2/$3/LC_MESSAGES"
    fi

    # create or update po file
    if [ ! -f "$2/$3/LC_MESSAGES/$4.po" ]; then
        msginit \
            --locale=$3 \
            --no-translator \
            -i $1 \
            -o $2/$3/LC_MESSAGES/$4.po
    else
        msgmerge \
            --update \
            $2/$3/LC_MESSAGES/$4.po \
            $1
    fi

    # (re)generate mo file from po
    msgfmt --check-header --check-domain -v \
        -o $2/$3/LC_MESSAGES/$4.mo \
        $2/$3/LC_MESSAGES/$4.po
}

# configs
DOMAIN="gibbon"
PACKAGE_NAME="gibbon"
PACKAGE_VERSION="v17.0.00"
declare -a LOCALES=(
    "zh_TW"
    "es_ES"
)

#
# main
#

set -x

PWD_RETURN=$PWD
BASE_DIR=$(realpath $(dirname $0))

cd $BASE_DIR

# extract raw text from source file
genTemplate \
    "$PACKAGE_NAME" \
    "$PACKAGE_VERSION" \
    "mock/i18n/$DOMAIN.pot" \
    *.php

for LOCALE in "${LOCALES[@]}"
do
    # generate locale from given information
    genLocale \
        "mock/i18n/$DOMAIN.pot" \
        "mock/i18n" \
        "$LOCALE" \
        "$DOMAIN"
done

cd $PWD_RETURN
