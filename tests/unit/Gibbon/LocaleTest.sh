#!/bin/bash

# This script generates / update
# the translation files for LocalTest.php

DOMAIN="gibbon"
TEST_LOCALE="zh_TW"

set -x

# extract raw text from source file
xgettext \
	--language=PHP \
	--from-code=UTF-8 \
	--add-comments=L10N \
	--keyword=translate:1 \
	--keyword=__:1 \
	-o mock/i18n/gibbon.pot \
	LocaleTest.php

# set UTF-8 as default charset
sed -i \
	's/CHARSET/UTF-8/' \
	mock/i18n/$DOMAIN.pot

# create or update po file
if [ ! -f "mock/i18n/$TEST_LOCALE/LC_MESSAGES/$DOMAIN.po" ]; then
	msginit \
		--locale=$TEST_LOCALE \
		--no-translator \
		-i mock/i18n/gibbon.pot \
		-o mock/i18n/$TEST_LOCALE/LC_MESSAGES/$DOMAIN.po
else
	msgmerge \
		mock/i18n/$TEST_LOCALE/LC_MESSAGES/$DOMAIN.po \
		mock/i18n/gibbon.pot
fi

# (re)generate mo file from po
msgfmt --check-header --check-domain -v \
	-o mock/i18n/$TEST_LOCALE/LC_MESSAGES/$DOMAIN.mo \
	mock/i18n/$TEST_LOCALE/LC_MESSAGES/$DOMAIN.po
