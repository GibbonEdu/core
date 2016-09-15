(function($) {
    /**
     * Czech language package
     * Translated by @AdwinTrave. Improved by @cuchac, @budik21
     */
    FormValidation.I18n = $.extend(true, FormValidation.I18n, {
        'cs_CZ': {
            base64: {
                'default': 'Prosím zadejte správný base64'
            },
            between: {
                'default': 'Prosím zadejte hodnotu mezi %s a %s',
                notInclusive: 'Prosím zadejte hodnotu mezi %s a %s (včetně těchto čísel)'
            },
            bic: {
                'default': 'Prosím zadejte správné BIC číslo'
            },
            callback: {
                'default': 'Prosím zadejte správnou hodnotu'
            },
            choice: {
                'default': 'Prosím vyberte správnou hodnotu',
                less: 'Hodnota musí být minimálně %s',
                more: 'Hodnota nesmí být více jak %s',
                between: 'Prosím vyberte mezi %s a %s'
            },
            color: {
                'default': 'Prosím zadejte správnou barvu'
            },
            creditCard: {
                'default': 'Prosím zadejte správné číslo kreditní karty'
            },
            cusip: {
                'default': 'Prosím zadejte správné CUSIP číslo'
            },
            cvv: {
                'default': 'Prosím zadejte správné CVV číslo'
            },
            date: {
                'default': 'Prosím zadejte správné datum',
                min: 'Prosím zadejte datum před %s',
                max: 'Prosím zadejte datum po %s',
                range: 'Prosím zadejte datum v rozmezí %s až %s'
            },
            different: {
                'default': 'Prosím zadejte jinou hodnotu'
            },
            digits: {
                'default': 'Toto pole může obsahovat pouze čísla'
            },
            ean: {
                'default': 'Prosím zadejte správné EAN číslo'
            },
            ein: {
                'default': 'Prosím zadejte správné EIN číslo'
            },
            emailAddress: {
                'default': 'Prosím zadejte správnou emailovou adresu'
            },
            file: {
                'default': 'Prosím vyberte soubor'
            },
            greaterThan: {
                'default': 'Prosím zadejte hodnotu větší nebo rovnu %s',
                notInclusive: 'Prosím zadejte hodnotu větší než %s'
            },
            grid: {
                'default': 'Prosím zadejte správné GRId číslo'
            },
            hex: {
                'default': 'Prosím zadejte správné hexadecimální číslo'
            },
            iban: {
                'default': 'Prosím zadejte správné IBAN číslo',
                country: 'Prosím zadejte správné IBAN číslo pro %s',
                countries: {
                    AD: 'Andorru',
                    AE: 'Spojené arabské emiráty',
                    AL: 'Albanii',
                    AO: 'Angolu',
                    AT: 'Rakousko',
                    AZ: 'Ázerbajdžán',
                    BA: 'Bosnu a Herzegovinu',
                    BE: 'Belgii',
                    BF: 'Burkinu Faso',
                    BG: 'Bulharsko',
                    BH: 'Bahrajn',
                    BI: 'Burundi',
                    BJ: 'Benin',
                    BR: 'Brazílii',
                    CH: 'Švýcarsko',
                    CI: 'Pobřeží slonoviny',
                    CM: 'Kamerun',
                    CR: 'Kostariku',
                    CV: 'Cape Verde',
                    CY: 'Kypr',
                    CZ: 'Českou republiku',
                    DE: 'Německo',
                    DK: 'Dánsko',
                    DO: 'Dominikánskou republiku',
                    DZ: 'Alžírsko',
                    EE: 'Estonsko',
                    ES: 'Španělsko',
                    FI: 'Finsko',
                    FO: 'Faerské ostrovy',
                    FR: 'Francie',
                    GB: 'Velkou Británii',
                    GE: 'Gruzii',
                    GI: 'Gibraltar',
                    GL: 'Grónsko',
                    GR: 'Řecko',
                    GT: 'Guatemalu',
                    HR: 'Chorvatsko',
                    HU: 'Maďarsko',
                    IE: 'Irsko',
                    IL: 'Israel',
                    IR: 'Irán',
                    IS: 'Island',
                    IT: 'Itálii',
                    JO: 'Jordansko',
                    KW: 'Kuwait',
                    KZ: 'Kazachstán',
                    LB: 'Libanon',
                    LI: 'Lichtenštejnsko',
                    LT: 'Litvu',
                    LU: 'Lucembursko',
                    LV: 'Lotyšsko',
                    MC: 'Monaco',
                    MD: 'Moldavsko',
                    ME: 'Černou Horu',
                    MG: 'Madagaskar',
                    MK: 'Makedonii',
                    ML: 'Mali',
                    MR: 'Mauritánii',
                    MT: 'Maltu',
                    MU: 'Mauritius',
                    MZ: 'Mosambik',
                    NL: 'Nizozemsko',
                    NO: 'Norsko',
                    PK: 'Pakistán',
                    PL: 'Polsko',
                    PS: 'Palestinu',
                    PT: 'Portugalsko',
                    QA: 'Katar',
                    RO: 'Rumunsko',
                    RS: 'Srbsko',
                    SA: 'Saudskou Arábii',
                    SE: 'Švédsko',
                    SI: 'Slovinsko',
                    SK: 'Slovensko',
                    SM: 'San Marino',
                    SN: 'Senegal',
                    TL: 'Východní Timor',
                    TN: 'Tunisko',
                    TR: 'Turecko',
                    VG: 'Britské Panenské ostrovy',
                    XK: 'Republic of Kosovo'
                }
            },
            id: {
                'default': 'Prosím zadejte správné rodné číslo',
                country: 'Prosím zadejte správné rodné číslo pro %s',
                countries: {
                    BA: 'Bosnu a Hercegovinu',
                    BG: 'Bulharsko',
                    BR: 'Brazílii',
                    CH: 'Švýcarsko',
                    CL: 'Chile',
                    CN: 'Čínu',
                    CZ: 'Českou Republiku',
                    DK: 'Dánsko',
                    EE: 'Estonsko',
                    ES: 'Španělsko',
                    FI: 'Finsko',
                    HR: 'Chorvatsko',
                    IE: 'Irsko',
                    IS: 'Island',
                    LT: 'Litvu',
                    LV: 'Lotyšsko',
                    ME: 'Černou horu',
                    MK: 'Makedonii',
                    NL: 'Nizozemí',
                    PL: 'Polsko',
                    RO: 'Rumunsko',
                    RS: 'Srbsko',
                    SE: 'Švédsko',
                    SI: 'Slovinsko',
                    SK: 'Slovensko',
                    SM: 'San Marino',
                    TH: 'Thajsko',
                    TR: 'Turecko',
                    ZA: 'Jižní Afriku'
                }
            },
            identical: {
                'default': 'Prosím zadejte stejnou hodnotu'
            },
            imei: {
                'default': 'Prosím zadejte správné IMEI číslo'
            },
            imo: {
                'default': 'Prosím zadejte správné IMO číslo'
            },
            integer: {
                'default': 'Prosím zadejte celé číslo'
            },
            ip: {
                'default': 'Prosím zadejte správnou IP adresu',
                ipv4: 'Prosím zadejte správnou IPv4 adresu',
                ipv6: 'Prosím zadejte správnou IPv6 adresu'
            },
            isbn: {
                'default': 'Prosím zadejte správné ISBN číslo'
            },
            isin: {
                'default': 'Prosím zadejte správné ISIN číslo'
            },
            ismn: {
                'default': 'Prosím zadejte správné ISMN číslo'
            },
            issn: {
                'default': 'Prosím zadejte správné ISSN číslo'
            },
            lessThan: {
                'default': 'Prosím zadejte hodnotu menší nebo rovno %s',
                notInclusive: 'Prosím zadejte hodnotu menčí než %s'
            },
            mac: {
                'default': 'Prosím zadejte správnou MAC adresu'
            },
            meid: {
                'default': 'Prosím zadejte správné MEID číslo'
            },
            notEmpty: {
                'default': 'Toto pole nesmí být prázdné'
            },
            numeric: {
                'default': 'Prosím zadejte číselnou hodnotu'
            },
            phone: {
                'default': 'Prosím zadejte správné telefoní číslo',
                country: 'Prosím zadejte správné telefoní číslo pro %s',
                countries: {
                    AE: 'Spojené arabské emiráty',
                    BG: 'Bulharsko',
                    BR: 'Brazílii',
                    CN: 'Čínu',
                    CZ: 'Českou Republiku',
                    DE: 'Německo',
                    DK: 'Dánsko',
                    ES: 'Španělsko',
                    FR: 'Francii',
                    GB: 'Velkou Británii',
                    IN: 'Indie',
                    MA: 'Maroko',
                    NL: 'Nizozemsko',
                    PK: 'Pákistán',
                    RO: 'Rumunsko',
                    RU: 'Rusko',
                    SK: 'Slovensko',
                    TH: 'Thajsko',
                    US: 'Spojené Státy Americké',
                    VE: 'Venezuelu'
                }
            },
            promise: {
                'default': 'Prosím zadejte správnou hodnotu'
            },
            regexp: {
                'default': 'Prosím zadejte hodnotu splňující zadání'
            },
            remote: {
                'default': 'Prosím zadejte správnou hodnotu'
            },
            rtn: {
                'default': 'Prosím zadejte správné RTN číslo'
            },
            sedol: {
                'default': 'Prosím zadejte správné SEDOL číslo'
            },
            siren: {
                'default': 'Prosím zadejte správné SIREN číslo'
            },
            siret: {
                'default': 'Prosím zadejte správné SIRET číslo'
            },
            step: {
                'default': 'Prosím zadejte správný krok %s'
            },
            stringCase: {
                'default': 'Pouze malá písmena jsou povoleny v tomto poli',
                upper: 'Pouze velké písmena jsou povoleny v tomto poli'
            },
            stringLength: {
                'default': 'Toto pole nesmí být prázdné',
                less: 'Prosím zadejte hodnotu menší než %s znaků',
                more: 'Prosím zadejte hodnotu %s znaků a delší',
                between: 'Prosím zadejte hodnotu mezi %s a %s znaky'
            },
            uri: {
                'default': 'Prosím zadejte správnou URI'
            },
            uuid: {
                'default': 'Prosím zadejte správné UUID číslo',
                version: 'Prosím zadejte správné UUID verze %s'
            },
            vat: {
                'default': 'Prosím zadejte správné VAT číslo',
                country: 'Prosím zadejte správné VAT číslo pro %s',
                countries: {
                    AT: 'Rakousko',
                    BE: 'Belgii',
                    BG: 'Bulharsko',
                    BR: 'Brazílii',
                    CH: 'Švýcarsko',
                    CY: 'Kypr',
                    CZ: 'Českou Republiku',
                    DE: 'Německo',
                    DK: 'Dánsko',
                    EE: 'Estonsko',
                    ES: 'Španělsko',
                    FI: 'Finsko',
                    FR: 'Francii',
                    GB: 'Velkou Británii',
                    GR: 'Řecko',
                    EL: 'Řecko',
                    HU: 'Maďarsko',
                    HR: 'Chorvatsko',
                    IE: 'Irsko',
                    IS: 'Island',
                    IT: 'Itálii',
                    LT: 'Litvu',
                    LU: 'Lucembursko',
                    LV: 'Lotyšsko',
                    MT: 'Maltu',
                    NL: 'Nizozemí',
                    NO: 'Norsko',
                    PL: 'Polsko',
                    PT: 'Portugalsko',
                    RO: 'Rumunsko',
                    RU: 'Rusko',
                    RS: 'Srbsko',
                    SE: 'Švédsko',
                    SI: 'Slovinsko',
                    SK: 'Slovensko',
                    VE: 'Venezuelu',
                    ZA: 'Jižní Afriku'
                }
            },
            vin: {
                'default': 'Prosím zadejte správné VIN číslo'
            },
            zipCode: {
                'default': 'Prosím zadejte správné PSČ',
                country: 'Prosím zadejte správné PSČ pro %s',
                countries: {
                    AT: 'Rakousko',
                    BG: 'Bulharsko',
                    BR: 'Brazílie',
                    CA: 'Kanadu',
                    CH: 'Švýcarsko',
                    CZ: 'Českou Republiku',
                    DE: 'Německo',
                    DK: 'Dánsko',
                    ES: 'Španělsko',
                    FR: 'Francii',
                    GB: 'Velkou Británii',
                    IE: 'Irsko',
                    IN: 'Indie',
                    IT: 'Itálii',
                    MA: 'Maroko',
                    NL: 'Nizozemí',
                    PL: 'Polsko',
                    PT: 'Portugalsko',
                    RO: 'Rumunsko',
                    RU: 'Rusko',
                    SE: 'Švédsko',
                    SG: 'Singapur',
                    SK: 'Slovensko',
                    US: 'Spojené Státy Americké'
                }
            }
        }
    });
}(window.jQuery));
