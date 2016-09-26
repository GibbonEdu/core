(function($) {
    /**
     * Catalan language package
     * Translated by @ArnauAregall
     */
    FormValidation.I18n = $.extend(true, FormValidation.I18n, {
        'ca_ES': {
            base64: {
                'default': 'Si us plau introdueix un valor vàlid en base 64'
            },
            between: {
                'default': 'Si us plau introdueix un valor entre %s i %s',
                notInclusive: 'Si us plau introdueix un valor comprès entre %s i %s'
            },
            bic: {
                'default': 'Si us plau introdueix un nombre BIC vàlid'
            },
            callback: {
                'default': 'Si us plau introdueix un valor vàlid'
            },
            choice: {
                'default': 'Si us plau introdueix un valor vàlid',
                less: 'Si us plau escull %s opcions com a mínim',
                more: 'Si us plau escull %s opcions com a màxim',
                between: 'Si us plau escull entre %s i %s opcions'
            },
            color: {
                'default': 'Si us plau introdueix un color vàlid'
            },
            creditCard: {
                'default': 'Si us plau introdueix un nombre vàlid de targeta de crèdit'
            },
            cusip: {
                'default': 'Si us plau introdueix un nombre CUSIP vàlid'
            },
            cvv: {
                'default': 'Si us plau introdueix un nombre CVV vàlid'
            },
            date: {
                'default': 'Si us plau introdueix una data vàlida',
                min: 'Si us plau introdueix una data posterior a %s',
                max: 'Si us plau introdueix una data anterior %s',
                range: 'Si us plau introdueix una data compresa entre %s i %s'
            },
            different: {
                'default': 'Si us plau introdueix un valor diferent'
            },
            digits: {
                'default': 'Si us plau introdueix només dígits'
            },
            ean: {
                'default': 'Si us plau introdueix un nombre EAN vàlid'
            },
            ein: {
                'default': 'Si us plau introdueix un nombre EIN vàlid'
            },
            emailAddress: {
                'default': 'Si us plau introdueix una adreça electrònica vàlida'
            },
            file: {
                'default': 'Si us plau selecciona un arxiu vàlid'
            },
            greaterThan: {
                'default': 'Si us plau introdueix un valor més gran o igual a %s',
                notInclusive: 'Si us plau introdueix un valor més gran que %s'
            },
            grid: {
                'default': 'Si us plau introdueix un nombre GRId vàlid'
            },
            hex: {
                'default': 'Si us plau introdueix un valor hexadecimal vàlid'
            },
            iban: {
                'default': 'Si us plau introdueix un nombre IBAN vàlid',
                country: 'Si us plau introdueix un nombre IBAN vàlid a %s',
                countries: {
                    AD: 'Andorra',
                    AE: 'Emirats Àrabs Units',
                    AL: 'Albània',
                    AO: 'Angola',
                    AT: 'Àustria',
                    AZ: 'Azerbaidjan',
                    BA: 'Bòsnia i Hercegovina',
                    BE: 'Bèlgica',
                    BF: 'Burkina Faso',
                    BG: 'Bulgària',
                    BH: 'Bahrain',
                    BI: 'Burundi',
                    BJ: 'Benín',
                    BR: 'Brasil',
                    CH: 'Suïssa',
                    CI: 'Costa d\'Ivori',
                    CM: 'Camerun',
                    CR: 'Costa Rica',
                    CV: 'Cap Verd',
                    CY: 'Xipre',
                    CZ: 'República Txeca',
                    DE: 'Alemanya',
                    DK: 'Dinamarca',
                    DO: 'República Dominicana',
                    DZ: 'Algèria',
                    EE: 'Estònia',
                    ES: 'Espanya',
                    FI: 'Finlàndia',
                    FO: 'Illes Fèroe',
                    FR: 'França',
                    GB: 'Regne Unit',
                    GE: 'Geòrgia',
                    GI: 'Gibraltar',
                    GL: 'Grenlàndia',
                    GR: 'Grècia',
                    GT: 'Guatemala',
                    HR: 'Croàcia',
                    HU: 'Hongria',
                    IE: 'Irlanda',
                    IL: 'Israel',
                    IR: 'Iran',
                    IS: 'Islàndia',
                    IT: 'Itàlia',
                    JO: 'Jordània',
                    KW: 'Kuwait',
                    KZ: 'Kazajistán',
                    LB: 'Líban',
                    LI: 'Liechtenstein',
                    LT: 'Lituània',
                    LU: 'Luxemburg',
                    LV: 'Letònia',
                    MC: 'Mònaco',
                    MD: 'Moldàvia',
                    ME: 'Montenegro',
                    MG: 'Madagascar',
                    MK: 'Macedònia',
                    ML: 'Malo',
                    MR: 'Mauritània',
                    MT: 'Malta',
                    MU: 'Maurici',
                    MZ: 'Moçambic',
                    NL: 'Països Baixos',
                    NO: 'Noruega',
                    PK: 'Pakistan',
                    PL: 'Polònia',
                    PS: 'Palestina',
                    PT: 'Portugal',
                    QA: 'Qatar',
                    RO: 'Romania',
                    RS: 'Sèrbia',
                    SA: 'Aràbia Saudita',
                    SE: 'Suècia',
                    SI: 'Eslovènia',
                    SK: 'Eslovàquia',
                    SM: 'San Marino',
                    SN: 'Senegal',
                    TL: 'Timor Oriental',
                    TN: 'Tunísia',
                    TR: 'Turquia',
                    VG: 'Illes Verges Britàniques',
                    XK: 'República de Kosovo'
                }
            },
            id: {
                'default': 'Si us plau introdueix un nombre d\'identificació vàlid',
                country: 'Si us plau introdueix un nombre d\'identificació vàlid a %s',
                countries: {
                    BA: 'Bòsnia i Hercegovina',
                    BG: 'Bulgària',
                    BR: 'Brasil',
                    CH: 'Suïssa',
                    CL: 'Xile',
                    CN: 'Xina',
                    CZ: 'República Checa',
                    DK: 'Dinamarca',
                    EE: 'Estònia',
                    ES: 'Espanya',
                    FI: 'Finlpandia',
                    HR: 'Cropàcia',
                    IE: 'Irlanda',
                    IS: 'Islàndia',
                    LT: 'Lituania',
                    LV: 'Letònia',
                    ME: 'Montenegro',
                    MK: 'Macedònia',
                    NL: 'Països Baixos',
                    PL: 'Polònia',
                    RO: 'Romania',
                    RS: 'Sèrbia',
                    SE: 'Suècia',
                    SI: 'Eslovènia',
                    SK: 'Eslovàquia',
                    SM: 'San Marino',
                    TH: 'Tailàndia',
                    TR: 'Turquia',
                    ZA: 'Sud-àfrica'
                }
            },
            identical: {
                'default': 'Si us plau introdueix exactament el mateix valor'
            },
            imei: {
                'default': 'Si us plau introdueix un nombre IMEI vàlid'
            },
            imo: {
                'default': 'Si us plau introdueix un nombre IMO vàlid'
            },
            integer: {
                'default': 'Si us plau introdueix un nombre vàlid'
            },
            ip: {
                'default': 'Si us plau introdueix una direcció IP vàlida',
                ipv4: 'Si us plau introdueix una direcció IPV4 vàlida',
                ipv6: 'Si us plau introdueix una direcció IPV6 vàlida'
            },
            isbn: {
                'default': 'Si us plau introdueix un nombre ISBN vàlid'
            },
            isin: {
                'default': 'Si us plau introdueix un nombre ISIN vàlid'
            },
            ismn: {
                'default': 'Si us plau introdueix un nombre ISMN vàlid'
            },
            issn: {
                'default': 'Si us plau introdueix un nombre ISSN vàlid'
            },
            lessThan: {
                'default': 'Si us plau introdueix un valor menor o igual a %s',
                notInclusive: 'Si us plau introdueix un valor menor que %s'
            },
            mac: {
                'default': 'Si us plau introdueix una adreça MAC vàlida'
            },
            meid: {
                'default': 'Si us plau introdueix nombre MEID vàlid'
            },
            notEmpty: {
                'default': 'Si us plau introdueix un valor'
            },
            numeric: {
                'default': 'Si us plau introdueix un nombre decimal vàlid'
            },
            phone: {
                'default': 'Si us plau introdueix un telèfon vàlid',
                country: 'Si us plau introdueix un telèfon vàlid a %s',
                countries: {
                    AE: 'Emirats Àrabs Units',
                    BG: 'Bulgària',
                    BR: 'Brasil',
                    CN: 'Xina',
                    CZ: 'República Checa',
                    DE: 'Alemanya',
                    DK: 'Dinamarca',
                    ES: 'Espanya',
                    FR: 'França',
                    GB: 'Regne Unit',
                    IN: 'Índia',
                    MA: 'Marroc',
                    NL: 'Països Baixos',
                    PK: 'Pakistan',
                    RO: 'Romania',
                    RU: 'Rússia',
                    SK: 'Eslovàquia',
                    TH: 'Tailàndia',
                    US: 'Estats Units',
                    VE: 'Veneçuela'
                }
            },
            promise: {
                'default': 'Si us plau introdueix un valor vàlid'
            },
            regexp: {
                'default': 'Si us plau introdueix un valor que coincideixi amb el patró'
            },
            remote: {
                'default': 'Si us plau introdueix un valor vàlid'
            },
            rtn: {
                'default': 'Si us plau introdueix un nombre RTN vàlid'
            },
            sedol: {
                'default': 'Si us plau introdueix un nombre SEDOL vàlid'
            },
            siren: {
                'default': 'Si us plau introdueix un nombre SIREN vàlid'
            },
            siret: {
                'default': 'Si us plau introdueix un nombre SIRET vàlid'
            },
            step: {
                'default': 'Si us plau introdueix un pas vàlid de %s'
            },
            stringCase: {
                'default': 'Si us plau introdueix només caràcters en minúscula',
                upper: 'Si us plau introdueix només caràcters en majúscula'
            },
            stringLength: {
                'default': 'Si us plau introdueix un valor amb una longitud vàlida',
                less: 'Si us plau introdueix menys de %s caràcters',
                more: 'Si us plau introdueix més de %s caràcters',
                between: 'Si us plau introdueix un valor amb una longitud compresa entre %s i %s caràcters'
            },
            uri: {
                'default': 'Si us plau introdueix una URI vàlida'
            },
            uuid: {
                'default': 'Si us plau introdueix un nom UUID vàlid',
                version: 'Si us plau introdueix una versió UUID vàlida per %s'
            },
            vat: {
                'default': 'Si us plau introdueix una quantitat d\'IVA vàlida',
                country: 'Si us plau introdueix una quantitat d\' IVA vàlida a %s',
                countries: {
                    AT: 'Àustria',
                    BE: 'Bèlgica',
                    BG: 'Bulgària',
                    BR: 'Brasil',
                    CH: 'Suïssa',
                    CY: 'Xipre',
                    CZ: 'República Checa',
                    DE: 'Alemanya',
                    DK: 'Dinamarca',
                    EE: 'Estònia',
                    ES: 'Espanya',
                    FI: 'Finlàndia',
                    FR: 'França',
                    GB: 'Regne Unit',
                    GR: 'Grècia',
                    EL: 'Grècia',
                    HU: 'Hongria',
                    HR: 'Croàcia',
                    IE: 'Irlanda',
                    IS: 'Islàndia',
                    IT: 'Itàlia',
                    LT: 'Lituània',
                    LU: 'Luxemburg',
                    LV: 'Letònia',
                    MT: 'Malta',
                    NL: 'Països Baixos',
                    NO: 'Noruega',
                    PL: 'Polònia',
                    PT: 'Portugal',
                    RO: 'Romania',
                    RU: 'Rússia',
                    RS: 'Sèrbia',
                    SE: 'Suècia',
                    SI: 'Eslovènia',
                    SK: 'Eslovàquia',
                    VE: 'Veneçuela',
                    ZA: 'Sud-àfrica'
                }
            },
            vin: {
                'default': 'Si us plau introdueix un nombre VIN vàlid'
            },
            zipCode: {
                'default': 'Si us plau introdueix un codi postal vàlid',
                country: 'Si us plau introdueix un codi postal vàlid a %s',
                countries: {
                    AT: 'Àustria',
                    BG: 'Bulgària',
                    BR: 'Brasil',
                    CA: 'Canadà',
                    CH: 'Suïssa',
                    CZ: 'República Checa',
                    DE: 'Alemanya',
                    DK: 'Dinamarca',
                    ES: 'Espanya',
                    FR: 'França',
                    GB: 'Rege Unit',
                    IE: 'Irlanda',
                    IN: 'Índia',
                    IT: 'Itàlia',
                    MA: 'Marroc',
                    NL: 'Països Baixos',
                    PL: 'Polònia',
                    PT: 'Portugal',
                    RO: 'Romania',
                    RU: 'Rússia',
                    SE: 'Suècia',
                    SG: 'Singapur',
                    SK: 'Eslovàquia',
                    US: 'Estats Units'
                }
            }
        }
    });
}(jQuery));
