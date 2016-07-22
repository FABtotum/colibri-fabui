<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 
if(!function_exists('getWlanInfo'))
{
	/**
	 * @param string interface name
	 * @return array infos about wlan interface
	 */
	function getWlanInfo($interface = 'wlan0')
	{
		exec('ifconfig '.$interface, $info);
		exec('iwconfig '.$interface, $info);
		
		$strInterface = implode(" ",$info);
		$strInterface = preg_replace('/\s\s+/', ' ', $strInterface);
		
		return array(
			'mac_address'         => getFromRegEx('/HWaddr ([0-9a-f:]+)/i', $strInterface),
			'ip_address'          => getFromRegEx('/inet addr:([0-9.]+)/i', $strInterface),
			'subnet_mask'         => getFromRegEx('/Mask:([0-9.]+)/i', $strInterface),
			'received_packets'    => getFromRegEx('/RX packets:(\d+)/', $strInterface),
			'transferred_packets' => getFromRegEx('/TX packets:(\d+)/', $strInterface),
			'received_bytes'      => getFromRegEx('/RX Bytes:(\d+ \(\d+.\d+ MiB\))/i', $strInterface),
			'transferred_bytes'   => getFromRegEx('/TX Bytes:(\d+ \(\d+.\d+ [K|M|G]iB\))/i', $strInterface),
			'ssid'                => getFromRegEx('/ESSID:\"((?:(?![\n\s]).)*)\"/i', $strInterface),
			'ap_mac_address'      => getFromRegEx('/Access Point: ([0-9a-f:]+)/i', $strInterface),
			'bitrate'             => getFromRegEx('/Bit Rate:([0-9]+.[0-9]+\s[a-z]+\/[a-z]+)/i', $strInterface),
			'link_quality'        => getFromRegEx('/Link Quality=([0-9]+\/[0-9]+)/i', $strInterface),
			'signal_level'        => getFromRegEx('/Signal Level=([0-9]+\/[0-9]+)/i', $strInterface),
			'power_management'    => getFromRegEx('/Power Management:([a-zA-Z]+)/i', $strInterface),
			'frequency'           => getFromRegEx('/Frequency:([0-9]+.[0-9]+\s[a-z]+)/i', $strInterface),
			'ieee'                => getFromRegEx('/IEEE ([0-9]+.[0-9]+[a-z]+)/i', $strInterface)
		);
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('scanWlan'))
{
	/**
	 * @param string $interface wlan interface name
	 * @return array list of discovered wifi's nets 
	 */
	function scanWlan($interface = 'wlan0')
	{
		$scanResult = shell_exec('iwlist '.$interface.' scan');
		$scanResult = preg_replace('/\s\s+/', ' ', $scanResult);
		$scanResult = str_replace($interface.' Scan completed :', '', $scanResult);
		$scanResult = explode('Cell ', $scanResult);
		$nets = array();
		foreach($scanResult as $net){
			if(trim($net) != ''){
				$temp = array();
				$temp['address']   		= getFromRegEx('/Address:\s([0-9a-f:]+)/i', $net);
				$temp['essid']     		= getFromRegEx('/ESSID:\"((?:.)*)\"/i', $net);	
				$temp['protocol']  		= getFromRegEx('/IEEE\s([0-9]+.[0-9]+[a-z]+)/i', $net);
				$temp['mode']      		= getFromRegEx('/Mode:([a-zA-Z]+)/i', $net);
				$temp['frequency'] 		= getFromRegEx('/Frequency:([0-9]+.[0-9]+\s[a-z]+)/i', $net);
				$temp['channel']  		= getFromRegEx('/Channel ([0-9]+)/i', $net);
				$temp['encryption_key'] = getFromRegEx('/Encryption key:([a-zA-Z]+)/i', $net);	
				$temp['bit_rates']      = getFromRegEx('/Bit Rates:([0-9]+.[0-9]+\s[a-z]+\/[a-z]+)/i', $net);
				$temp['quality']        = getFromRegEx('/Quality=([0-9]+)/i', $net); 
				$temp['signal_level']   = getFromRegEx('/Signal level=([0-9]+)/i', $net);
				//add to nets lists
				$nets[] = $temp;
			}
		}
		return $nets;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('getFromRegEx'))
{
	/**
	 * 
	 */
	function getFromRegEx($regEx, $string)
	{
		preg_match($regEx, $string, $tempResult);
		return isset($tempResult[1]) ? $tempResult[1] : '';
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('wifiConnect'))
{
	/**
	 * connecto to a wifi network
	 */
	function wifiConnect($essid, $password)
	{
		$CI =& get_instance();
		$CI->config->load('fabtotum');
		$setWifiCommand = 'sudo sh '.$CI->config->item('ext_path').'bash/set_wifi.sh "'.$essid.'" "'.$password.'"';
		log_message('debug', $setWifiCommand);
		$scriptResult = shell_exec($setWifiCommand);
		return true;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('wifiDisconnect'))
{
	/**
	 * disconnecto from wifi network
	 */
	function wifiDisconnect()
	{
		$CI =& get_instance();
		$CI->config->load('fabtotum');
		$setWifiCommand = 'sudo sh '.$CI->config->item('ext_path').'bash/set_wifi.sh';
		log_message('debug', $setWifiCommand);
		$scriptResult = shell_exec($setWifiCommand);
		return true;
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('setSystemDate'))
{
	/**
	 * set system date format = YYYY-MM-DD HH:mm:ss
	 */
	function setSystemDate($date)
	{
		log_message('debug', 'sudo date -s "'.$date.'"');
		shell_exec('sudo date -s "'.$date.'"');
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(!function_exists('isInternetAvaialable'))
{
	/**
	 * check if internet connection is avaialable
	 */
	function isInternetAvaialable()
	{
		return !$sock = @fsockopen('http://www.google.com', 80, $num, $error, 2) ? false : true;
	}
}
?>