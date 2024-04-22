<?php

namespace Gravity_Forms\Gravity_SMTP\Models\Hydrators;

class Hydrator_Postmark implements Hydrator {

	public function hydrate( $row ) {
		$row['clicked'] = 'N/A';
		$row['opened']  = 'N/A';

		return $row;
	}

}
