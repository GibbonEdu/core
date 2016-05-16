cd /Applications/MAMP/htdocs/github_gibbonEdu/core/
echo 'en_GB'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/en_GB/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/en_GB/LC_MESSAGES/gibbon.mo ./i18n/en_GB/LC_MESSAGES/gibbon.po
echo 'zh_CN'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/zh_CN/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/zh_CN/LC_MESSAGES/gibbon.mo ./i18n/zh_CN/LC_MESSAGES/gibbon.po
echo 'zh_HK'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/zh_HK/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/zh_HK/LC_MESSAGES/gibbon.mo ./i18n/zh_HK/LC_MESSAGES/gibbon.po
echo 'en_US'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/en_US/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/en_US/LC_MESSAGES/gibbon.mo ./i18n/en_US/LC_MESSAGES/gibbon.po
echo 'pl_PL'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/pl_PL/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/pl_PL/LC_MESSAGES/gibbon.mo ./i18n/pl_PL/LC_MESSAGES/gibbon.po
echo 'it_IT'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/it_IT/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/it_IT/LC_MESSAGES/gibbon.mo ./i18n/it_IT/LC_MESSAGES/gibbon.po
echo 'es_ES'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/es_ES/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/es_ES/LC_MESSAGES/gibbon.mo ./i18n/es_ES/LC_MESSAGES/gibbon.po
echo 'id_ID'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/id_ID/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/id_ID/LC_MESSAGES/gibbon.mo ./i18n/id_ID/LC_MESSAGES/gibbon.po
echo 'ar_SA'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/ar_SA/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ar_SA/LC_MESSAGES/gibbon.mo ./i18n/ar_SA/LC_MESSAGES/gibbon.po
echo 'fr_FR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/fr_FR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/fr_FR/LC_MESSAGES/gibbon.mo ./i18n/fr_FR/LC_MESSAGES/gibbon.po
echo 'sw_KE'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/sw_KE/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/sw_KE/LC_MESSAGES/gibbon.mo ./i18n/sw_KE/LC_MESSAGES/gibbon.po
echo 'pt_PT'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/pt_PT/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/pt_PT/LC_MESSAGES/gibbon.mo ./i18n/pt_PT/LC_MESSAGES/gibbon.po
echo 'ro_RO'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/ro_RO/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ro_RO/LC_MESSAGES/gibbon.mo ./i18n/ro_RO/LC_MESSAGES/gibbon.po
echo 'ur_IN'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/ur_IN/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ur_IN/LC_MESSAGES/gibbon.mo ./i18n/ur_IN/LC_MESSAGES/gibbon.po
echo 'ja_JP'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/ja_JP/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ja_JP/LC_MESSAGES/gibbon.mo ./i18n/ja_JP/LC_MESSAGES/gibbon.po
echo 'ru_RU'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/ru_RU/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ru_RU/LC_MESSAGES/gibbon.mo ./i18n/ru_RU/LC_MESSAGES/gibbon.po
echo 'uk_UA'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/uk_UA/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/uk_UA/LC_MESSAGES/gibbon.mo ./i18n/uk_UA/LC_MESSAGES/gibbon.po
echo 'bn_BD'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/bn_BD/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/bn_BD/LC_MESSAGES/gibbon.mo ./i18n/bn_BD/LC_MESSAGES/gibbon.po
echo 'da_DK'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/da_DK/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/da_DK/LC_MESSAGES/gibbon.mo ./i18n/da_DK/LC_MESSAGES/gibbon.po
echo 'fa_IR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/fa_IR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/fa_IR/LC_MESSAGES/gibbon.mo ./i18n/fa_IR/LC_MESSAGES/gibbon.po
echo 'pt_BR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/pt_BR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/pt_BR/LC_MESSAGES/gibbon.mo ./i18n/pt_BR/LC_MESSAGES/gibbon.po
echo 'nl_NL'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/nl_NL/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/nl_NL/LC_MESSAGES/gibbon.mo ./i18n/nl_NL/LC_MESSAGES/gibbon.po
echo 'ke_GE'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/ke_GE/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ke_GE/LC_MESSAGES/gibbon.mo ./i18n/ke_GE/LC_MESSAGES/gibbon.po
echo 'hu_HU'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:2 -o ./i18n/hu_HU/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/hu_HU/LC_MESSAGES/gibbon.mo ./i18n/hu_HU/LC_MESSAGES/gibbon.po