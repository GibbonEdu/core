(function($) {
    /**
     * Indonesian language package
     * Translated by @egig
     */
    FormValidation.I18n = $.extend(true, FormValidation.I18n, {
        'id_ID': {
            base64: {
                'default': 'Silahkan isi karakter base 64 tersandi yang valid'
            },
            between: {
                'default': 'Silahkan isi nilai antara %s dan %s',
                notInclusive: 'Silahkan isi nilai antara %s dan %s, strictly'
            },
            bic: {
                'default': 'Silahkan isi nomor BIC yang valid'
            },
            callback: {
                'default': 'Silahkan isi nilai yang valid'
            },
            choice: {
                'default': 'Silahkan isi nilai yang valid',
                less: 'Silahkan pilih pilihan %s pada minimum',
                more: 'Silahkan pilih pilihan %s pada maksimum',
                between: 'Silahkan pilih pilihan %s - %s'
            },
            color: {
                'default': 'Silahkan isi karakter warna yang valid'
            },
            creditCard: {
                'default': 'Silahkan isi nomor kartu kredit yang valid'
            },
            cusip: {
                'default': 'Silahkan isi nomor CUSIP yang valid'
            },
            cvv: {
                'default': 'Silahkan isi nomor CVV yang valid'
            },
            date: {
                'default': 'Silahkan isi tanggal yang benar',
                min: 'Silahkan isi tanggal setelah tanggal %s',
                max: 'Silahkan isi tanggal sebelum tanggal %s',
                range: 'Silahkan isi tanggal antara %s - %s'
            },
            different: {
                'default': 'Silahkan isi nilai yang berbeda'
            },
            digits: {
                'default': 'Silahkan isi dengan hanya digit'
            },
            ean: {
                'default': 'Silahkan isi nomor EAN yang valid'
            },
            ein: {
                'default': 'Silahkan isi nomor EIN yang valid'
            },
            emailAddress: {
                'default': 'Silahkan isi alamat email yang valid'
            },
            file: {
                'default': 'Silahkan pilih file yang valid'
            },
            greaterThan: {
                'default': 'Silahkan isi nilai yang lebih besar atau sama dengan %s',
                notInclusive: 'Silahkan is nilai yang lebih besar dari %s'
            },
            grid: {
                'default': 'Silahkan nomor GRId yang valid'
            },
            hex: {
                'default': 'Silahkan isi karakter hexadecimal yang valid'
            },
            iban: {
                'default': 'silahkan isi nomor IBAN yang valid',
                country: 'Silahkan isi nomor IBAN yang valid dalam %s',
                countries: {
                    AD: 'Andorra',
                    AE: 'Uni Emirat Arab',
                    AL: 'Albania',
                    AO: 'Angola',
                    AT: 'Austria',
                    AZ: 'Azerbaijan',
                    BA: 'Bosnia and Herzegovina',
                    BE: 'Belgia',
                    BF: 'Burkina Faso',
                    BG: 'Bulgaria',
                    BH: 'Bahrain',
                    BI: 'Burundi',
                    BJ: 'Benin',
                    BR: 'Brazil',
                    CH: 'Switzerland',
                    CI: 'Pantai Gading',
                    CM: 'Kamerun',
                    CR: 'Costa Rica',
                    CV: 'Cape Verde',
                    CY: 'Cyprus',
                    CZ: 'Czech',
                    DE: 'Jerman',
                    DK: 'Denmark',
                    DO: 'Republik Dominika',
                    DZ: 'Algeria',
                    EE: 'Estonia',
                    ES: 'Spanyol',
                    FI: 'Finlandia',
                    FO: 'Faroe Islands',
                    FR: 'Francis',
                    GB: 'Inggris',
                    GE: 'Georgia',
                    GI: 'Gibraltar',
                    GL: 'Greenland',
                    GR: 'Yunani',
                    GT: 'Guatemala',
                    HR: 'Kroasia',
                    HU: 'Hungary',
                    IE: 'Irlandia',
                    IL: 'Israel',
                    IR: 'Iran',
                    IS: 'Iceland',
                    IT: 'Italia',
                    JO: 'Jordan',
                    KW: 'Kuwait',
                    KZ: 'Kazakhstan',
                    LB: 'Libanon',
                    LI: 'Liechtenstein',
                    LT: 'Lithuania',
                    LU: 'Luxembourg',
                    LV: 'Latvia',
                    MC: 'Monaco',
                    MD: 'Moldova',
                    ME: 'Montenegro',
                    MG: 'Madagascar',
                    MK: 'Macedonia',
                    ML: 'Mali',
                    MR: 'Mauritania',
                    MT: 'Malta',
                    MU: 'Mauritius',
                    MZ: 'Mozambique',
                    NL: 'Netherlands',
                    NO: 'Norway',
                    PK: 'Pakistan',
                    PL: 'Polandia',
                    PS: 'Palestina',
                    PT: 'Portugal',
                    QA: 'Qatar',
                    RO: 'Romania',
                    RS: 'Serbia',
                    SA: 'Saudi Arabia',
                    SE: 'Swedia',
                    SI: 'Slovenia',
                    SK: 'Slovakia',
                    SM: 'San Marino',
                    SN: 'Senegal',
                    TL: 'Timor Leste',
                    TN: 'Tunisia',
                    TR: 'Turki',
                    VG: 'Virgin Islands, British',
                    XK: 'Kosovo'
                }
            },
            id: {
                'default': 'Silahkan isi nomor identitas yang valid',
                country: 'Silahkan isi nomor identitas yang valid dalam %s',
                countries: {
                    BA: 'Bosnia and Herzegovina',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CH: 'Switzerland',
                    CL: 'Chile',
                    CN: 'Cina',
                    CZ: 'Czech',
                    DK: 'Denmark',
                    EE: 'Estonia',
                    ES: 'Spanyol',
                    FI: 'Finlandia',
                    HR: 'Kroasia',
                    IE: 'Irlandia',
                    IS: 'Iceland',
                    LT: 'Lithuania',
                    LV: 'Latvia',
                    ME: 'Montenegro',
                    MK: 'Macedonia',
                    NL: 'Netherlands',
                    PL: 'Polandia',
                    RO: 'Romania',
                    RS: 'Serbia',
                    SE: 'Sweden',
                    SI: 'Slovenia',
                    SK: 'Slovakia',
                    SM: 'San Marino',
                    TH: 'Thailand',
                    TR: 'Turki',
                    ZA: 'Africa Selatan'
                }
            },
            identical: {
                'default': 'Silahkan isi nilai yang sama'
            },
            imei: {
                'default': 'Silahkan isi nomor IMEI yang valid'
            },
            imo: {
                'default': 'Silahkan isi nomor IMO yang valid'
            },
            integer: {
                'default': 'Silahkan isi angka yang valid'
            },
            ip: {
                'default': 'Silahkan isi alamat IP yang valid',
                ipv4: 'Silahkan isi alamat IPv4 yang valid',
                ipv6: 'Silahkan isi alamat IPv6 yang valid'
            },
            isbn: {
                'default': 'Slilahkan isi nomor ISBN yang valid'
            },
            isin: {
                'default': 'Silahkan isi ISIN yang valid'
            },
            ismn: {
                'default': 'Silahkan isi nomor ISMN yang valid'
            },
            issn: {
                'default': 'Silahkan isi nomor ISSN yang valid'
            },
            lessThan: {
                'default': 'Silahkan isi nilai kurang dari atau sama dengan %s',
                notInclusive: 'Silahkan isi nilai kurang dari %s'
            },
            mac: {
                'default': 'Silahkan isi MAC address yang valid'
            },
            meid: {
                'default': 'Silahkan isi nomor MEID yang valid'
            },
            notEmpty: {
                'default': 'Silahkan isi'
            },
            numeric: {
                'default': 'Silahkan isi nomor yang valid'
            },
            phone: {
                'default': 'Silahkan isi nomor telepon yang valid',
                country: 'Silahkan isi nomor telepon yang valid dalam %s',
                countries: {
                    AE: 'Uni Emirat Arab',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CN: 'Cina',
                    CZ: 'Czech',
                    DE: 'Jerman',
                    DK: 'Denmark',
                    ES: 'Spanyol',
                    FR: 'Francis',
                    GB: 'Inggris',
                    IN: 'India',
                    MA: 'Maroko',
                    NL: 'Netherlands',
                    PK: 'Pakistan',
                    RO: 'Romania',
                    RU: 'Russia',
                    SK: 'Slovakia',
                    TH: 'Thailand',
                    US: 'Amerika Serikat',
                    VE: 'Venezuela'
                }
            },
            promise: {
                'default': 'Silahkan isi nilai yang valid'
            },
            regexp: {
                'default': 'Silahkan isi nilai yang cocok dengan pola'
            },
            remote: {
                'default': 'Silahkan isi nilai yang valid'
            },
            rtn: {
                'default': 'Silahkan isi nomor RTN yang valid'
            },
            sedol: {
                'default': 'Silahkan isi nomor SEDOL yang valid'
            },
            siren: {
                'default': 'Silahkan isi nomor SIREN yang valid'
            },
            siret: {
                'default': 'Silahkan isi nomor SIRET yang valid'
            },
            step: {
                'default': 'Silahkan isi langkah yang benar pada %s'
            },
            stringCase: {
                'default': 'Silahkan isi hanya huruf kecil',
                upper: 'Silahkan isi hanya huruf besar'
            },
            stringLength: {
                'default': 'Silahkan isi nilai dengan panjang karakter yang benar',
                less: 'Silahkan isi kurang dari %s karakter',
                more: 'Silahkan isi lebih dari %s karakter',
                between: 'Silahkan isi antara %s dan %s panjang karakter'
            },
            uri: {
                'default': 'Silahkan isi URI yang valid'
            },
            uuid: {
                'default': 'Silahkan isi nomor UUID yang valid',
                version: 'Silahkan si nomor versi %s UUID yang valid'
            },
            vat: {
                'default': 'Silahkan isi nomor VAT yang valid',
                country: 'Silahkan nomor VAT yang valid dalam %s',
                countries: {
                    AT: 'Austria',
                    BE: 'Belgium',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CH: 'Switzerland',
                    CY: 'Cyprus',
                    CZ: 'Czech',
                    DE: 'Jerman',
                    DK: 'Denmark',
                    EE: 'Estonia',
                    ES: 'Spanyol',
                    FI: 'Finlandia',
                    FR: 'Francis',
                    GB: 'Inggris',
                    GR: 'Yunani',
                    EL: 'Yunani',
                    HU: 'Hungaria',
                    HR: 'Kroasia',
                    IE: 'Irlandia',
                    IS: 'Iceland',
                    IT: 'Italy',
                    LT: 'Lithuania',
                    LU: 'Luxembourg',
                    LV: 'Latvia',
                    MT: 'Malta',
                    NL: 'Belanda',
                    NO: 'Norway',
                    PL: 'Polandia',
                    PT: 'Portugal',
                    RO: 'Romania',
                    RU: 'Russia',
                    RS: 'Serbia',
                    SE: 'Sweden',
                    SI: 'Slovenia',
                    SK: 'Slovakia',
                    VE: 'Venezuela',
                    ZA: 'Afrika Selatan'
                }
            },
            vin: {
                'default': 'Silahkan isi nomor VIN yang valid'
            },
            zipCode: {
                'default': 'Silahkan isi kode pos yang valid',
                country: 'Silahkan isi kode pos yang valid di %s',
                countries: {
                    AT: 'Austria',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CA: 'Kanada',
                    CH: 'Switzerland',
                    CZ: 'Czech',
                    DE: 'Jerman',
                    DK: 'Denmark',
                    ES: 'Spanyol',
                    FR: 'Francis',
                    GB: 'Inggris',
                    IE: 'Irlandia',
                    IN: 'India',
                    IT: 'Italia',
                    MA: 'Maroko',
                    NL: 'Belanda',
                    PL: 'Polandia',
                    PT: 'Portugal',
                    RO: 'Romania',
                    RU: 'Russia',
                    SE: 'Sweden',
                    SG: 'Singapura',
                    SK: 'Slovakia',
                    US: 'Amerika Serikat'
                }
            }
        }
    });
}(jQuery));
