<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

use InvalidArgumentException;

class Moola {

	protected $amount;

	protected $currency_code;

	protected $currency_data = array(
		'ALL' => array(
			'decimals' => 2,
			'name'     => 'Albania Lek',
			'symbol'   => 'Lek',
		),
		'AFN' => array(
			'decimals' => 2,
			'name'     => 'Afghanistan Afghani',
			'symbol'   => '؋',
		),
		'ARS' => array(
			'decimals' => 2,
			'name'     => 'Argentina Peso',
			'symbol'   => '$',
		),
		'AWG' => array(
			'decimals' => 2,
			'name'     => 'Aruba Guilder',
			'symbol'   => 'ƒ',
		),
		'AUD' => array(
			'decimals' => 2,
			'name'     => 'Australia Dollar',
			'symbol'   => '$',
		),
		'AZN' => array(
			'decimals' => 2,
			'name'     => 'Azerbaijan Manat',
			'symbol'   => '₼',
		),
		'BSD' => array(
			'decimals' => 2,
			'name'     => 'Bahamas Dollar',
			'symbol'   => '$',
		),
		'BBD' => array(
			'decimals' => 2,
			'name'     => 'Barbados Dollar',
			'symbol'   => '$',
		),
		'BYN' => array(
			'decimals' => 2,
			'name'     => 'Belarus Ruble',
			'symbol'   => 'Br',
		),
		'BZD' => array(
			'decimals' => 2,
			'name'     => 'Belize Dollar',
			'symbol'   => 'BZ$',
		),
		'BMD' => array(
			'decimals' => 2,
			'name'     => 'Bermuda Dollar',
			'symbol'   => '$',
		),
		'BOB' => array(
			'decimals' => 2,
			'name'     => 'Bolivia Bolíviano',
			'symbol'   => '$b',
		),
		'BAM' => array(
			'decimals' => 2,
			'name'     => 'Bosnia and Herzegovina Convertible Mark',
			'symbol'   => 'KM',
		),
		'BWP' => array(
			'decimals' => 2,
			'name'     => 'Botswana Pula',
			'symbol'   => 'P',
		),
		'BGN' => array(
			'decimals' => 2,
			'name'     => 'Bulgaria Lev',
			'symbol'   => 'лв',
		),
		'BRL' => array(
			'decimals' => 2,
			'name'     => 'Brazil Real',
			'symbol'   => 'R$',
		),
		'BND' => array(
			'decimals' => 2,
			'name'     => 'Brunei Darussalam Dollar',
			'symbol'   => '$',
		),
		'KHR' => array(
			'decimals' => 2,
			'name'     => 'Cambodia Riel',
			'symbol'   => '៛',
		),
		'CAD' => array(
			'decimals' => 2,
			'name'     => 'Canada Dollar',
			'symbol'   => '$',
		),
		'KYD' => array(
			'decimals' => 2,
			'name'     => 'Cayman Islands Dollar',
			'symbol'   => '$',
		),
		'CLP' => array(
			'decimals' => 2,
			'name'     => 'Chile Peso',
			'symbol'   => '$',
		),
		'CNY' => array(
			'decimals' => 2,
			'name'     => 'China Yuan Renminbi',
			'symbol'   => '¥',
		),
		'COP' => array(
			'decimals' => 2,
			'name'     => 'Colombia Peso',
			'symbol'   => '$',
		),
		'CRC' => array(
			'decimals' => 2,
			'name'     => 'Costa Rica Colon',
			'symbol'   => '₡',
		),
		'HRK' => array(
			'decimals' => 2,
			'name'     => 'Croatia Kuna',
			'symbol'   => 'kn',
		),
		'CUP' => array(
			'decimals' => 2,
			'name'     => 'Cuba Peso',
			'symbol'   => '₱',
		),
		'CZK' => array(
			'decimals' => 2,
			'name'     => 'Czech Republic Koruna',
			'symbol'   => 'Kč',
		),
		'DKK' => array(
			'decimals' => 2,
			'name'     => 'Denmark Krone',
			'symbol'   => 'kr',
		),
		'DOP' => array(
			'decimals' => 2,
			'name'     => 'Dominican Republic Peso',
			'symbol'   => 'RD$',
		),
		'XCD' => array(
			'decimals' => 2,
			'name'     => 'East Caribbean Dollar',
			'symbol'   => '$',
		),
		'EGP' => array(
			'decimals' => 2,
			'name'     => 'Egypt Pound',
			'symbol'   => '£',
		),
		'SVC' => array(
			'decimals' => 2,
			'name'     => 'El Salvador Colon',
			'symbol'   => '$',
		),
		'EUR' => array(
			'decimals' => 2,
			'name'     => 'Euro Member Countries',
			'symbol'   => '€',
		),
		'FKP' => array(
			'decimals' => 2,
			'name'     => 'Falkland Islands (Malvinas) Pound',
			'symbol'   => '£',
		),
		'FJD' => array(
			'decimals' => 2,
			'name'     => 'Fiji Dollar',
			'symbol'   => '$',
		),
		'GHS' => array(
			'decimals' => 2,
			'name'     => 'Ghana Cedi',
			'symbol'   => '¢',
		),
		'GIP' => array(
			'decimals' => 2,
			'name'     => 'Gibraltar Pound',
			'symbol'   => '£',
		),
		'GTQ' => array(
			'decimals' => 2,
			'name'     => 'Guatemala Quetzal',
			'symbol'   => 'Q',
		),
		'GGP' => array(
			'decimals' => 2,
			'name'     => 'Guernsey Pound',
			'symbol'   => '£',
		),
		'GYD' => array(
			'decimals' => 2,
			'name'     => 'Guyana Dollar',
			'symbol'   => '$',
		),
		'HNL' => array(
			'decimals' => 2,
			'name'     => 'Honduras Lempira',
			'symbol'   => 'L',
		),
		'HKD' => array(
			'decimals' => 2,
			'name'     => 'Hong Kong Dollar',
			'symbol'   => '$',
		),
		'HUF' => array(
			'decimals' => 2,
			'name'     => 'Hungary Forint',
			'symbol'   => 'Ft',
		),
		'ISK' => array(
			'decimals' => 2,
			'name'     => 'Iceland Krona',
			'symbol'   => 'kr',
		),
		'INR' => array(
			'decimals' => 2,
			'name'     => 'India Rupee',
			'symbol'   => '',
		),
		'IDR' => array(
			'decimals' => 0,
			'name'     => 'Indonesia Rupiah',
			'symbol'   => 'Rp',
		),
		'IRR' => array(
			'decimals' => 2,
			'name'     => 'Iran Rial',
			'symbol'   => '﷼',
		),
		'IMP' => array(
			'decimals' => 2,
			'name'     => 'Isle of Man Pound',
			'symbol'   => '£',
		),
		'ILS' => array(
			'decimals' => 2,
			'name'     => 'Israel Shekel',
			'symbol'   => '₪',
		),
		'JMD' => array(
			'decimals' => 2,
			'name'     => 'Jamaica Dollar',
			'symbol'   => 'J$',
		),
		'JPY' => array(
			'decimals' => 0,
			'name'     => 'Japan Yen',
			'symbol'   => '¥',
		),
		'JEP' => array(
			'decimals' => 2,
			'name'     => 'Jersey Pound',
			'symbol'   => '£',
		),
		'KZT' => array(
			'decimals' => 2,
			'name'     => 'Kazakhstan Tenge',
			'symbol'   => 'лв',
		),
		'KPW' => array(
			'decimals' => 2,
			'name'     => 'Korea (North) Won',
			'symbol'   => '₩',
		),
		'KRW' => array(
			'decimals' => 0,
			'name'     => 'Korea (South) Won',
			'symbol'   => '₩',
		),
		'KGS' => array(
			'decimals' => 2,
			'name'     => 'Kyrgyzstan Som',
			'symbol'   => 'лв',
		),
		'LAK' => array(
			'decimals' => 2,
			'name'     => 'Laos Kip',
			'symbol'   => '₭',
		),
		'LBP' => array(
			'decimals' => 2,
			'name'     => 'Lebanon Pound',
			'symbol'   => '£',
		),
		'LRD' => array(
			'decimals' => 2,
			'name'     => 'Liberia Dollar',
			'symbol'   => '$',
		),
		'MKD' => array(
			'decimals' => 2,
			'name'     => 'Macedonia Denar',
			'symbol'   => 'ден',
		),
		'MYR' => array(
			'decimals' => 2,
			'name'     => 'Malaysia Ringgit',
			'symbol'   => 'RM',
		),
		'MUR' => array(
			'decimals' => 2,
			'name'     => 'Mauritius Rupee',
			'symbol'   => '₨',
		),
		'MXN' => array(
			'decimals' => 2,
			'name'     => 'Mexico Peso',
			'symbol'   => '$',
		),
		'MNT' => array(
			'decimals' => 2,
			'name'     => 'Mongolia Tughrik',
			'symbol'   => '₮',
		),
		'MZN' => array(
			'decimals' => 2,
			'name'     => 'Mozambique Metical',
			'symbol'   => 'MT',
		),
		'NAD' => array(
			'decimals' => 2,
			'name'     => 'Namibia Dollar',
			'symbol'   => '$',
		),
		'NPR' => array(
			'decimals' => 2,
			'name'     => 'Nepal Rupee',
			'symbol'   => '₨',
		),
		'ANG' => array(
			'decimals' => 2,
			'name'     => 'Netherlands Antilles Guilder',
			'symbol'   => 'ƒ',
		),
		'NZD' => array(
			'decimals' => 2,
			'name'     => 'New Zealand Dollar',
			'symbol'   => '$',
		),
		'NIO' => array(
			'decimals' => 2,
			'name'     => 'Nicaragua Cordoba',
			'symbol'   => 'C$',
		),
		'NGN' => array(
			'decimals' => 2,
			'name'     => 'Nigeria Naira',
			'symbol'   => '₦',
		),
		'NOK' => array(
			'decimals' => 2,
			'name'     => 'Norway Krone',
			'symbol'   => 'kr',
		),
		'OMR' => array(
			'decimals' => 3,
			'name'     => 'Oman Rial',
			'symbol'   => '﷼',
		),
		'PKR' => array(
			'decimals' => 2,
			'name'     => 'Pakistan Rupee',
			'symbol'   => '₨',
		),
		'PAB' => array(
			'decimals' => 2,
			'name'     => 'Panama Balboa',
			'symbol'   => 'B/.',
		),
		'PYG' => array(
			'decimals' => 0,
			'name'     => 'Paraguay Guarani',
			'symbol'   => 'Gs',
		),
		'PEN' => array(
			'decimals' => 2,
			'name'     => 'Peru Sol',
			'symbol'   => 'S/.',
		),
		'PHP' => array(
			'decimals' => 2,
			'name'     => 'Philippines Peso',
			'symbol'   => '₱',
		),
		'PLN' => array(
			'decimals' => 2,
			'name'     => 'Poland Zloty',
			'symbol'   => 'zł',
		),
		'QAR' => array(
			'decimals' => 2,
			'name'     => 'Qatar Riyal',
			'symbol'   => '﷼',
		),
		'RON' => array(
			'decimals' => 2,
			'name'     => 'Romania Leu',
			'symbol'   => 'lei',
		),
		'RUB' => array(
			'decimals' => 2,
			'name'     => 'Russia Ruble',
			'symbol'   => '₽',
		),
		'SHP' => array(
			'decimals' => 2,
			'name'     => 'Saint Helena Pound',
			'symbol'   => '£',
		),
		'SAR' => array(
			'decimals' => 2,
			'name'     => 'Saudi Arabia Riyal',
			'symbol'   => '﷼',
		),
		'RSD' => array(
			'decimals' => 2,
			'name'     => 'Serbia Dinar',
			'symbol'   => 'Дин.',
		),
		'SCR' => array(
			'decimals' => 2,
			'name'     => 'Seychelles Rupee',
			'symbol'   => '₨',
		),
		'SGD' => array(
			'decimals' => 2,
			'name'     => 'Singapore Dollar',
			'symbol'   => '$',
		),
		'SBD' => array(
			'decimals' => 2,
			'name'     => 'Solomon Islands Dollar',
			'symbol'   => '$',
		),
		'SOS' => array(
			'decimals' => 2,
			'name'     => 'Somalia Shilling',
			'symbol'   => 'S',
		),
		'ZAR' => array(
			'decimals' => 2,
			'name'     => 'South Africa Rand',
			'symbol'   => 'R',
		),
		'LKR' => array(
			'decimals' => 2,
			'name'     => 'Sri Lanka Rupee',
			'symbol'   => '₨',
		),
		'SEK' => array(
			'decimals' => 2,
			'name'     => 'Sweden Krona',
			'symbol'   => 'kr',
		),
		'CHF' => array(
			'decimals' => 2,
			'name'     => 'Switzerland Franc',
			'symbol'   => 'CHF',
		),
		'SRD' => array(
			'decimals' => 2,
			'name'     => 'Suriname Dollar',
			'symbol'   => '$',
		),
		'SYP' => array(
			'decimals' => 2,
			'name'     => 'Syria Pound',
			'symbol'   => '£',
		),
		'TWD' => array(
			'decimals' => 2,
			'name'     => 'Taiwan New Dollar',
			'symbol'   => 'NT$',
		),
		'THB' => array(
			'decimals' => 2,
			'name'     => 'Thailand Baht',
			'symbol'   => '฿',
		),
		'TTD' => array(
			'decimals' => 2,
			'name'     => 'Trinidad and Tobago Dollar',
			'symbol'   => 'TT$',
		),
		'TRY' => array(
			'decimals' => 2,
			'name'     => 'Turkey Lira',
			'symbol'   => '',
		),
		'TVD' => array(
			'decimals' => 2,
			'name'     => 'Tuvalu Dollar',
			'symbol'   => '$',
		),
		'UAH' => array(
			'decimals' => 2,
			'name'     => 'Ukraine Hryvnia',
			'symbol'   => '₴',
		),
		'GBP' => array(
			'decimals' => 2,
			'name'     => 'United Kingdom Pound',
			'symbol'   => '£',
		),
		'USD' => array(
			'decimals' => 2,
			'name'     => 'United States Dollar',
			'symbol'   => '$',
		),
		'UYU' => array(
			'decimals' => 2,
			'name'     => 'Uruguay Peso',
			'symbol'   => '$U',
		),
		'UZS' => array(
			'decimals' => 2,
			'name'     => 'Uzbekistan Som',
			'symbol'   => 'лв',
		),
		'VEF' => array(
			'decimals' => 2,
			'name'     => 'Venezuela Bolívar',
			'symbol'   => 'Bs',
		),
		'VND' => array(
			'decimals' => 0,
			'name'     => 'Viet Nam Dong',
			'symbol'   => '₫',
		),
		'YER' => array(
			'decimals' => 2,
			'name'     => 'Yemen Rial',
			'symbol'   => '﷼',
		),
		'ZWD' => array(
			'decimals' => 2,
			'name'     => 'Zimbabwe Dollar',
			'symbol'   => 'Z$',
		),
	);

	public function __construct( $amount, $currency_code, $is_raw = true ) {
		if ( ! $is_raw ) {
			$amount = $this->convert_display_amount_to_raw( $amount, $currency_code );
		}
		$this->amount        = $amount;
		$this->currency_code = $currency_code;

		$this->get_currency_data( $currency_code );
	}

	public function raw_value() {
		return $this->amount;
	}

	public function display_value( $precision = 0, $show_currency = false, $commas = false ) {
		$currency_data = $this->get_currency_data( $this->currency_code );
		$decimals      = (int) $currency_data['decimals'];

		$divider = 1;

		for ( $i = 0; $i < $decimals; $i++ ) {
			$divider *= 10;
		}

		$float_val = (float) ( $this->amount / $divider );

		if ( ! $show_currency ) {
			return round( $float_val, $precision );
		}

		$rounded       = round( $float_val, $precision );
		$formatted_val = $commas ? number_format( $rounded ) : $rounded;

		return sprintf( '%s%s', $currency_data['symbol'], $formatted_val );
	}

	public function change_currency( $new_currency_code ) {
		$new_data     = $this->get_currency_data( $new_currency_code );
		$current_data = $this->get_currency_data( $this->currency_code );

		$this->currency_code = $new_currency_code;

		if ( $new_data['decimals'] === $current_data['decimals'] ) {
			return;
		}

		$modifier = 1;

		$start = min( $new_data['decimals'], $current_data['decimals'] );
		$end   = max( $new_data['decimals'], $current_data['decimals'] );

		for ( $i = $start; $i < $end; $i++ ) {
			$modifier *= 10;
		}

		if ( $new_data['decimals'] > $current_data['decimals'] ) {
			$this->amount = $this->amount * $modifier;
		}

		if ( $new_data['decimals'] < $current_data['decimals'] ) {
			$this->amount = $this->amount / $modifier;
		}
	}

	public function convert_display_amount_to_raw( $display_value, $currency_code ) {
		$currency_data = $this->get_currency_data( $currency_code );
		$sanitized = $this->sanitize_display_value( $display_value );

		$modifier = 1;

		for( $i = 0; $i < $currency_data['decimals']; $i++ ) {
			$modifier *= 10;
		}

		return $sanitized * $modifier;
	}

	private function sanitize_display_value( $display_value ) {
		$stripped = preg_replace( "/[^0-9.]/", "", $display_value );

		if ( $stripped === '' ) {
			$stripped = 0;
		}

		if ( ! is_numeric( $stripped ) ) {
			throw new InvalidArgumentException( 'Invalid display value provided for sanitization.' );
		}

		return floatval( $stripped );
	}

	private function get_currency_data( $currency_code ) {
		if ( ! array_key_exists( $currency_code, $this->currency_data ) ) {
			throw new InvalidArgumentException( 'Invalid currency code provided.' );
		}

		return $this->currency_data[ $currency_code ];
	}
}
