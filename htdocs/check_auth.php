<?php
		
		
		
		// check if session is auth
		if (empty($_SESSION['auth']) || $_SESSION['auth'] !== TRUE) {
			header("Location: " . AUTH_URL . '?redirect_to=' . urlencode($_SERVER["REQUEST_URI"]) );
			return;
		} 
	
?>