<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

/**
 * Provides methods for retrieving lists of geological data.
 *
 * Each of the public methods allows two arguments:
 *
 * @param $as_json          - bool     - Whether the data should be returned as JSON instead of an array.
 * @param $process_callback - callable - An optional callback that takes the data as a parameter and returns a modified version.
 */
class GeoData {

	/**
	 * Provides a list of Countries organized by their 2-character country codes.
	 *
	 * @return array
	 */
	private static function countries_list() {
		return array(
			'AF' => __( 'Afghanistan', 'gravitytools' ),
			'AX' => __( 'Åland Islands', 'gravitytools' ),
			'AL' => __( 'Albania', 'gravitytools' ),
			'DZ' => __( 'Algeria', 'gravitytools' ),
			'AS' => __( 'American Samoa', 'gravitytools' ),
			'AD' => __( 'Andorra', 'gravitytools' ),
			'AO' => __( 'Angola', 'gravitytools' ),
			'AI' => __( 'Anguilla', 'gravitytools' ),
			'AQ' => __( 'Antarctica', 'gravitytools' ),
			'AG' => __( 'Antigua and Barbuda', 'gravitytools' ),
			'AR' => __( 'Argentina', 'gravitytools' ),
			'AM' => __( 'Armenia', 'gravitytools' ),
			'AW' => __( 'Aruba', 'gravitytools' ),
			'AC' => __( 'Ascension Island', 'gravitytools' ),
			'AU' => __( 'Australia', 'gravitytools' ),
			'AT' => __( 'Austria', 'gravitytools' ),
			'AZ' => __( 'Azerbaijan', 'gravitytools' ),
			'BS' => __( 'Bahamas', 'gravitytools' ),
			'BH' => __( 'Bahrain', 'gravitytools' ),
			'BD' => __( 'Bangladesh', 'gravitytools' ),
			'BB' => __( 'Barbados', 'gravitytools' ),
			'BY' => __( 'Belarus', 'gravitytools' ),
			'BE' => __( 'Belgium', 'gravitytools' ),
			'PW' => __( 'Belau', 'gravitytools' ),
			'BZ' => __( 'Belize', 'gravitytools' ),
			'BJ' => __( 'Benin', 'gravitytools' ),
			'BM' => __( 'Bermuda', 'gravitytools' ),
			'BT' => __( 'Bhutan', 'gravitytools' ),
			'BO' => __( 'Bolivia', 'gravitytools' ),
			'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'gravitytools' ),
			'BA' => __( 'Bosnia and Herzegovina', 'gravitytools' ),
			'BW' => __( 'Botswana', 'gravitytools' ),
			'BV' => __( 'Bouvet Island', 'gravitytools' ),
			'BR' => __( 'Brazil', 'gravitytools' ),
			'IO' => __( 'British Indian Ocean Territory', 'gravitytools' ),
			'BN' => __( 'Brunei', 'gravitytools' ),
			'BG' => __( 'Bulgaria', 'gravitytools' ),
			'BF' => __( 'Burkina Faso', 'gravitytools' ),
			'BI' => __( 'Burundi', 'gravitytools' ),
			'KH' => __( 'Cambodia', 'gravitytools' ),
			'CM' => __( 'Cameroon', 'gravitytools' ),
			'CA' => __( 'Canada', 'gravitytools' ),
			'CV' => __( 'Cape Verde', 'gravitytools' ),
			'KY' => __( 'Cayman Islands', 'gravitytools' ),
			'CF' => __( 'Central African Republic', 'gravitytools' ),
			'TD' => __( 'Chad', 'gravitytools' ),
			'CL' => __( 'Chile', 'gravitytools' ),
			'CN' => __( 'China', 'gravitytools' ),
			'CX' => __( 'Christmas Island', 'gravitytools' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'gravitytools' ),
			'CO' => __( 'Colombia', 'gravitytools' ),
			'KM' => __( 'Comoros', 'gravitytools' ),
			'CG' => __( 'Congo (Brazzaville)', 'gravitytools' ),
			'CD' => __( 'Congo (Kinshasa)', 'gravitytools' ),
			'CK' => __( 'Cook Islands', 'gravitytools' ),
			'CR' => __( 'Costa Rica', 'gravitytools' ),
			'HR' => __( 'Croatia', 'gravitytools' ),
			'CU' => __( 'Cuba', 'gravitytools' ),
			'CW' => __( 'Cura&ccedil;ao', 'gravitytools' ),
			'CY' => __( 'Cyprus', 'gravitytools' ),
			'CZ' => __( 'Czech Republic', 'gravitytools' ),
			'DK' => __( 'Denmark', 'gravitytools' ),
			'DJ' => __( 'Djibouti', 'gravitytools' ),
			'DM' => __( 'Dominica', 'gravitytools' ),
			'DO' => __( 'Dominican Republic', 'gravitytools' ),
			'EC' => __( 'Ecuador', 'gravitytools' ),
			'EG' => __( 'Egypt', 'gravitytools' ),
			'SV' => __( 'El Salvador', 'gravitytools' ),
			'GQ' => __( 'Equatorial Guinea', 'gravitytools' ),
			'ER' => __( 'Eritrea', 'gravitytools' ),
			'EE' => __( 'Estonia', 'gravitytools' ),
			'ET' => __( 'Ethiopia', 'gravitytools' ),
			'FK' => __( 'Falkland Islands', 'gravitytools' ),
			'FO' => __( 'Faroe Islands', 'gravitytools' ),
			'FJ' => __( 'Fiji', 'gravitytools' ),
			'FI' => __( 'Finland', 'gravitytools' ),
			'FR' => __( 'France', 'gravitytools' ),
			'GF' => __( 'French Guiana', 'gravitytools' ),
			'PF' => __( 'French Polynesia', 'gravitytools' ),
			'TF' => __( 'French Southern Territories', 'gravitytools' ),
			'GA' => __( 'Gabon', 'gravitytools' ),
			'GM' => __( 'Gambia', 'gravitytools' ),
			'GE' => __( 'Georgia', 'gravitytools' ),
			'DE' => __( 'Germany', 'gravitytools' ),
			'GH' => __( 'Ghana', 'gravitytools' ),
			'GI' => __( 'Gibraltar', 'gravitytools' ),
			'GR' => __( 'Greece', 'gravitytools' ),
			'GL' => __( 'Greenland', 'gravitytools' ),
			'GD' => __( 'Grenada', 'gravitytools' ),
			'GP' => __( 'Guadeloupe', 'gravitytools' ),
			'GU' => __( 'Guam', 'gravitytools' ),
			'GT' => __( 'Guatemala', 'gravitytools' ),
			'GG' => __( 'Guernsey', 'gravitytools' ),
			'GN' => __( 'Guinea', 'gravitytools' ),
			'GW' => __( 'Guinea-Bissau', 'gravitytools' ),
			'GY' => __( 'Guyana', 'gravitytools' ),
			'HT' => __( 'Haiti', 'gravitytools' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'gravitytools' ),
			'HN' => __( 'Honduras', 'gravitytools' ),
			'HK' => __( 'Hong Kong', 'gravitytools' ),
			'HU' => __( 'Hungary', 'gravitytools' ),
			'IS' => __( 'Iceland', 'gravitytools' ),
			'IN' => __( 'India', 'gravitytools' ),
			'ID' => __( 'Indonesia', 'gravitytools' ),
			'IR' => __( 'Iran', 'gravitytools' ),
			'IQ' => __( 'Iraq', 'gravitytools' ),
			'IE' => __( 'Ireland', 'gravitytools' ),
			'IM' => __( 'Isle of Man', 'gravitytools' ),
			'IL' => __( 'Israel', 'gravitytools' ),
			'IT' => __( 'Italy', 'gravitytools' ),
			'CI' => __( 'Ivory Coast', 'gravitytools' ),
			'JM' => __( 'Jamaica', 'gravitytools' ),
			'JP' => __( 'Japan', 'gravitytools' ),
			'JE' => __( 'Jersey', 'gravitytools' ),
			'JO' => __( 'Jordan', 'gravitytools' ),
			'KZ' => __( 'Kazakhstan', 'gravitytools' ),
			'KE' => __( 'Kenya', 'gravitytools' ),
			'KI' => __( 'Kiribati', 'gravitytools' ),
			'XK' => __( 'Kosovo', 'gravitytools' ),
			'KW' => __( 'Kuwait', 'gravitytools' ),
			'KG' => __( 'Kyrgyzstan', 'gravitytools' ),
			'LA' => __( 'Laos', 'gravitytools' ),
			'LV' => __( 'Latvia', 'gravitytools' ),
			'LB' => __( 'Lebanon', 'gravitytools' ),
			'LS' => __( 'Lesotho', 'gravitytools' ),
			'LR' => __( 'Liberia', 'gravitytools' ),
			'LY' => __( 'Libya', 'gravitytools' ),
			'LI' => __( 'Liechtenstein', 'gravitytools' ),
			'LT' => __( 'Lithuania', 'gravitytools' ),
			'LU' => __( 'Luxembourg', 'gravitytools' ),
			'MO' => __( 'Macao', 'gravitytools' ),
			'MK' => __( 'North Macedonia', 'gravitytools' ),
			'MG' => __( 'Madagascar', 'gravitytools' ),
			'MW' => __( 'Malawi', 'gravitytools' ),
			'MY' => __( 'Malaysia', 'gravitytools' ),
			'MV' => __( 'Maldives', 'gravitytools' ),
			'ML' => __( 'Mali', 'gravitytools' ),
			'MT' => __( 'Malta', 'gravitytools' ),
			'MH' => __( 'Marshall Islands', 'gravitytools' ),
			'MQ' => __( 'Martinique', 'gravitytools' ),
			'MR' => __( 'Mauritania', 'gravitytools' ),
			'MU' => __( 'Mauritius', 'gravitytools' ),
			'YT' => __( 'Mayotte', 'gravitytools' ),
			'MX' => __( 'Mexico', 'gravitytools' ),
			'FM' => __( 'Micronesia', 'gravitytools' ),
			'MD' => __( 'Moldova', 'gravitytools' ),
			'MC' => __( 'Monaco', 'gravitytools' ),
			'MN' => __( 'Mongolia', 'gravitytools' ),
			'ME' => __( 'Montenegro', 'gravitytools' ),
			'MS' => __( 'Montserrat', 'gravitytools' ),
			'MA' => __( 'Morocco', 'gravitytools' ),
			'MZ' => __( 'Mozambique', 'gravitytools' ),
			'MM' => __( 'Myanmar', 'gravitytools' ),
			'NA' => __( 'Namibia', 'gravitytools' ),
			'NR' => __( 'Nauru', 'gravitytools' ),
			'NP' => __( 'Nepal', 'gravitytools' ),
			'NL' => __( 'Netherlands', 'gravitytools' ),
			'NC' => __( 'New Caledonia', 'gravitytools' ),
			'NZ' => __( 'New Zealand', 'gravitytools' ),
			'NI' => __( 'Nicaragua', 'gravitytools' ),
			'NE' => __( 'Niger', 'gravitytools' ),
			'NG' => __( 'Nigeria', 'gravitytools' ),
			'NU' => __( 'Niue', 'gravitytools' ),
			'NF' => __( 'Norfolk Island', 'gravitytools' ),
			'MP' => __( 'Northern Mariana Islands', 'gravitytools' ),
			'KP' => __( 'North Korea', 'gravitytools' ),
			'NO' => __( 'Norway', 'gravitytools' ),
			'OM' => __( 'Oman', 'gravitytools' ),
			'PK' => __( 'Pakistan', 'gravitytools' ),
			'PS' => __( 'Palestinian Territory', 'gravitytools' ),
			'PA' => __( 'Panama', 'gravitytools' ),
			'PG' => __( 'Papua New Guinea', 'gravitytools' ),
			'PY' => __( 'Paraguay', 'gravitytools' ),
			'PE' => __( 'Peru', 'gravitytools' ),
			'PH' => __( 'Philippines', 'gravitytools' ),
			'PN' => __( 'Pitcairn', 'gravitytools' ),
			'PL' => __( 'Poland', 'gravitytools' ),
			'PT' => __( 'Portugal', 'gravitytools' ),
			'PR' => __( 'Puerto Rico', 'gravitytools' ),
			'QA' => __( 'Qatar', 'gravitytools' ),
			'RE' => __( 'Reunion', 'gravitytools' ),
			'RO' => __( 'Romania', 'gravitytools' ),
			'RU' => __( 'Russia', 'gravitytools' ),
			'RW' => __( 'Rwanda', 'gravitytools' ),
			'BL' => __( 'Saint Barth&eacute;lemy', 'gravitytools' ),
			'SH' => __( 'Saint Helena', 'gravitytools' ),
			'KN' => __( 'Saint Kitts and Nevis', 'gravitytools' ),
			'LC' => __( 'Saint Lucia', 'gravitytools' ),
			'MF' => __( 'Saint Martin (French part)', 'gravitytools' ),
			'SX' => __( 'Saint Martin (Dutch part)', 'gravitytools' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'gravitytools' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'gravitytools' ),
			'SM' => __( 'San Marino', 'gravitytools' ),
			'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'gravitytools' ),
			'SA' => __( 'Saudi Arabia', 'gravitytools' ),
			'SN' => __( 'Senegal', 'gravitytools' ),
			'RS' => __( 'Serbia', 'gravitytools' ),
			'SC' => __( 'Seychelles', 'gravitytools' ),
			'SL' => __( 'Sierra Leone', 'gravitytools' ),
			'SG' => __( 'Singapore', 'gravitytools' ),
			'SK' => __( 'Slovakia', 'gravitytools' ),
			'SI' => __( 'Slovenia', 'gravitytools' ),
			'SB' => __( 'Solomon Islands', 'gravitytools' ),
			'SO' => __( 'Somalia', 'gravitytools' ),
			'ZA' => __( 'South Africa', 'gravitytools' ),
			'GS' => __( 'South Georgia/Sandwich Islands', 'gravitytools' ),
			'KR' => __( 'South Korea', 'gravitytools' ),
			'SS' => __( 'South Sudan', 'gravitytools' ),
			'ES' => __( 'Spain', 'gravitytools' ),
			'LK' => __( 'Sri Lanka', 'gravitytools' ),
			'SD' => __( 'Sudan', 'gravitytools' ),
			'SR' => __( 'Suriname', 'gravitytools' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'gravitytools' ),
			'SZ' => __( 'Eswatini', 'gravitytools' ),
			'SE' => __( 'Sweden', 'gravitytools' ),
			'CH' => __( 'Switzerland', 'gravitytools' ),
			'SY' => __( 'Syria', 'gravitytools' ),
			'TW' => __( 'Taiwan', 'gravitytools' ),
			'TJ' => __( 'Tajikistan', 'gravitytools' ),
			'TZ' => __( 'Tanzania', 'gravitytools' ),
			'TH' => __( 'Thailand', 'gravitytools' ),
			'TL' => __( 'Timor-Leste', 'gravitytools' ),
			'TG' => __( 'Togo', 'gravitytools' ),
			'TK' => __( 'Tokelau', 'gravitytools' ),
			'TO' => __( 'Tonga', 'gravitytools' ),
			'TT' => __( 'Trinidad and Tobago', 'gravitytools' ),
			'TN' => __( 'Tunisia', 'gravitytools' ),
			'TR' => __( 'Turkey', 'gravitytools' ),
			'TM' => __( 'Turkmenistan', 'gravitytools' ),
			'TC' => __( 'Turks and Caicos Islands', 'gravitytools' ),
			'TV' => __( 'Tuvalu', 'gravitytools' ),
			'UG' => __( 'Uganda', 'gravitytools' ),
			'UA' => __( 'Ukraine', 'gravitytools' ),
			'AE' => __( 'United Arab Emirates', 'gravitytools' ),
			'GB' => __( 'United Kingdom (UK)', 'gravitytools' ),
			'US' => __( 'United States (US)', 'gravitytools' ),
			'UM' => __( 'United States (US) Minor Outlying Islands', 'gravitytools' ),
			'UY' => __( 'Uruguay', 'gravitytools' ),
			'UZ' => __( 'Uzbekistan', 'gravitytools' ),
			'VU' => __( 'Vanuatu', 'gravitytools' ),
			'VA' => __( 'Vatican', 'gravitytools' ),
			'VE' => __( 'Venezuela', 'gravitytools' ),
			'VN' => __( 'Vietnam', 'gravitytools' ),
			'VG' => __( 'Virgin Islands (British)', 'gravitytools' ),
			'VI' => __( 'Virgin Islands (US)', 'gravitytools' ),
			'WF' => __( 'Wallis and Futuna', 'gravitytools' ),
			'EH' => __( 'Western Sahara', 'gravitytools' ),
			'WS' => __( 'Samoa', 'gravitytools' ),
			'YE' => __( 'Yemen', 'gravitytools' ),
			'ZM' => __( 'Zambia', 'gravitytools' ),
			'ZW' => __( 'Zimbabwe', 'gravitytools' ),
		);
	}

	/**
	 * Provides a list of US states organized by their two-character state code.
	 *
	 * @return array
	 */
	private static function states_list() {
		return array(
			'AL' => __( 'Alabama', 'gravitytools' ),
			'AK' => __( 'Alaska', 'gravitytools' ),
			'AZ' => __( 'Arizona', 'gravitytools' ),
			'AR' => __( 'Arkansas', 'gravitytools' ),
			'CA' => __( 'California', 'gravitytools' ),
			'CO' => __( 'Colorado', 'gravitytools' ),
			'CT' => __( 'Connecticut', 'gravitytools' ),
			'DE' => __( 'Delaware', 'gravitytools' ),
			'DC' => __( 'District of Columbia', 'gravitytools' ),
			'FL' => __( 'Florida', 'gravitytools' ),
			'GA' => __( 'Georgia', 'gravitytools' ),
			'HI' => __( 'Hawaii', 'gravitytools' ),
			'ID' => __( 'Idaho', 'gravitytools' ),
			'IL' => __( 'Illinois', 'gravitytools' ),
			'IN' => __( 'Indiana', 'gravitytools' ),
			'IA' => __( 'Iowa', 'gravitytools' ),
			'KS' => __( 'Kansas', 'gravitytools' ),
			'KY' => __( 'Kentucky', 'gravitytools' ),
			'LA' => __( 'Louisiana', 'gravitytools' ),
			'ME' => __( 'Maine', 'gravitytools' ),
			'MD' => __( 'Maryland', 'gravitytools' ),
			'MA' => __( 'Massachusetts', 'gravitytools' ),
			'MI' => __( 'Michigan', 'gravitytools' ),
			'MN' => __( 'Minnesota', 'gravitytools' ),
			'MS' => __( 'Mississippi', 'gravitytools' ),
			'MO' => __( 'Missouri', 'gravitytools' ),
			'MT' => __( 'Montana', 'gravitytools' ),
			'NE' => __( 'Nebraska', 'gravitytools' ),
			'NV' => __( 'Nevada', 'gravitytools' ),
			'NH' => __( 'New Hampshire', 'gravitytools' ),
			'NJ' => __( 'New Jersey', 'gravitytools' ),
			'NM' => __( 'New Mexico', 'gravitytools' ),
			'NY' => __( 'New York', 'gravitytools' ),
			'NC' => __( 'North Carolina', 'gravitytools' ),
			'ND' => __( 'North Dakota', 'gravitytools' ),
			'OH' => __( 'Ohio', 'gravitytools' ),
			'OK' => __( 'Oklahoma', 'gravitytools' ),
			'OR' => __( 'Oregon', 'gravitytools' ),
			'PA' => __( 'Pennsylvania', 'gravitytools' ),
			'RI' => __( 'Rhode Island', 'gravitytools' ),
			'SC' => __( 'South Carolina', 'gravitytools' ),
			'SD' => __( 'South Dakota', 'gravitytools' ),
			'TN' => __( 'Tennessee', 'gravitytools' ),
			'TX' => __( 'Texas', 'gravitytools' ),
			'UT' => __( 'Utah', 'gravitytools' ),
			'VT' => __( 'Vermont', 'gravitytools' ),
			'VA' => __( 'Virginia', 'gravitytools' ),
			'WA' => __( 'Washington', 'gravitytools' ),
			'WV' => __( 'West Virginia', 'gravitytools' ),
			'WI' => __( 'Wisconsin', 'gravitytools' ),
			'WY' => __( 'Wyoming', 'gravitytools' ),
		);
	}

	/**
	 * Provides a list of Canadian provinces, organized by their two-character province code.
	 *
	 * @return array
	 */
	private static function provinces_list() {
		return array(
			'AB' => __( 'Alberta', 'gravitytools' ),
			'BC' => __( 'British Columbia', 'gravitytools' ),
			'MB' => __( 'Manitoba', 'gravitytools' ),
			'NB' => __( 'New Brunswick', 'gravitytools' ),
			'NL' => __( 'Newfoundland and Labrador', 'gravitytools' ),
			'NS' => __( 'Nova Scotia', 'gravitytools' ),
			'NT' => __( 'Northwest Territories', 'gravitytools' ),
			'NU' => __( 'Nunavut', 'gravitytools' ),
			'ON' => __( 'Ontario', 'gravitytools' ),
			'PE' => __( 'Prince Edward Island', 'gravitytools' ),
			'QC' => __( 'Quebec', 'gravitytools' ),
			'SK' => __( 'Saskatchewan', 'gravitytools' ),
			'YT' => __( 'Yukon', 'gravitytools' ),
		);
	}

	/**
	 * Provides a list of phone number formatting info.
	 *
	 * @return array
	 */
	private static function phone_list() {
		$countries = self::countries_list();

		$data = array(
			array( 'AF', '93', '🇦🇫' ),
			array( 'AX', '358', '🇦🇽' ),
			array( 'AL', '355', '🇦🇱' ),
			array( 'DZ', '213', '🇩🇿' ),
			array( 'AS', '1', '🇦🇸' ),
			array( 'AD', '376', '🇦🇩' ),
			array( 'AO', '244', '🇦🇴' ),
			array( 'AI', '1', '🇦🇮' ),
			array( 'AG', '1', '🇦🇬' ),
			array( 'AR', '54', '🇦🇷' ),
			array( 'AM', '374', '🇦🇲' ),
			array( 'AW', '297', '🇦🇼' ),
			array( 'AC', '247', '🇦🇨' ),
			array( 'AU', '61', '🇦🇺' ),
			array( 'AT', '43', '🇦🇹' ),
			array( 'AZ', '994', '🇦🇿' ),
			array( 'BS', '1', '🇧🇸' ),
			array( 'BH', '973', '🇧🇭' ),
			array( 'BD', '880', '🇧🇩' ),
			array( 'BB', '1', '🇧🇧' ),
			array( 'BY', '375', '🇧🇾' ),
			array( 'BE', '32', '🇧🇪' ),
			array( 'BZ', '501', '🇧🇿' ),
			array( 'BJ', '229', '🇧🇯' ),
			array( 'BM', '1', '🇧🇲' ),
			array( 'BT', '975', '🇧🇹' ),
			array( 'BO', '591', '🇧🇴' ),
			array( 'BA', '387', '🇧🇦' ),
			array( 'BW', '267', '🇧🇼' ),
			array( 'BR', '55', '🇧🇷' ),
			array( 'IO', '246', '🇮🇴' ),
			array( 'VG', '1', '🇻🇬' ),
			array( 'BN', '673', '🇧🇳' ),
			array( 'BG', '359', '🇧🇬' ),
			array( 'BF', '226', '🇧🇫' ),
			array( 'BI', '257', '🇧🇮' ),
			array( 'KH', '855', '🇰🇭' ),
			array( 'CM', '237', '🇨🇲' ),
			array( 'CA', '1', '🇨🇦' ),
			array( 'CV', '238', '🇨🇻' ),
			array( 'BQ', '599', '🇧🇶' ),
			array( 'KY', '1', '🇰🇾' ),
			array( 'CF', '236', '🇨🇫' ),
			array( 'TD', '235', '🇹🇩' ),
			array( 'CL', '56', '🇨🇱' ),
			array( 'CN', '86', '🇨🇳' ),
			array( 'CX', '61', '🇨🇽' ),
			array( 'CC', '61', '🇨🇨' ),
			array( 'CO', '57', '🇨🇴' ),
			array( 'KM', '269', '🇰🇲' ),
			array( 'CG', '242', '🇨🇬' ),
			array( 'CD', '243', '🇨🇩' ),
			array( 'CK', '682', '🇨🇰' ),
			array( 'CR', '506', '🇨🇷' ),
			array( 'CI', '225', '🇨🇮' ),
			array( 'HR', '385', '🇭🇷' ),
			array( 'CU', '53', '🇨🇺' ),
			array( 'CW', '599', '🇨🇼' ),
			array( 'CY', '357', '🇨🇾' ),
			array( 'CZ', '420', '🇨🇿' ),
			array( 'DK', '45', '🇩🇰' ),
			array( 'DJ', '253', '🇩🇯' ),
			array( 'DM', '1', '🇩🇲' ),
			array( 'DO', '1', '🇩🇴' ),
			array( 'EC', '593', '🇪🇨' ),
			array( 'EG', '20', '🇪🇬' ),
			array( 'SV', '503', '🇸🇻' ),
			array( 'GQ', '240', '🇬🇶' ),
			array( 'ER', '291', '🇪🇷' ),
			array( 'EE', '372', '🇪🇪' ),
			array( 'SZ', '268', '🇸🇿' ),
			array( 'ET', '251', '🇪🇹' ),
			array( 'FK', '500', '🇫🇰' ),
			array( 'FO', '298', '🇫🇴' ),
			array( 'FJ', '679', '🇫🇯' ),
			array( 'FI', '358', '🇫🇮' ),
			array( 'FR', '33', '🇫🇷' ),
			array( 'GF', '594', '🇬🇫' ),
			array( 'PF', '689', '🇵🇫' ),
			array( 'GA', '241', '🇬🇦' ),
			array( 'GM', '220', '🇬🇲' ),
			array( 'GE', '995', '🇬🇪' ),
			array( 'DE', '49', '🇩🇪' ),
			array( 'GH', '233', '🇬🇭' ),
			array( 'GI', '350', '🇬🇮' ),
			array( 'GR', '30', '🇬🇷' ),
			array( 'GL', '299', '🇬🇱' ),
			array( 'GD', '1', '🇬🇩' ),
			array( 'GP', '590', '🇬🇵' ),
			array( 'GU', '1', '🇬🇺' ),
			array( 'GT', '502', '🇬🇹' ),
			array( 'GG', '44', '🇬🇬' ),
			array( 'GN', '224', '🇬🇳' ),
			array( 'GW', '245', '🇬🇼' ),
			array( 'GY', '592', '🇬🇾' ),
			array( 'HT', '509', '🇭🇹' ),
			array( 'HN', '504', '🇭🇳' ),
			array( 'HK', '852', '🇭🇰' ),
			array( 'HU', '36', '🇭🇺' ),
			array( 'IS', '354', '🇮🇸' ),
			array( 'IN', '91', '🇮🇳' ),
			array( 'ID', '62', '🇮🇩' ),
			array( 'IR', '98', '🇮🇷' ),
			array( 'IQ', '964', '🇮🇶' ),
			array( 'IE', '353', '🇮🇪' ),
			array( 'IM', '44', '🇮🇲' ),
			array( 'IL', '972', '🇮🇱' ),
			array( 'IT', '39', '🇮🇹' ),
			array( 'JM', '1', '🇯🇲' ),
			array( 'JP', '81', '🇯🇵' ),
			array( 'JE', '44', '🇯🇪' ),
			array( 'JO', '962', '🇯🇴' ),
			array( 'KZ', '7', '🇰🇿' ),
			array( 'KE', '254', '🇰🇪' ),
			array( 'KI', '686', '🇰🇮' ),
			array( 'XK', '383', '🇽🇰' ),
			array( 'KW', '965', '🇰🇼' ),
			array( 'KG', '996', '🇰🇬' ),
			array( 'LA', '856', '🇱🇦' ),
			array( 'LV', '371', '🇱🇻' ),
			array( 'LB', '961', '🇱🇧' ),
			array( 'LS', '266', '🇱🇸' ),
			array( 'LR', '231', '🇱🇷' ),
			array( 'LY', '218', '🇱🇾' ),
			array( 'LI', '423', '🇱🇮' ),
			array( 'LT', '370', '🇱🇹' ),
			array( 'LU', '352', '🇱🇺' ),
			array( 'MO', '853', '🇲🇴' ),
			array( 'MG', '261', '🇲🇬' ),
			array( 'MW', '265', '🇲🇼' ),
			array( 'MY', '60', '🇲🇾' ),
			array( 'MV', '960', '🇲🇻' ),
			array( 'ML', '223', '🇲🇱' ),
			array( 'MT', '356', '🇲🇹' ),
			array( 'MH', '692', '🇲🇭' ),
			array( 'MQ', '596', '🇲🇶' ),
			array( 'MR', '222', '🇲🇷' ),
			array( 'MU', '230', '🇲🇺' ),
			array( 'YT', '262', '🇾🇹' ),
			array( 'MX', '52', '🇲🇽' ),
			array( 'FM', '691', '🇫🇲' ),
			array( 'MD', '373', '🇲🇩' ),
			array( 'MC', '377', '🇲🇨' ),
			array( 'MN', '976', '🇲🇳' ),
			array( 'ME', '382', '🇲🇪' ),
			array( 'MS', '1', '🇲🇸' ),
			array( 'MA', '212', '🇲🇦' ),
			array( 'MZ', '258', '🇲🇿' ),
			array( 'MM', '95', '🇲🇲' ),
			array( 'NA', '264', '🇳🇦' ),
			array( 'NR', '674', '🇳🇷' ),
			array( 'NP', '977', '🇳🇵' ),
			array( 'NL', '31', '🇳🇱' ),
			array( 'NC', '687', '🇳🇨' ),
			array( 'NZ', '64', '🇳🇿' ),
			array( 'NI', '505', '🇳🇮' ),
			array( 'NE', '227', '🇳🇪' ),
			array( 'NG', '234', '🇳🇬' ),
			array( 'NU', '683', '🇳🇺' ),
			array( 'NF', '672', '🇳🇫' ),
			array( 'KP', '850', '🇰🇵' ),
			array( 'MK', '389', '🇲🇰' ),
			array( 'MP', '1', '🇲🇵' ),
			array( 'NO', '47', '🇳🇴' ),
			array( 'OM', '968', '🇴🇲' ),
			array( 'PK', '92', '🇵🇰' ),
			array( 'PW', '680', '🇵🇼' ),
			array( 'PS', '970', '🇵🇸' ),
			array( 'PA', '507', '🇵🇦' ),
			array( 'PG', '675', '🇵🇬' ),
			array( 'PY', '595', '🇵🇾' ),
			array( 'PE', '51', '🇵🇪' ),
			array( 'PH', '63', '🇵🇭' ),
			array( 'PL', '48', '🇵🇱' ),
			array( 'PT', '351', '🇵🇹' ),
			array( 'PR', '1', '🇵🇷' ),
			array( 'QA', '974', '🇶🇦' ),
			array( 'RE', '262', '🇷🇪' ),
			array( 'RO', '40', '🇷🇴' ),
			array( 'RU', '7', '🇷🇺' ),
			array( 'RW', '250', '🇷🇼' ),
			array( 'WS', '685', '🇼🇸' ),
			array( 'SM', '378', '🇸🇲' ),
			array( 'ST', '239', '🇸🇹' ),
			array( 'SA', '966', '🇸🇦' ),
			array( 'SN', '221', '🇸🇳' ),
			array( 'RS', '381', '🇷🇸' ),
			array( 'SC', '248', '🇸🇨' ),
			array( 'SL', '232', '🇸🇱' ),
			array( 'SG', '65', '🇸🇬' ),
			array( 'SX', '1', '🇸🇽' ),
			array( 'SK', '421', '🇸🇰' ),
			array( 'SI', '386', '🇸🇮' ),
			array( 'SB', '677', '🇸🇧' ),
			array( 'SO', '252', '🇸🇴' ),
			array( 'ZA', '27', '🇿🇦' ),
			array( 'KR', '82', '🇰🇷' ),
			array( 'SS', '211', '🇸🇸' ),
			array( 'ES', '34', '🇪🇸' ),
			array( 'LK', '94', '🇱🇰' ),
			array( 'BL', '590', '🇧🇱' ),
			array( 'SH', '290', '🇸🇭' ),
			array( 'KN', '1', '🇰🇳' ),
			array( 'LC', '1', '🇱🇨' ),
			array( 'MF', '590', '🇲🇫' ),
			array( 'PM', '508', '🇵🇲' ),
			array( 'VC', '1', '🇻🇨' ),
			array( 'SD', '249', '🇸🇩' ),
			array( 'SR', '597', '🇸🇷' ),
			array( 'SJ', '47', '🇸🇯' ),
			array( 'SE', '46', '🇸🇪' ),
			array( 'CH', '41', '🇨🇭' ),
			array( 'SY', '963', '🇸🇾' ),
			array( 'TW', '886', '🇹🇼' ),
			array( 'TJ', '992', '🇹🇯' ),
			array( 'TZ', '255', '🇹🇿' ),
			array( 'TH', '66', '🇹🇭' ),
			array( 'TL', '670', '🇹🇱' ),
			array( 'TG', '228', '🇹🇬' ),
			array( 'TK', '690', '🇹🇰' ),
			array( 'TO', '676', '🇹🇴' ),
			array( 'TT', '1', '🇹🇹' ),
			array( 'TN', '216', '🇹🇳' ),
			array( 'TR', '90', '🇹🇷' ),
			array( 'TM', '993', '🇹🇲' ),
			array( 'TC', '1', '🇹🇨' ),
			array( 'TV', '688', '🇹🇻' ),
			array( 'UG', '256', '🇺🇬' ),
			array( 'UA', '380', '🇺🇦' ),
			array( 'AE', '971', '🇦🇪' ),
			array( 'GB', '44', '🇬🇧' ),
			array( 'US', '1', '🇺🇸' ),
			array( 'UY', '598', '🇺🇾' ),
			array( 'VI', '1', '🇻🇮' ),
			array( 'UZ', '998', '🇺🇿' ),
			array( 'VU', '678', '🇻🇺' ),
			array( 'VA', '39', '🇻🇦' ),
			array( 'VE', '58', '🇻🇪' ),
			array( 'VN', '84', '🇻🇳' ),
			array( 'WF', '681', '🇼🇫' ),
			array( 'EH', '212', '🇪🇭' ),
			array( 'YE', '967', '🇾🇪' ),
			array( 'ZM', '260', '🇿🇲' ),
			array( 'ZW', '263', '🇿🇼' ),
		);

		return array_map( function ( $item ) use ( $countries ) {
			return array(
				'iso'          => $item[0],
				'name'         => $countries[ $item[0] ],
				'calling_code' => $item[1],
				'flag'         => $item[2],
			);
		}, $data );
	}

	/**
	 * Retrieves the given list of data by type. Helper method used to route the individual type requests
	 * through to the appropriate data list method.
	 *
	 * @param $type - string - The type of data to retrieve.
	 * @param $as_json - boolean - Whether to retrieve this data as as JSON string.
	 * @param $process_callback - callable - An optional callback for transforming the data before returning.
	 *
	 * @return string|array
	 */
	private static function get_data_by_type( $type, $as_json = false, $process_callback = null ) {
		switch ( $type ) {
			case 'country':
				$data = self::countries_list();
				break;
			case 'state':
				$data = self::states_list();
				break;
			case 'province':
				$data = self::provinces_list();
				break;
			case 'phone':
				$data = self::phone_list();
				break;
			default:
				$data = array();
				break;
		}

		if ( ! is_null( $process_callback ) ) {
			$data = call_user_func( $process_callback, $data );
		}

		return $as_json ? json_encode( $data ) : $data;
	}

	/**
	 * Provides an array of US States.
	 *
	 * @param $as_json - boolean - Whether to retrieve this data as a JSON string.
	 * @param $process_callback - callable - An optional callback for transforming the data before returning.
	 *
	 * @return string|array
	 */
	public static function states( $as_json = false, $process_callback = null ) {
		return self::get_data_by_type( 'state', $as_json, $process_callback );
	}

	/**
	 * Provides an array of Canadian Provinces.
	 *
	 * @param $as_json - boolean - Whether to retrieve this data as a JSON string.
	 * @param $process_callback - callable - An optional callback for transforming the data before returning.
	 *
	 * @return string|array
	 */
	public static function provinces( $as_json = false, $process_callback = null ) {
		return self::get_data_by_type( 'province', $as_json, $process_callback );
	}

	/**
	 * Provides an array of Countries.
	 *
	 * @param $as_json - boolean - Whether to retrieve this data as a JSON string.
	 * @param $process_callback - callable - An optional callback for transforming the data before returning.
	 *
	 * @return string|array
	 */
	public static function countries( $as_json = false, $process_callback = null ) {
		return self::get_data_by_type( 'country', $as_json, $process_callback );
	}

	/**
	 * Provides an array of phone format information.
	 *
	 * @param $as_json - boolean - Whether to retrieve this data as a JSON string.
	 * @param $process_callback - callable - An optional callback for transforming the data before returning.
	 *
	 * @return string|array
	 */
	public static function phone_info( $as_json = false, $process_callback = null ) {
		return self::get_data_by_type( 'phone', $as_json, $process_callback );
	}
}
