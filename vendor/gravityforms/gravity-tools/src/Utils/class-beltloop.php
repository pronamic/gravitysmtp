<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

class Beltloop {

	public static function sort( $data, $id_key = 'id', $sort_key = 'prevId' ) {
		$indexed = array();

		foreach ( $data as $datum ) {
			$indexed[] = array(
				'sort_val' => $datum[ $sort_key ],
				'data' => $datum,
			);
		}

		$sorted = array();

		$current_index = 0;

		while ( count( $indexed ) ) {
			$item_by_idx = self::get_matching_item_by_sort_key( $current_index, $indexed, $id_key );
			$current  = $item_by_idx['data'];
			$sorted[] = $current;

			unset( $indexed[ $item_by_idx['idx'] ] );
			$current_index = $current[ $id_key ];
		}

		return $sorted;
	}

	private static function get_matching_item_by_sort_key( $index, $items, $id_key = 'id' ) {
		$checked = array_filter( $items, function( $item ) use ( $index ) {
			return (int) $item['sort_val'] === (int) $index;
		} );

		if ( ! empty( $checked ) ) {
			return array(
				'data' => $checked[ array_key_first( $checked ) ]['data'],
				'idx'  => array_key_first( $checked ),
			);
		}

		if ( (int) $index !== 0 ) {
			$heads = array_filter( $items, function( $item ) {
				$v = $item['sort_val'];
				return $v === null || $v === '' || (int) $v === 0;
			} );
			if ( ! empty( $heads ) ) {
				$first = $heads[ array_key_first( $heads ) ];
				return array(
					'data' => $first['data'],
					'idx'  => array_key_first( $heads ),
				);
			}
		}

		$min_id   = null;
		$min_idx  = null;
		$min_data = null;
		foreach ( $items as $idx => $item ) {
			$id = isset( $item['data'][ $id_key ] ) ? (int) $item['data'][ $id_key ] : 0;
			if ( $min_id === null || $id < $min_id ) {
				$min_id   = $id;
				$min_idx  = $idx;
				$min_data = $item['data'];
			}
		}

		return array(
			'data' => $min_data,
			'idx'  => $min_idx,
		);
	}

	public static function partial_sort( $full_list, $partial_list, $id_key = 'id' ) {
		$indexed = array();

		foreach ( $full_list as $item ) {
			$indexed[ $item[ $id_key ] ] = $item;
		}

		$sorted = array();

		foreach ( $partial_list as $part_item ) {
			$search         = $part_item[ $id_key ];
			$pos            = (int) array_search( $search, array_keys( $indexed ) );
			$sorted[ $pos ] = $part_item;
		}

		ksort( $sorted );

		return array_values( $sorted );
	}
}
