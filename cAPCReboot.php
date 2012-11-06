<?php
	/*
	 * this class reboots remote devices using APC remote reboot bars
	 */
	
	class apcReboot{
		private $ip;
		private $un;
		private $pw;
		private $prefix;
		private $ch = false;
		private $cr = false;
		
		// public variables
		public $rebootOptions = array(1 => "No Action", 2 => "On Immediate", 3 => "On Delayed", 4 => "Off Immediate", 5 => "Off Delayed", 6 => "Reboot Immediate", 7 => "Reboot Delayed", 8 => "Cancel Pending Commands");
		
		public function __construct($ip,$un,$pw,$ssl=false){
			$this->ip = $ip;
			$this->un = $un;
			$this->pw = $pw;
			$this->prefix = ($ssl) ? "https://" : "http://";
			
			// log in to the device.
			$this->curlPost("");
		}
		
		// this function enumerates the devices on a reboot bar
		public function enumerate(){
			// get the devices from http://10.15.10.214/rPDUout.htm
			$devices = $this->curlPost("/rPDUout.htm");
			
			$devices = substr($devices,stripos($devices,"</head>")+7);
			$devices = "<html>".$devices;
			
			//echo $devices;
			
			// parse this out
			$doc = new DOMDocument();
			$doc->recover = true;
			$doc->strictErrorChecking = false;
			@$doc->loadHTML($devices);
			
			// devices array
			$devs = array();
			
			foreach($doc->getElementsByTagName("input") as $node){
				// find the closest TD
				$parent = false;
				$pointer = $node;
				$i = 0;
				while($i < 5 && $parent === false){
					$i++;
					$pointer = $pointer->parentNode;
					//echo $pointer->nodeName."\n";
					if(strtolower($pointer->nodeName) == "tr")
						$parent = $pointer;
				}
				
				// we don't want this if it's not the right table row. MUST CONTAIN AN INPUT CHECKBOX!
				if($parent !== false){
					$itemID = false;
					foreach($node->attributes as $attr){
						if($attr->nodeName == "value"){
							$itemID = $attr->nodeValue;
							break;
						}
					}
					
					// make sure that this contains identifying information. 
					if($parent->nodeName == "tr" && $parent->getElementsByTagName("a")->length > 0){
						// what is our device number?
						$devOrigName = strip_tags($parent->getElementsByTagName("td")->item(3)->getElementsByTagName("a")->item(0)->nodeValue);
						$devNo = strip_tags($parent->getElementsByTagName("td")->item(1)->getElementsByTagName("font")->item(0)->nodeValue);
						$devName = $devNo.":".$devOrigName;
						
						// push this device on to our stack
						$devs[$devName] = array("id" => $itemID, "name" => $devOrigName);
						
						// get the current status
						foreach($parent->getElementsByTagName("img")->item(0)->attributes as $attr){
							if($attr->nodeName == "title"){
								$attrval = explode(":",$attr->nodeValue);
								$devs[$devName]["status"] = trim($attrval[1]);
							}
						}
					}
				}
			}
			
			// close out and clean up
			//$this->done();
			
			return $devs;
		}
		
		// this function cleans up
		public function done(){
			$this->curlPost("/logout.htm");
			curl_close($this->ch);
		}
		
		// a request start is:
		public function operate($type,$device){
			// then log open the control page
			$post = array("HX" => "", "HX2" => "", "C2" => "C2", "rPDUOutletCtrl" => $type, "OL_Cntrl_Col1_Btn" => $device, "Submit" => "Next >>");
			$this->curlPost("/Forms/rPDUout1", $post);
			$this->curlPost("/rPDUoconf.htm");
			
			// then submit the request http://10.15.10.214/Forms/rPDUoconf1
			$post = array("HX" => "", "HX2" => "", "C2" => "C2", "Control" => "", "Submit" => "Apply");
			$this->curlPost("/Forms/rPDUoconf1", $post);
			$this->curlPost("/rPDUout.htm");
		}
		
		// this function posts to the APC unit
		private function curlPost($url, $args=array()){
			// set up the url
			$url = $this->prefix.$this->ip.$url;
			
			// create the post string
			$postString = "";
			
			foreach($args as $key => $value)
				$postString .= $key."=" . urlencode( $value ) . "&";

			// MAKE THE POST
			if($this->ch === false)
				$this->ch = curl_init();
			
			curl_setopt($this->ch, CURLOPT_USERPWD, $this->un . ":" . $this->pw);
			curl_setopt($this->ch, CURLOPT_URL,$url);
			curl_setopt($this->ch, CURLOPT_HEADER, 0); 
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); 
			if($postString != "")
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postString);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
			$this->cr = curl_exec($this->ch);

			return $this->cr;
		}
	}
?>