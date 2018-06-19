cd /Applications/MAMP/htdocs/github_gibbonEdu/core/
echo 'en_GB'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/en_GB/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/en_GB/LC_MESSAGES/gibbon.mo ./i18n/en_GB/LC_MESSAGES/gibbon.po
echo 'zh_CN'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/zh_CN/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/zh_CN/LC_MESSAGES/gibbon.mo ./i18n/zh_CN/LC_MESSAGES/gibbon.po
echo 'zh_HK'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/zh_HK/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/zh_HK/LC_MESSAGES/gibbon.mo ./i18n/zh_HK/LC_MESSAGES/gibbon.po
echo 'en_US'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/en_US/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/en_US/LC_MESSAGES/gibbon.mo ./i18n/en_US/LC_MESSAGES/gibbon.po
echo 'pl_PL'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/pl_PL/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/pl_PL/LC_MESSAGES/gibbon.mo ./i18n/pl_PL/LC_MESSAGES/gibbon.po
echo 'it_IT'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/it_IT/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/it_IT/LC_MESSAGES/gibbon.mo ./i18n/it_IT/LC_MESSAGES/gibbon.po
echo 'es_ES'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/es_ES/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/es_ES/LC_MESSAGES/gibbon.mo ./i18n/es_ES/LC_MESSAGES/gibbon.po
echo 'id_ID'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/id_ID/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/id_ID/LC_MESSAGES/gibbon.mo ./i18n/id_ID/LC_MESSAGES/gibbon.po
echo 'ar_SA'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ar_SA/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ar_SA/LC_MESSAGES/gibbon.mo ./i18n/ar_SA/LC_MESSAGES/gibbon.po
echo 'fr_FR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/fr_FR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/fr_FR/LC_MESSAGES/gibbon.mo ./i18n/fr_FR/LC_MESSAGES/gibbon.po
echo 'sw_KE'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/sw_KE/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/sw_KE/LC_MESSAGES/gibbon.mo ./i18n/sw_KE/LC_MESSAGES/gibbon.po
echo 'pt_PT'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/pt_PT/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/pt_PT/LC_MESSAGES/gibbon.mo ./i18n/pt_PT/LC_MESSAGES/gibbon.po
echo 'ro_RO'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ro_RO/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ro_RO/LC_MESSAGES/gibbon.mo ./i18n/ro_RO/LC_MESSAGES/gibbon.po
echo 'ur_IN'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ur_IN/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ur_IN/LC_MESSAGES/gibbon.mo ./i18n/ur_IN/LC_MESSAGES/gibbon.po
echo 'ja_JP'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ja_JP/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ja_JP/LC_MESSAGES/gibbon.mo ./i18n/ja_JP/LC_MESSAGES/gibbon.po
echo 'ru_RU'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ru_RU/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ru_RU/LC_MESSAGES/gibbon.mo ./i18n/ru_RU/LC_MESSAGES/gibbon.po
echo 'uk_UA'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/uk_UA/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/uk_UA/LC_MESSAGES/gibbon.mo ./i18n/uk_UA/LC_MESSAGES/gibbon.po
echo 'bn_BD'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/bn_BD/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/bn_BD/LC_MESSAGES/gibbon.mo ./i18n/bn_BD/LC_MESSAGES/gibbon.po
echo 'da_DK'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/da_DK/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/da_DK/LC_MESSAGES/gibbon.mo ./i18n/da_DK/LC_MESSAGES/gibbon.po
echo 'fa_IR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/fa_IR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/fa_IR/LC_MESSAGES/gibbon.mo ./i18n/fa_IR/LC_MESSAGES/gibbon.po
echo 'pt_BR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/pt_BR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/pt_BR/LC_MESSAGES/gibbon.mo ./i18n/pt_BR/LC_MESSAGES/gibbon.po
echo 'nl_NL'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/nl_NL/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/nl_NL/LC_MESSAGES/gibbon.mo ./i18n/nl_NL/LC_MESSAGES/gibbon.po
echo 'ka_GE'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ka_GE/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ka_GE/LC_MESSAGES/gibbon.mo ./i18n/ka_GE/LC_MESSAGES/gibbon.po
echo 'hu_HU'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/hu_HU/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/hu_HU/LC_MESSAGES/gibbon.mo ./i18n/hu_HU/LC_MESSAGES/gibbon.po
echo 'bg_BG'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/bg_BG/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/bg_BG/LC_MESSAGES/gibbon.mo ./i18n/bg_BG/LC_MESSAGES/gibbon.po
echo 'ko_KP'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/ko_KP/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/ko_KP/LC_MESSAGES/gibbon.mo ./i18n/ko_KP/LC_MESSAGES/gibbon.po
echo 'fi_FI'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/fi_FI/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/fi_FI/LC_MESSAGES/gibbon.mo ./i18n/fi_FI/LC_MESSAGES/gibbon.po
echo 'de_DE'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/de_DE/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/de_DE/LC_MESSAGES/gibbon.mo ./i18n/de_DE/LC_MESSAGES/gibbon.po
echo 'in_OR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/in_OR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/in_OR/LC_MESSAGES/gibbon.mo ./i18n/in_OR/LC_MESSAGES/gibbon.po
echo 'no_NO'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/no_NO/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/no_NO/LC_MESSAGES/gibbon.mo ./i18n/no_NO/LC_MESSAGES/gibbon.po
echo 'vi_VN'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/vi_VN/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/vi_VN/LC_MESSAGES/gibbon.mo ./i18n/vi_VN/LC_MESSAGES/gibbon.po
echo 'sq_AL'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/sq_AL/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/sq_AL/LC_MESSAGES/gibbon.mo ./i18n/sq_AL/LC_MESSAGES/gibbon.po
echo 'th_TH'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/th_TH/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/th_TH/LC_MESSAGES/gibbon.mo ./i18n/th_TH/LC_MESSAGES/gibbon.po
echo 'el_GR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/el_GR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/el_GR/LC_MESSAGES/gibbon.mo ./i18n/el_GR/LC_MESSAGES/gibbon.po
echo 'am_ET'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/am_ET/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/am_ET/LC_MESSAGES/gibbon.mo ./i18n/am_ET/LC_MESSAGES/gibbon.po
echo 'om_ET'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/om_ET/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/om_ET/LC_MESSAGES/gibbon.mo ./i18n/om_ET/LC_MESSAGES/gibbon.po
echo 'hr_HR'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/hr_HR/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/hr_HR/LC_MESSAGES/gibbon.mo ./i18n/hr_HR/LC_MESSAGES/gibbon.po
echo 'et_EE'
xgettext --from-code=iso-8859-1 --omit-header -j --language=PHP --keyword=__:1,1t --keyword=__:2,2t -o ./i18n/et_EE/LC_MESSAGES/gibbon.po $(find . -type f -name "*.php" ! -path "./lib/*" ! -path "./tests/*" ! -path "./vendor/*" | sed 's/ /*/g')
msgfmt -cv -o ./i18n/et_EE/LC_MESSAGES/gibbon.mo ./i18n/et_EE/LC_MESSAGES/gibbon.po
