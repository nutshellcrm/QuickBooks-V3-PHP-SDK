/*******************************************************************************
* Copyright 2016 Intuit
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*     http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*******************************************************************************/
<?php

/**
 * Logs API Requests/Responses To Disk
 */
class LogRequestsToDisk {

	/**
	 * Indicating whether Service Requests Logging should be enabled
	 * @var bool
	 */
	public $EnableServiceRequestsLogging;

	/**
	 * The Service Request Logging Location.
	 * @var string
	 */
	public $ServiceRequestLoggingLocation;

	/**
	 * Initializes a new instance of the LogRequestsToDisk class.
	 * @param bool enableServiceRequestLogging Value indicating whether to log request response messages
	 * @param string serviceRequestLoggingLocation Request Response logging locationl
	 */
	public function __construct($enableServiceRequestLogging=FALSE,$serviceRequestLoggingLocation=NULL)
	{
		$this->EnableServiceRequestsLogging = $enableServiceRequestLogging;
		$this->ServiceRequestLoggingLocation = $serviceRequestLoggingLocation;
	}
	
	/**
	 * Gets the log destination folder
	 * @return string log destination folder
	 */
	public function GetLogDestination()
	{
		if ($this->EnableServiceRequestsLogging)
		{
		    if (FALSE === file_exists($this->ServiceRequestLoggingLocation))
		    {
		        $this->ServiceRequestLoggingLocation = sys_get_temp_dir();
		    }
		}
		return $this->ServiceRequestLoggingLocation;
	}
	
	/**
	 * Logs the Platform Request to Disk.
	 * @param string xml The xml to log.
	 * @param string url of the request/response
	 * @param array headers HTTP headers of the request/response
	 * @param bool isRequest Specifies whether the xml is request or response.
	 */
    public function LogPlatformRequests($xml, $url, $headers, $isRequest)
    {
        if ($this->EnableServiceRequestsLogging)
        {
            if (FALSE === file_exists($this->ServiceRequestLoggingLocation))
            {
                $this->ServiceRequestLoggingLocation = sys_get_temp_dir();
            }
            
            // Use filecount to have some sort of sequence number for debugging purposes - 5 digits
			$sequenceNumber = iterator_count(new DirectoryIterator($this->ServiceRequestLoggingLocation));
			$sequenceNumber = str_pad((int)$sequenceNumber,5,"0",STR_PAD_LEFT);
		
			$iter = 0;
            $filePath = NULL;
			do
			{
	            $filePath = NULL;
	            if ($isRequest)
	            {
					$filePath = CoreConstants::REQUESTFILENAME_FORMAT;
					$filePath = str_replace("{0}", $this->ServiceRequestLoggingLocation, $filePath);
	            }
	            else
	            {
	            	$filePath = CoreConstants::RESPONSEFILENAME_FORMAT;
	            	$filePath = str_replace("{0}", $this->ServiceRequestLoggingLocation, $filePath);
	            }
				$filePath = str_replace("{1}", CoreConstants::SLASH_CHAR.$sequenceNumber.'-', $filePath);
				$filePath = str_replace("{2}", time()."-".(int)$iter, $filePath);
				$iter++;
			}
			while (file_exists($filePath));
			
            try
            {
				$collapsedHeaders = array();
				foreach($headers as $key=>$val)
					$collapsedHeaders[] = "{$key}: {$val}";
            	
            	file_put_contents($filePath,
            	                  ($isRequest?"REQUEST":"RESPONSE")." URI FOR SEQUENCE ID {$sequenceNumber}\n==================================\n{$url}\n\n",
            	                  FILE_APPEND);
            	file_put_contents($filePath,
            	                  ($isRequest?"REQUEST":"RESPONSE")." HEADERS\n================\n".implode("\n",$collapsedHeaders)."\n\n",
            	                  FILE_APPEND);
            	file_put_contents($filePath,
            	                  ($isRequest?"REQUEST":"RESPONSE")." BODY\n=============\n".$xml."\n\n",
            	                  FILE_APPEND);
            }
            catch (Exception $e)
            {
                throw new IdsException("Exception during LogPlatformRequests.");
            }
        }
    }
        
}
