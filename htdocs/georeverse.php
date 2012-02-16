<?php
	/**
	 * look for a place and return an array that contains detailed geographic datas 
	 */
	function georeverse($place) {
		$ch = curl_init("https://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=" . urlencode($place));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$response = json_decode($response);
			
		$localization = array('valueString'=>$place);
			
		if ($response->status == 'OK') {
			
			$localization['geoPt'] = array();
			
			$localization['geoPt']['lon'] = $response->results[0]->geometry->location->lng;
			$localization['geoPt']['lat'] = $response->results[0]->geometry->location->lat;
			
			$street_number = null;
			$street_name = null;
			
			
			foreach ($response->results[0]->address_components as $ac) {
				
				if (in_array('street_number', $ac->types)) {
            $street_number = $ac->short_name;
				} 
				else if (in_array('route', $ac->types)) {
            $street_name = $ac->long_name;
				}
				else if (in_array('country', $ac->types)) {
            $localization['country'] = $ac->short_name;
				}
				else if (in_array('postal_code', $ac->types)) {
            $localization['zipCode'] = $ac->short_name;
				}
				else if (in_array('locality', $ac->types)) {
            $localization['city'] = $ac->long_name;
				}
				else if (in_array('administrative_area_level_1', $ac->types)) {
            $localization['adminLevel1'] = $ac->long_name;
				}
				else if (in_array('administrative_area_level_2', $ac->types)) {
            $localization['adminLevel2'] = $ac->long_name;
				}
			}
			
			$localization['valueString'] = $response->results[0]->formatted_address;
			
			// concat street_number and street_name into street
			$street = array();
			if (!is_null($street_number)) {
				$street[] = $street_number;
			}
			if (!is_null($street_name)) {
				$street[] = $street_name;
			}
			if (count($street)>0) {
				$localization['street'] = implode(', ', $street);
			}
		}
			
		return $localization;
			
	}
	
?>