<?php

namespace SEO\Services;

use SEO\Common\SEOException;

class KeywordsEverywhere
{

    /**
     * Checks the validity of the given API key. Returns `true` if it is valid, and `false` otherwise.
     *
     * @param string $key
     * @return bool
     */
    public static function checkApiKey($key) {
        $data = static::get(static::uri('checkApiKey', array(
            'apiKey' => $key
        )));

        return $data[0];
    }

    /**
     * Checks the validity of the given API key. Returns `true` if it is valid, and `false` otherwise.
     *
     * @param string $key
     * @return bool
     */
    public static function getKeywordData($key, $keywords, $country = '', $currency = '') {
        $data = static::get(static::uri('getKeywordData', array(
            'apiKey' => $key,
            'country' => $country,
            'currency' => $currency,
            'dataSource' => 'cli',
            'source' => 'gkplan',
            'kw' => $keywords,
            't' => round(microtime(true) * 1000)
        )));

        return $data;
    }

    /**
     * Returns an array of countries supported by the service.
     *
     * @return array
     */
    public static function getCountries() {
        return array(
            '' => 'Global',
            'au' => 'Australia',
            'ca' => 'Canada',
            'in' => 'India',
            'nz' => 'New Zealand',
            'za' => 'South Africa',
            'uk' => 'United Kingdom',
            'us' => 'United States'
        );
    }

    /**
     * Returns an array of currencies supported by the service.
     *
     * @return array
     */
    public static function getCurrencies() {
        return array(
            ''    => 'United States Dollar ($)',
            'aed' => 'UAE Dirham (AED)',
            'all' => 'Albanian Lek (ALL)',
            'ang' => 'Neth Antilles Guilder (NAƒ)',
            'ars' => 'Argentine Peso ($)',
            'aud' => 'Australian Dollar ($)',
            'awg' => 'Aruba Florin (ƒ)',
            'bbd' => 'Barbados Dollar (BBD)',
            'bdt' => 'Bangladesh Taka (Tk)',
            'bgn' => 'Bulgarian Lev (лв)',
            'bhd' => 'Bahraini Dinar (BHD)',
            'bif' => 'Burundi Franc (FBu)',
            'bmd' => 'Bermuda Dollar (BD$)',
            'bnd' => 'Brunei Dollar (B$)',
            'bob' => 'Bolivian Boliviano (Bs)',
            'brl' => 'Brazilian Real (R$)',
            'bsd' => 'Bahamian Dollar (B$)',
            'btn' => 'Bhutan Ngultrum (Nu)',
            'bwp' => 'Botswana Pula (P)',
            'byr' => 'Belarus Ruble (Br)',
            'bzd' => 'Belize Dollar (BZ$)',
            'cad' => 'Canadian Dollar (C$)',
            'chf' => 'Swiss Franc (CHF)',
            'clp' => 'Chilean Peso ($)',
            'cny' => 'Chinese Yuan (¥)',
            'cop' => 'Colombian Peso ($)',
            'crc' => 'Costa Rica Colon (₡)',
            'cup' => 'Cuban Peso ($MN)',
            'cve' => 'Cape Verde Escudo (Esc)',
            'czk' => 'Czech Koruna (Kč)',
            'djf' => 'Djibouti Franc (Fdj)',
            'dkk' => 'Danish Krone (kr)',
            'dop' => 'Dominican Peso (RD$)',
            'dzd' => 'Algerian Dinar (دج)',
            'eek' => 'Estonian Kroon (EEK)',
            'egp' => 'Egyptian Pound (EGP)',
            'etb' => 'Ethiopian Birr (Br)',
            'eur' => 'Euro (€)',
            'fjd' => 'Fiji Dollar (FJ$)',
            'fkp' => 'Falkland Islands Pound (£)',
            'gbp' => 'British Pound (£)',
            'ghs' => 'Ghanaian Cedi (GHS)',
            'gmd' => 'Gambian Dalasi (D)',
            'gnf' => 'Guinea Franc (FG)',
            'gtq' => 'Guatemala Quetzal (Q)',
            'gyd' => 'Guyana Dollar (GY$)',
            'hkd' => 'Hong Kong Dollar (HK$)',
            'hnl' => 'Honduras Lempira (L)',
            'hrk' => 'Croatian Kuna (kn)',
            'huf' => 'Hungarian Forint (Ft)',
            'idr' => 'Indonesian Rupiah (Rp)',
            'ils' => 'Israeli Shekel (₪)',
            'inr' => 'Indian Rupee (Rs)',
            'iqd' => 'Iraqi Dinar (IQD)',
            'isk' => 'Iceland Krona (kr)',
            'jod' => 'Jordanian Dinar (JOD)',
            'jpy' => 'Japanese Yen (¥)',
            'kes' => 'Kenyan Shilling (KSh)',
            'kgs' => 'Kyrgyzstan Som (KGS)',
            'khr' => 'Cambodia Riel (KHR)',
            'kmf' => 'Comoros Franc (KMF)',
            'kpw' => 'North Korean Won (₩)',
            'krw' => 'Korean Won (₩)',
            'kwd' => 'Kuwaiti Dinar (KWD)',
            'kyd' => 'Cayman Islands Dollar ($)',
            'kzt' => 'Kazakhstan Tenge (KZT)',
            'lkr' => 'Sri Lanka Rupee (ரூ)',
            'mad' => 'Moroccan Dirham (MAD)',
            'mdl' => 'Moldovan Leu (MDL)',
            'mkd' => 'Macedonian Denar (MKD)',
            'mmk' => 'Myanmar Kyat (K)',
            'mnt' => 'Mongolian Tugrik (₮)',
            'mop' => 'Macau Pataca ($)',
            'mro' => 'Mauritania Ougulya (UM)',
            'mur' => 'Mauritius Rupee (₨)',
            'mvr' => 'Maldives Rufiyaa (Rf)',
            'mwk' => 'Malawi Kwacha (MK)',
            'mxn' => 'Mexican Peso ($)',
            'myr' => 'Malaysian Ringgit (RM)',
            'nad' => 'Namibian Dollar (N$)',
            'ngn' => 'Nigerian Naira (₦)',
            'nio' => 'Nicaragua Cordoba (C$)',
            'nok' => 'Norwegian Krone (kr)',
            'npr' => 'Nepalese Rupee (₨)',
            'nzd' => 'New Zealand Dollar ($)',
            'omr' => 'Omani Rial (OMR)',
            'pab' => 'Panama Balboa (B)',
            'pen' => 'Peruvian Nuevo Sol (PEN)',
            'pgk' => 'Papua New Guinea Kina (K)',
            'php' => 'Philippine Peso (₱)',
            'pkr' => 'Pakistani Rupee (Rs)',
            'pln' => 'Polish Zloty (zł)',
            'qar' => 'Qatar Rial (QAR)',
            'ron' => 'Romanian New Leu (L)',
            'rub' => 'Russian Rouble (руб)',
            'rwf' => 'Rwanda Franc (RF)',
            'sar' => 'Saudi Arabian Riyal (SAR)',
            'sbd' => 'Solomon Islands Dollar (SI$)',
            'scr' => 'Seychelles Rupee (SR)',
            'sdg' => 'Sudanese Pound (SDG)',
            'sek' => 'Swedish Krona (kr)',
            'sgd' => 'Singapore Dollar (S$)',
            'shp' => 'St Helena Pound (£)',
            'skk' => 'Slovak Koruna (Sk)',
            'sll' => 'Sierra Leone Leone (Le)',
            'sos' => 'Somali Shilling (So)',
            'std' => 'Sao Tome Dobra (Db)',
            'svc' => 'El Salvador Colon (₡)',
            'syp' => 'Syrian Pound (SYP)',
            'szl' => 'Swaziland Lilageni (SZL)',
            'thb' => 'Thai Baht (฿)',
            'tnd' => 'Tunisian Dinar (TND)',
            'top' => 'Tonga Paang (T$)',
            'try' => 'Turkish Lira (YTL)',
            'ttd' => 'Trinidad Tobago Dollar (TTD)',
            'twd' => 'Taiwan Dollar (NT$)',
            'tzs' => 'Tanzanian Shilling (x)',
            'ugx' => 'Ugandan Shilling (USh)',
            'usd' => 'United States Dollar ($)',
            'uyu' => 'Uruguayan New Peso (UYU)',
            'uzs' => 'Uzbekistan Sum (UZS)',
            'vef' => 'Venezuelan Bolivar (VEF)',
            'vnd' => 'Vietnam Dong (₫)',
            'vuv' => 'Vanuatu Vatu (Vt)',
            'wst' => 'Samoa Tala (WS$)',
            'xaf' => 'CFA Franc (BEAC) (BEAC)',
            'xcd' => 'East Caribbean Dollar (EC$)',
            'xof' => 'CFA Franc (BCEAO) (BCEAO)',
            'xpf' => 'Pacific Franc (F)',
            'yer' => 'Yemen Riyal (YER)',
            'zar' => 'South African Rand (R)',
            'zmk' => 'Zambian Kwacha (ZMK)'
        );
    }

    /**
     * Generates an absolute URL for a request, given the request name and an optional array of arguments.
     *
     * @param string $request
     * @param (string|int)[] $args
     * @return string
     */
    protected static function uri($request, $args = array()) {
        //$uri = 'https://keywordseverywhere.com/service/1/' . $request . '.php';
        $uri = 'https://api.getseostudio.com/v1/keywords/retrieve';
        if (!empty($args)) $uri .= '?' . http_build_query($args);
        return $uri;
    }

    /**
     * Performs a GET request with JSON encoding and returns the parsed response.
     *
     * @param string $uri
     * @return mixed
     *
     * @throws SEOException Code `1` upon a connection error
     * @throws SEOException Code `2` upon a response which is not OK (~200)
     * @throws SEOException Code `3` upon a JSON parse error
     */
    protected static function get($uri) {
        global $studio;
        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $studio->getopt('api.secretkey')
        ]);

        $data = @curl_exec($ch);
        if (curl_errno($ch) > 0) throw new SEOException("Connect error", 1);
        if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) != 200) throw new SEOException('API error', 2);

        $parsed = @json_decode($data, true);
        if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) throw new SEOException('Parse error', 3);

        return $parsed;
    }

}
