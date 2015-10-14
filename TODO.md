
# ToDo

## EXT:tika

* Add a tab to file records in file list allowing to extract a file's text
	* user switches to tab
	* tab shows button "Show text content"
	* after clicking button, an AJAX request is fired to get the text
	* field is implemented as user func field
	* check if tab/field can be registered for text file types only
	* get text from Tika as html (maybe)
* Test the different service implementations
	* Integration
		* AppService
		* SolrCellService
	* Unit
		* SolrCellService
* Change AppService to use Process class like ServerService
* Add configuration check in EXT:reports/EXT:solr backend module
* current status report check fails when using an already running Tika Server 


## Apache Tika

* Add an option to provide tika-app.jar with a string input for
		 language detection instead of a file


## Apache Solr / SolrCell

* Add option to allow language detection through SolrCell
* Add option to retrieve SolrCell Tika version 
