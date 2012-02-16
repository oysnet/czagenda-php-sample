<?php
		
		session_start();
		
	  include_once "./init.php";	
		include_once "./check_auth.php";
		include_once "./georeverse.php";
		
		function date_convert($str) {
			$pdt = strptime($str, '%Y-%m-%dT%H:%M:%S');
			return strftime('%h %d, %Y at %H:%M:%S',mktime($pdt['tm_hour'], $pdt['tm_min'], $pdt['tm_sec'], $pdt['tm_mon'], $pdt['tm_mday'], 1900 + $pdt['tm_year']));
		}
		
		// contacts kinds
		$contact_types = array('information', 'registration', 'press');
		
		$success = FALSE;
		$error_message = null;
		$event = null;
		
		
		// if a form is sent
		if ($_POST) {
			
			// create event structure
			$data = array();			
			$data['event'] = array();
			
			// add links attribute to specify what kind of event we want to create
			$data['event']['links'] = array(array('href'=>'/schema/event', 'rel'=>'describedby'));
			
			// add required attributes
			$data['event']['title'] = $_POST['title'];
			$data['event']['category'] = $_POST['category'];
			$data['event']['eventStatus'] = $_POST['status'];
			$data['event']['when'] = array(array('startTime'=>$_POST['start_time']));
			
			// add other attributes
			
			// end time
			if (! empty($_POST['end_time'])) {
				$data['event']['when'][0]['endTime'] = $_POST['end_time'];
			}
			if (! empty($_POST['date_value_string'])) {
				$data['event']['when'][0]['valueString'] = $_POST['date_value_string'];
			}
						
			// full description
			$data['event']['content'] = $_POST['content'];
			
			// languages
			$data['event']['languages'] = array($_POST['language']);
			
			// logo URL
			if (! empty($_POST['logo'])) {
				$data['event']['logo'] = $_POST['logo'];
			}
			
			// website
			if (! empty($_POST['website'])) {
				$data['event']['website'] = $_POST['website'];
			}
			
			// tags
			if (! empty($_POST['tags'])) {
				$data['event']['tags'] = preg_split('/\s*,\s*/', $_POST['tags']);
			}
			
			// contacts 
			foreach($contact_types as $c_type) {
				
				// for each contact kind, if at least phone, fax, email, link or additional informations is fill 
				if (! (empty($_POST['phone_' . $c_type]) 
						&& empty($_POST['fax_' . $c_type]) 
						&& empty($_POST['email_' . $c_type])
						&& empty($_POST['link_' . $c_type])
						&& empty($_POST['additionalInformations_' . $c_type])) ) {
							
							$contact = array('rel'=>$c_type);
							if (!empty($_POST['phone_' . $c_type])) {
								$contact['phone'] = $_POST['phone_' . $c_type];
							}
							if (!empty($_POST['fax_' . $c_type])) {
								$contact['fax'] = $_POST['fax_' . $c_type];
							}
							if (!empty($_POST['email_' . $c_type])) {
								$contact['email'] = $_POST['email_' . $c_type];
							}
							if (!empty($_POST['link_' . $c_type])) {
								$contact['link'] = $_POST['link_' . $c_type];
							}
							if (!empty($_POST['additionalInformations_' . $c_type])) {
								$contact['additionalInformations'] = $_POST['additionalInformations_' . $c_type];
							}
							
							if (empty($data['event']['contacts'])) {
								$data['event']['contacts'] = array($contact);
							} else {
								$data['event']['contacts'][] = $contact;
							}
						}
			}
			
			// search for geographic information if localization is fill
			if (!empty($_POST['localization'])) {
				$localization = georeverse($_POST['localization']);
				$data['event']['where'] = array($localization);
			} 
			
			// if we already have 
			else if (count(array_intersect(array('localization_latitude', 'localization_longitude', 'localization_city', 'localization_country', 'localization_zipCode','localization_adminLevel1', 'localization_adminLevel2'), array_keys($_POST)))>0) {
					
					$localization = array();
					
					if (!empty($_POST['localization_latitude'])) {
						$localization['geoPt'] = array('lat' => floatval($_POST['localization_latitude']), 'lon' => floatval($_POST['localization_longitude']));
					}
					
					if (!empty($_POST['localization_city'])) {
						$localization['city'] = $_POST['localization_city'];
					}
					
					if (!empty($_POST['localization_country'])) {
						$localization['country'] = $_POST['localization_country'];
					}

					if (!empty($_POST['localization_zipCode'])) {
						$localization['zipCode'] = $_POST['localization_zipCode'];
					}
					
					if (!empty($_POST['localization_adminLevel1'])) {
						$localization['adminLevel1'] = $_POST['localization_adminLevel1'];
					}
					
					if (!empty($_POST['localization_adminLevel2'])) {
						$localization['adminLevel2'] = $_POST['localization_adminLevel2'];
					}
					
					$data['event']['where'] = array($localization);
					
			}
			
			try {
				
				$data = json_encode($data);
				if (empty($_POST['id'])) {
					// create event
					$request = new OAuthRequester(CZAGENDA_OAUTH_HOST . "/event/", 'POST', array(), $body=$data);
				} else {
					// update event
					$request = new OAuthRequester(CZAGENDA_OAUTH_HOST . $_POST['id'], 'PUT', array(), $body=$data);
				}
				$result = $request->doRequest(0, array(CURLOPT_HTTPHEADER=>array('Content-Type: application/json')));
			
				// success event creation code is 201
				if ($result['code'] == 201 || $result['code'] == 200) {
					$event = json_decode($result['body']);
					$event->createDate = date_convert($event->createDate);
					$event->updateDate = date_convert($event->updateDate);
					$success = TRUE;
				}
			} catch (Exception $e){
				$error_message = $e->getMessage();
				$event = json_decode($data);
				if (!empty($_POST['createDate'])) $event->createDate = $_POST['createDate'];
				if (!empty($_POST['updateDate'])) $event->updateDate = $_POST['updateDate'];
				if (!empty($_POST['id'])) $event->id = $_POST['id'];
			}
			
		} else {
			// gathering datas to display in form 
			if (!empty($_GET['id'])) {
				$request = new OAuthRequester(CZAGENDA_OAUTH_HOST . $_GET['id'], 'GET');
				$result = $request->doRequest(0);
				$event = json_decode($result['body']);
				
				
				$event->createDate = date_convert($event->createDate);
				$event->updateDate = date_convert($event->updateDate);
			}
		}
		
		// extract 10 firsts categories
		$request = new OAuthRequester(CZAGENDA_OAUTH_HOST . "/category/", 'GET', array());
		$result = $request->doRequest(0);
		if ($result['code'] == 200) {
			$categories = json_decode($result['body']);
		}
		
		
?>
<!doctype html> 
<html lang="en">
  
  <head>
    <meta charset="utf-8">
    
    <link type="text/css" href="css/czagenda-theme/jquery-ui-1.8.17.custom.css" rel="stylesheet" />
    <link type="text/css" href="css/base.css" rel="stylesheet" />
    <script type="text/javascript" src="js/openlayers/OpenLayers.js"></script>
    <script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
    
    <script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
    <script type="text/javascript" src="js/base.js"></script>
    <title>Event creation</title>
    <script type="text/javascript">
        
    </script>
  </head>
  <body>
  	<div id="wrapper">
  	<?php 
  		if ($_POST) {
  			
				if ($success === TRUE) {
          ?>
            <div class="ui-widget">
            <div class="ui-state-highlight ui-corner-all"> 
            <p><span class="ui-icon ui-icon-info"></span>
          <?					
					if (empty($_POST['id'])) {
						echo "Event was successfully created";
					} else {
						echo "Event was successfully updated";
					}
          ?>
            </p>
            </div>
            </div>
          <?					
				} else {
				  ?>
            <div class="ui-widget">
            <div class="ui-state-error ui-corner-all"> 
            <p><span class="ui-icon ui-icon-alert"></span>
          <?      
					echo $error_message;
          ?>
            </p>
            </div>
            </div>
          <?  
				}
			}
  	?>
  	
  	<form action="" method="POST">
  		<div id="maintags">
    		<fieldset class="tab">
    			<legend>Informations</legend>
    			<p>
  	        <label for="title">Title</label> 
  	        <input type="text" id="title" name="title" value="<?php if (!(is_null($event) || empty($event->event->title))) echo $event->event->title;?>" required="required">
  	      </p>
  	      <p>
  	        <label for="content" class='textarea'>Description</label> 
  	        <textarea  id="content" name="content" ><?php if (!(is_null($event) || empty($event->event->content))) echo $event->event->content;?></textarea>
  	      </p>
  	      <p>
            <label for="start_time">Start date</label> 
            <input type="text" id="start_time" name="start_time" value="<?php if (!(is_null($event) || empty($event->event->when[0]->startTime))) echo $event->event->when[0]->startTime;?>"  required="required">
          </p>
          
          <p>
            <label for="end_time">End date</label> 
            <input type="text" id="end_time" name="end_time" value="<?php if (!(is_null($event) || empty($event->event->when[0]->endTime))) echo $event->event->when[0]->endTime;?>">
          </p>
          
          <p>
            <label for="date_value_string">Date as string</label> 
            <input type="text" id="date_value_string" name="date_value_string" value="<?php if (!(is_null($event) || empty($event->event->when[0]->valueString))) echo $event->event->when[0]->valueString;?>">
          </p>
        </fieldset>
        <fieldset class="tab <?php if (!(is_null($event) || empty($event->event->where[0]->geoPt))) echo "map"?>">
          <legend>Location</legend>
  	      <p>
  	        <label for="localization">Localization</label> 
  	        <input type="text" id="localization" name="localization" value="<?php if (!(is_null($event) || empty($event->event->where[0]->valueString)) && empty($event->event->where[0]->geoPt)) echo $event->event->where[0]->valueString;?>" >
  	      </p>
  	      	<?php 
  	      		if (!(is_null($event) || empty($event->event->where[0]))) {
  	      			
  							foreach( $event->event->where[0] as $key=>$val) {
  								
  								if ($key == 'valueString' && empty($event->event->where[0]->geoPt)) continue;
  								
  								if ($key == 'geoPt') {
  									echo "<p><label for='localization_latitude'>Latitude</label>";
  									echo "<input type='text' readonly='readonly' id='localization_latitude' name='localization_latitude' value='".$val->lat."'></p>";
  									echo "<p><label for='localization_longitude'>Longitude</label>";
  									echo "<input type='text' readonly='readonly' id='localization_longitude' name='localization_longitude' value='".$val->lon."'></p>";
                    echo "<div id='map'></div>";
  								} 
  								
  								else {
  								
  									echo "<p><label for='localization_$key'>$key</label>";
  									echo "<input type='text' readonly='readonly' id='localization_$key' name='localization_$key' value='$val'></p>";
  								}
  							}
  							
  	      		}
  	      	?>
  	      
    		</fieldset>
    		
    		
    		<fieldset class="tab">
    		  <legend>Image and website</legend>
    		<p>
          <label for="logo">Logo URL</label> 
          <input type="text" id="logo" name="logo" value="<?php if (!(is_null($event) || empty($event->event->logo))) echo $event->event->logo;?>">
        </p>
    		
    		<p>
          <label for="website">Website URL</label> 
          <input type="text" id="website" name="website" value="<?php if (!(is_null($event) || empty($event->event->website))) echo $event->event->website;?>">
        </p>
    		</fieldset>
    		
    		<fieldset class="tab">
    		<legend>Meta data</legend>
    		
    		<p>
          <label for="category">Category</label> 
          <select id="category" name="category" required="required">
          <?php
          	foreach($categories->rows as $category) {
          		
  						$selected = '';
  						if (!is_null($event) && $event->event->category == $category->id) {
  							$selected = "selected";
  						}
  						
          		echo "<option value='$category->id' ". $selected ." >$category->title</option>";
  				 	} ?>
          </select>
        </p>
    		
    		<p>
          <label for="tags">Tags</label> 
          <input type="text" id="tags" name="tags" value="<?php if (!(is_null($event) || empty($event->event->tags))) echo implode(', ', $event->event->tags);?>">
        </p>
    		
    		<p>
          <label for="language">Language</label> 
          <select id="language" name="language">
          	<option value="fre" <?php if (!(is_null($event) || empty($event->event->languages)) && $event->event->languages[0]=="fre") echo "selected";?>>French</option>
          	<option value="eng" <?php if (!(is_null($event) || empty($event->event->languages)) && $event->event->languages[0]=="eng") echo "selected";?>>English</option>        	
          </select>
        </p>
        
        <p>
          <label for="status">Status</label> 
          <select id="status" name="status" required="required">
          	<option value="confirmed" <?php if (!is_null($event) && $event->event->eventStatus == "confirmed") echo "selected" ?>>Confirmed</option>
          	<option value="canceled" <?php if (!is_null($event) && $event->event->eventStatus == "canceled") echo "selected" ?>>Canceled</option>
          	<option value="tentative" <?php if (!is_null($event) && $event->event->eventStatus == "tentative") echo "selected" ?>>Tentative</option>
          </select>
        </p>
        <?php if (!(is_null($event) || empty($event->id))) {?>
          <p>
            <label for="id">Id</label> 
            <input type="text" id="id" readonly='readonly' name="id" value="<?php echo $event->id?>" >
          </p>
          
          <p>
            <label for="createDate">Create date</label> 
            <input type="text" id="createDate" readonly='readonly' name="createDate" value="<?php echo $event->createDate?>" >
          </p>
          
          <p>
            <label for="updateDate">Update date</label> 
            <input type="text" id="updateDate" readonly='readonly' name="updateDate" value="<?php echo $event->updateDate?>" >
          </p>
          
          <?php } ?>
    		</fieldset>
    		<div class="tab">
    		  <legend>Contacts</legend>
    		<div id="contacts" >
    		  
    		<?php 
    			foreach($contact_types as $c_type) {
    					$found = FALSE;
    					$contact = null;
    					if (!is_null($event) && !empty($event->event->contacts)) {
  							foreach($event->event->contacts as $contact) {
  									if ($contact->rel == $c_type)	{
  										$found = TRUE;
  										break;
  									}			
  							}					
  							
  							if (!$found) {
  								$contact = null;
  							}		
    					}
    		?>
  				<fieldset>
  					<legend><?php echo $c_type ?></legend>
  					
  					<p>
  		        <label for="phone_<?php echo $c_type ?>">Phone</label> 
  		        <input type="text" id="phone_<?php echo $c_type ?>" name="phone_<?php echo $c_type ?>" value="<?php if (!(is_null($contact) || empty($contact->phone))) echo $contact->phone;?>">
  		      </p>
  					
  					<p>
  		        <label for="fax_<?php echo $c_type ?>">Fax</label> 
  		        <input type="text" id="fax_<?php echo $c_type ?>" name="fax_<?php echo $c_type ?>" value="<?php if (!(is_null($contact) || empty($contact->fax))) echo $contact->fax;?>">
  		      </p>
  					
  					<p>
  		        <label for="email_<?php echo $c_type ?>">Email</label> 
  		        <input type="text" id="email_<?php echo $c_type ?>" name="email_<?php echo $c_type ?>" value="<?php if (!(is_null($contact) || empty($contact->email))) echo $contact->email;?>">
  		      </p>
  					
  					<p>
  		        <label for="link_<?php echo $c_type ?>">Website</label> 
  		        <input type="text" id="link_<?php echo $c_type ?>" name="link_<?php echo $c_type ?>" value="<?php if (!(is_null($contact) || empty($contact->link))) echo $contact->link;?>">
  		      </p>
  					
  					<p>
  		        <label for="additionalInformations_<?php echo $c_type ?>">More informations</label> 
  		        <input type="text" id="additionalInformations_<?php echo $c_type ?>" name="additionalInformations_<?php echo $c_type ?>" value="<?php if (!(is_null($contact) || empty($contact->additionalInformations))) echo $contact->additionalInformations;?>">
  		      </p>
  					
  				</fieldset>					
    		<?php	} ?>
    		</div></div>
      </div>
  		
  		<p>
  			<input type="submit" value="submit">
  		</p>
  		
  	</form>
  	</div>
  </body>
</html>