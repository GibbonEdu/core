(function($) {
    /**
     * Vietnamese language package
     * Translated by @nghuuphuoc
     */
    FormValidation.I18n = $.extend(true, FormValidation.I18n, {
        'vi_VN': {
            base64: {
                'default': 'Vui lòng nhập chuỗi mã hoá base64 hợp lệ'
            },
            between: {
                'default': 'Vui lòng nhập giá trị nằm giữa %s và %s',
                notInclusive: 'Vui lòng nhập giá trị nằm giữa %s và %s'
            },
            bic: {
                'default': 'Vui lòng nhập số BIC hợp lệ'
            },
            callback: {
                'default': 'Vui lòng nhập giá trị hợp lệ'
            },
            choice: {
                'default': 'Vui lòng nhập giá trị hợp lệ',
                less: 'Vui lòng chọn ít nhất %s lựa chọn',
                more: 'Vui lòng chọn nhiều nhất %s lựa chọn',
                between: 'Vui lòng chọn %s - %s lựa chọn'
            },
            color: {
                'default': 'Vui lòng nhập mã màu hợp lệ'
            },
            creditCard: {
                'default': 'Vui lòng nhập số thẻ tín dụng hợp lệ'
            },
            cusip: {
                'default': 'Vui lòng nhập số CUSIP hợp lệ'
            },
            cvv: {
                'default': 'Vui lòng nhập số CVV hợp lệ'
            },
            date: {
                'default': 'Vui lòng nhập ngày hợp lệ',
                min: 'Vui lòng nhập ngày sau %s',
                max: 'Vui lòng nhập ngày trước %s',
                range: 'Vui lòng nhập ngày trong khoảng %s - %s'
            },
            different: {
                'default': 'Vui lòng nhập một giá trị khác'
            },
            digits: {
                'default': 'Vui lòng chỉ nhập số'
            },
            ean: {
                'default': 'Vui lòng nhập số EAN hợp lệ'
            },
            ein: {
                'default': 'Vui lòng nhập số EIN hợp lệ'
            },
            emailAddress: {
                'default': 'Vui lòng nhập địa chỉ email hợp lệ'
            },
            file: {
                'default': 'Vui lòng chọn file hợp lệ'
            },
            greaterThan: {
                'default': 'Vui lòng nhập giá trị lớn hơn hoặc bằng %s',
                notInclusive: 'Vui lòng nhập giá trị lớn hơn %s'
            },
            grid: {
                'default': 'Vui lòng nhập số GRId hợp lệ'
            },
            hex: {
                'default': 'Vui lòng nhập số hexa hợp lệ'
            },
            iban: {
                'default': 'Vui lòng nhập số IBAN hợp lệ',
                country: 'Vui lòng nhập mã IBAN hợp lệ của %s',
                countries: {
                    AD: 'Andorra',
                    AE: 'Tiểu vương quốc Ả Rập thống nhất',
                    AL: 'Albania',
                    AO: 'Angola',
                    AT: 'Áo',
                    AZ: 'Azerbaijan',
                    BA: 'Bosnia và Herzegovina',
                    BE: 'Bỉ',
                    BF: 'Burkina Faso',
                    BG: 'Bulgaria',
                    BH: 'Bahrain',
                    BI: 'Burundi',
                    BJ: 'Benin',
                    BR: 'Brazil',
                    CH: 'Thuỵ Sĩ',
                    CI: 'Bờ Biển Ngà',
                    CM: 'Cameroon',
                    CR: 'Costa Rica',
                    CV: 'Cape Verde',
                    CY: 'Síp',
                    CZ: 'Séc',
                    DE: 'Đức',
                    DK: 'Đan Mạch',
                    DO: 'Dominican',
                    DZ: 'Algeria',
                    EE: 'Estonia',
                    ES: 'Tây Ban Nha',
                    FI: 'Phần Lan',
                    FO: 'Đảo Faroe',
                    FR: 'Pháp',
                    GB: 'Vương quốc Anh',
                    GE: 'Georgia',
                    GI: 'Gibraltar',
                    GL: 'Greenland',
                    GR: 'Hy Lạp',
                    GT: 'Guatemala',
                    HR: 'Croatia',
                    HU: 'Hungary',
                    IE: 'Ireland',
                    IL: 'Israel',
                    IR: 'Iran',
                    IS: 'Iceland',
                    IT: 'Ý',
                    JO: 'Jordan',
                    KW: 'Kuwait',
                    KZ: 'Kazakhstan',
                    LB: 'Lebanon',
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
                    NL: 'Hà Lan',
                    NO: 'Na Uy',
                    PK: 'Pakistan',
                    PL: 'Ba Lan',
                    PS: 'Palestine',
                    PT: 'Bồ Đào Nha',
                    QA: 'Qatar',
                    RO: 'Romania',
                    RS: 'Serbia',
                    SA: 'Ả Rập Xê Út',
                    SE: 'Thuỵ Điển',
                    SI: 'Slovenia',
                    SK: 'Slovakia',
                    SM: 'San Marino',
                    SN: 'Senegal',
                    TL: 'Đông Timor',
                    TN: 'Tunisia',
                    TR: 'Thổ Nhĩ Kỳ',
                    VG: 'Đảo Virgin, Anh quốc',
                    XK: 'Kosovo'
                }
            },
            id: {
                'default': 'Vui lòng nhập mã ID hợp lệ',
                country: 'Vui lòng nhập mã ID hợp lệ của %s',
                countries: {
                    BA: 'Bosnia và Herzegovina',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CH: 'Thuỵ Sĩ',
                    CL: 'Chi Lê',
                    CN: 'Trung Quốc',
                    CZ: 'Séc',
                    DK: 'Đan Mạch',
                    EE: 'Estonia',
                    ES: 'Tây Ban Nha',
                    FI: 'Phần Lan',
                    HR: 'Croatia',
                    IE: 'Ireland',
                    IS: 'Iceland',
                    LT: 'Lithuania',
                    LV: 'Latvia',
                    ME: 'Montenegro',
                    MK: 'Macedonia',
                    NL: 'Hà Lan',
                    PL: 'Ba Lan',
                    RO: 'Romania',
                    RS: 'Serbia',
                    SE: 'Thuỵ Điển',
                    SI: 'Slovenia',
                    SK: 'Slovakia',
                    SM: 'San Marino',
                    TH: 'Thái Lan',
                    TR: 'Thổ Nhĩ Kỳ',
                    ZA: 'Nam Phi'
                }
            },
            identical: {
                'default': 'Vui lòng nhập cùng giá trị'
            },
            imei: {
                'default': 'Vui lòng nhập số IMEI hợp lệ'
            },
            imo: {
                'default': 'Vui lòng nhập số IMO hợp lệ'
            },
            integer: {
                'default': 'Vui lòng nhập số hợp lệ'
            },
            ip: {
                'default': 'Vui lòng nhập địa chỉ IP hợp lệ',
                ipv4: 'Vui lòng nhập địa chỉ IPv4 hợp lệ',
                ipv6: 'Vui lòng nhập địa chỉ IPv6 hợp lệ'
            },
            isbn: {
                'default': 'Vui lòng nhập số ISBN hợp lệ'
            },
            isin: {
                'default': 'Vui lòng nhập số ISIN hợp lệ'
            },
            ismn: {
                'default': 'Vui lòng nhập số ISMN hợp lệ'
            },
            issn: {
                'default': 'Vui lòng nhập số ISSN hợp lệ'
            },
            lessThan: {
                'default': 'Vui lòng nhập giá trị nhỏ hơn hoặc bằng %s',
                notInclusive: 'Vui lòng nhập giá trị nhỏ hơn %s'
            },
            mac: {
                'default': 'Vui lòng nhập địa chỉ MAC hợp lệ'
            },
            meid: {
                'default': 'Vui lòng nhập số MEID hợp lệ'
            },
            notEmpty: {
                'default': 'Vui lòng nhập giá trị'
            },
            numeric: {
                'default': 'Vui lòng nhập số hợp lệ'
            },
            phone: {
                'default': 'Vui lòng nhập số điện thoại hợp lệ',
                country: 'Vui lòng nhập số điện thoại hợp lệ của %s',
                countries: {
                    AE: 'Tiểu vương quốc Ả Rập thống nhất',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CN: 'Trung Quốc',
                    CZ: 'Séc',
                    DE: 'Đức',
                    DK: 'Đan Mạch',
                    ES: 'Tây Ban Nha',
                    FR: 'Pháp',
                    GB: 'Vương quốc Anh',
                    IN: 'Ấn Độ',
                    MA: 'Maroc',
                    NL: 'Hà Lan',
                    PK: 'Pakistan',
                    RO: 'Romania',
                    RU: 'Nga',
                    SK: 'Slovakia',
                    TH: 'Thái Lan',
                    US: 'Mỹ',
                    VE: 'Venezuela'
                }
            },
            promise: {
                'default': 'Vui lòng nhập giá trị hợp lệ'
            },
            regexp: {
                'default': 'Vui lòng nhập giá trị thích hợp với biểu mẫu'
            },
            remote: {
                'default': 'Vui lòng nhập giá trị hợp lệ'
            },
            rtn: {
                'default': 'Vui lòng nhập số RTN hợp lệ'
            },
            sedol: {
                'default': 'Vui lòng nhập số SEDOL hợp lệ'
            },
            siren: {
                'default': 'Vui lòng nhập số Siren hợp lệ'
            },
            siret: {
                'default': 'Vui lòng nhập số Siret hợp lệ'
            },
            step: {
                'default': 'Vui lòng nhập bước nhảy của %s'
            },
            stringCase: {
                'default': 'Vui lòng nhập ký tự thường',
                upper: 'Vui lòng nhập ký tự in hoa'
            },
            stringLength: {
                'default': 'Vui lòng nhập giá trị có độ dài hợp lệ',
                less: 'Vui lòng nhập ít hơn %s ký tự',
                more: 'Vui lòng nhập nhiều hơn %s ký tự',
                between: 'Vui lòng nhập giá trị có độ dài trong khoảng %s và %s ký tự'
            },
            uri: {
                'default': 'Vui lòng nhập địa chỉ URI hợp lệ'
            },
            uuid: {
                'default': 'Vui lòng nhập số UUID hợp lệ',
                version: 'Vui lòng nhập số UUID phiên bản %s hợp lệ'
            },
            vat: {
                'default': 'Vui lòng nhập số VAT hợp lệ',
                country: 'Vui lòng nhập số VAT hợp lệ của %s',
                countries: {
                    AT: 'Áo',
                    BE: 'Bỉ',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CH: 'Thuỵ Sĩ',
                    CY: 'Síp',
                    CZ: 'Séc',
                    DE: 'Đức',
                    DK: 'Đan Mạch',
                    EE: 'Estonia',
                    ES: 'Tây Ban Nha',
                    FI: 'Phần Lan',
                    FR: 'Pháp',
                    GB: 'Vương quốc Anh',
                    GR: 'Hy Lạp',
                    EL: 'Hy Lạp',
                    HU: 'Hungari',
                    HR: 'Croatia',
                    IE: 'Ireland',
                    IS: 'Iceland',
                    IT: 'Ý',
                    LT: 'Lithuania',
                    LU: 'Luxembourg',
                    LV: 'Latvia',
                    MT: 'Malta',
                    NL: 'Hà Lan',
                    NO: 'Na Uy',
                    PL: 'Ba Lan',
                    PT: 'Bồ Đào Nha',
                    RO: 'Romania',
                    RU: 'Nga',
                    RS: 'Serbia',
                    SE: 'Thuỵ Điển',
                    SI: 'Slovenia',
                    SK: 'Slovakia',
                    VE: 'Venezuela',
                    ZA: 'Nam Phi'
                }
            },
            vin: {
                'default': 'Vui lòng nhập số VIN hợp lệ'
            },
            zipCode: {
                'default': 'Vui lòng nhập mã bưu điện hợp lệ',
                country: 'Vui lòng nhập mã bưu điện hợp lệ của %s',
                countries: {
                    AT: 'Áo',
                    BG: 'Bulgaria',
                    BR: 'Brazil',
                    CA: 'Canada',
                    CH: 'Thuỵ Sĩ',
                    CZ: 'Séc',
                    DE: 'Đức',
                    DK: 'Đan Mạch',
                    ES: 'Tây Ban Nha',
                    FR: 'Pháp',
                    GB: 'Vương quốc Anh',
                    IE: 'Ireland',
                    IN: 'Ấn Độ',
                    IT: 'Ý',
                    MA: 'Maroc',
                    NL: 'Hà Lan',
                    PL: 'Ba Lan',
                    PT: 'Bồ Đào Nha',
                    RO: 'Romania',
                    RU: 'Nga',
                    SE: 'Thuỵ Sĩ',
                    SG: 'Singapore',
                    SK: 'Slovakia',
                    US: 'Mỹ'
                }
            }
        }
    });
}(jQuery));
