<?php

class ext_source_goodpractice{
	
	private $soapClient = false;
	
	public function __construct(){
		$this->connect();
	}
	
	public function search($searchterms, $sort='fullname ASC', $page=0, $recordsperpage=50,&$totalcount){
		if($this->soapClient){
			//$ap_param = array('searchTerms'=>$searchterms,'tags'=>'','contentCategories'=>'');
			$ap_param = array('searchTerms'=>implode(' ',$searchterms));
			try {
				//Attempt search 
			    //$search = $this->soapClient->__call("GetFilterableSearchResults", array($ap_param));
				$search = $this->soapClient->GetFilterableSearchResults($ap_param);
				
				$ret =  $search->GetFilterableSearchResultsResult->ContentItems->ContentItem; //@TODO process this result into a standard form

				$totalcount = count($ret);
				
				$slinker = new url_shortner();
				
				$link_template = $slinker->parse_url('http://member.goodpractice.net/{p}' . $slinker->stringify_params($slinker->get_params_by_proname("Good Practice SSO")));
				
				array_walk($ret,array($this,'normalise'),array('ltemp'=>$link_template,'strcat'=>get_string("category")));
													
				$limitfrom = $page * $recordsperpage;
			    $limitto   = $limitfrom + $recordsperpage;
				
				$sort_param = explode(' ',$sort);
				usort($ret,$this->create_sorter($sort_param[0],$sort_param[1]));
				
				$ret = array_slice($ret,$limitfrom,$recordsperpage);
				
				if(!isset($sort_param[1])){
					$sort_param[1] = 'ASC';
				}

				return $ret;
				 
			} catch (SoapFault $fault) { 
			    debugging("Sorry, SOAP client returned the following \nERROR: ".$fault->faultcode."\nMESSAGE:".$fault->faultstring."\n"); 
			}
		}
	}
	
	
	/*
	 * Private methods
	 */
	 private function connect(){
	 	if($this->soapClient === false){
	 		// Prepare SoapHeader parameters 
			$sh_params = array( 
			            	'Username'    =>    'rob', 
			            	'Password'    =>    'gws'
						);//@TODO pull from config
			
	 		$headers = array();
			
	 		$this->soapClient = new SoapClient("https://member.goodpractice.net/gwsv2.asmx?wsdl"); //@todo config up
	 		
	 		//Create the authorisation header			
			$strHeaderComponent_Session = "<AuthorisationHeader xmlns=\"http://member.goodpractice.net/gwsv2/\"><UserName>{$sh_params['Username']}</UserName><Password>{$sh_params['Password']}</Password></AuthorisationHeader>";
			$objVar_Session_Inside = new SoapVar($strHeaderComponent_Session, XSD_ANYXML, null, null, null);
			$headers[] = new SoapHeader('https://member.goodpractice.net/gwsv2/', 'AuthorisationHeader', $objVar_Session_Inside);
			
			//Add the header to the SOAP client 
			if(!$this->soapClient->__setSoapHeaders($headers)){
				debugging('Failed to set SOAP Client Headers',DEBUG_NORMAL);
			}
			
			if(!$this->soapClient->Authenticated()->AuthenticatedResult){
				debugging('SOAP Failed to Authenticate');
			}
	 	}
	 } 

	private function create_sorter($key,$order='ASC'){
		return function($a,$b='ASC')use($key,$order){
			if($order == 'ASC'){
				if(is_string($a->{$key})){
					return strcasecmp($a->{$key}, $b->{$key});
				}else if(is_integer($a->{$key}) || is_float($a->{$key}) || is_double($a->{$key}) || is_numeric($a->{$key})){
					if($a->{$key} == $a->{$key}){
						return 0;
					}
					return (($a->{$key} > $a->{$key})?1:-1);
				}else{
					//@TODO make sure this is a valid fallback
					if($a->{$key} == $a->{$key}){
						return 0;
					}
					return (($a->{$key} > $a->{$key})?1:-1);
				}
			}else{
				if(is_string($a->{$key})){
					return strcasecmp($b->{$key},$a->{$key});
				}else if(is_integer($a->{$key}) || is_float($a->{$key}) || is_double($a->{$key}) || is_numeric($a->{$key})){
					if($a->{$key} == $a->{$key}){
						return 0;
					}
					return (($a->{$key} < $a->{$key})?1:-1);
				}else{
					//@TODO make sure this is a valid fallback
					if($a->{$key} == $a->{$key}){
						return 0;
					}
					return (($a->{$key} < $a->{$key})?1:-1);
				}
			}
		};
	}

	private function normalise($rec,$k,$params){
		
		$rec->fullname = $rec->Name;
		$rec->category = 0;
		$rec->id = $rec->Id;
		$rec->summary = $rec->ShortDescription;
		$rec->fullurl = str_replace('{p}', str_replace('~/','',$rec->Url), $params['ltemp']);
		$rec->source = 'goodpractice';
		$rec->visible = 1;
		
		$rec->summary .= "<br /><p class=\"category\">";
        //$course->summary .= "{$params['strcat']}: <a href=\"search.php?id=$course->category\">";
        //$course->summary .= $displaylist[$course->category];
        $rec->summary .= "{$params['strcat']}: {$rec->ContentCategory}";
        $rec->summary .= "</a></p>";			
	}
} 