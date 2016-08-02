<?
//Ensure SNMP extention is loaded and if not FAIL
if (!extension_loaded("snmp")) {
	echo "PHP SNMP Extension not loaded!";
	exit(2);
}
$SNMP_Host = getenv('UPTIME_HOSTNAME');
$SNMP_Port = getenv('UPTIME_SNMP-PORT');
$SNMP_Community = getenv('UPTIME_READ-COMMUNITY');
$SNMP_Connection_String = $SNMP_Host . ":" . $SNMP_Port;

//Check to see if we can connect and if it is an APC or not and IF not, fail.
$sysDescrQuery = snmpget("$SNMP_Connection_String", "$SNMP_Community", "sysDescr.0", 1000000); 

if ($sysDescrQuery === false) {
	echo "Cannot read sysDescr.0 data from host.";
	exit(2);
} elseif (strpos($sysDescrQuery, 'APC') !== false) {
	} else {
		echo $sysDescrQuery;
		echo "Device description indicicates it is not an APC branded device, exiting.";
		exit(2);
	}

// Everything must be good to this point so pull the data from the UPS!
$stateQuery = snmpget("$SNMP_Connection_String", "$SNMP_Community", ".1.3.6.1.4.1.318.1.1.1.4.1.1.0", 1000000); 
$state = str_replace(array('INTEGER: ', '"'),"", $stateQuery);

// if we are connected, pull and parse the details from the unit
if ($stateQuery) {
	
	$charge = 0;
	$chargeQuery = @snmpget("$SNMP_Connection_String", "$SNMP_Community", ".1.3.6.1.4.1.318.1.1.1.2.2.1.0", 1000000); 
	if ($chargeQuery) {
		$charge = str_replace(array('Gauge32: ', '"'),"",$chargeQuery);
	}
	
	$load = 0;
	$loadQuery = @snmpget("$SNMP_Connection_String", "$SNMP_Community", ".1.3.6.1.4.1.318.1.1.1.4.2.3.0", 1000000); 
	if ($loadQuery) {
		$load = str_replace(array('Gauge32: ', '"'),"",$loadQuery);
	}
	
	$runtime = 0;
	$runtimeQuery = @snmpget("$SNMP_Connection_String", "$SNMP_Community", ".1.3.6.1.4.1.318.1.1.1.2.2.3.0", 1000000); 
	if ($runtimeQuery) {
		//$runtime = str_replace(array('TimeTicks: ', '"'),"",$loadQuery);
		preg_match('#\((.*?)\)#', $runtimeQuery, $match);
		$runtime = $match[1]/6000;
	}

// outputs
	
	if ($state == 1) {
		$output = "State unknown"; 
		echo "state_int ".$state."\n";
		echo "state ".$output."\n";
		echo "charge ".$charge."\n";
		echo "load ".$load."\n";
		echo "runtime ".$runtime."\n";
		exit(1); 
	} 
	if ($state == 2) {
		$output = "Power connected";
		echo "state_int ".$state."\n";
		echo "state ".$output."\n";
		echo "charge ".$charge."\n";
		echo "load ".$load."\n";
		echo "runtime ".$runtime."\n";
		exit(0);
	} 
	if ($state == 3) {
		$output = "Power lost! Running on batteries. MINUTES REMAINING: $runtime. Load at $load%, $charge% batterry capacity left."; 
		echo "state_int ".$state."\n";
		echo "state ".$output."\n";
		echo "charge ".$charge."\n";
		echo "load ".$load."\n";
		echo "runtime ".$runtime."\n";		
		exit(1); 
	} 
	if ($state == 7) {
		$output = "UPS turned off";
		echo "state_int ".$state."\n";
		echo "state ".$output."\n";
		echo "charge ".$charge."\n";
		echo "load ".$load."\n";
		echo "runtime ".$runtime."\n";		
		exit(1);
	}
	if ($state == 8) {
		$output = "UPS is rebooting";
		echo "state_int ".$state."\n";
		echo "state ".$output."\n";
		echo "charge ".$charge."\n";
		echo "load ".$load."\n";
		echo "runtime ".$runtime."\n";		
		exit(1);
	}
	if ($state == 11) {
		$output = "UPS is sleeping until power returns";
		echo "state_int ".$state."\n";
		echo "state ".$output."\n";
		echo "charge ".$charge."\n";
		echo "load ".$load."\n";
		echo "runtime ".$runtime."\n";		
		exit(1);
	}
} else {
	echo "Cannot read data from host.";
	exit(2);
} 
?> 