<script>
function disableDropdown() {
  var purpose = document.getElementById('Purpose-value');
	var level = document.getElementById('level');
	(purpose.value == "Maintain" || purpose.value == "Produce")? level.disabled=true: level.disabled=false
}
</script>

<?php
/**
* Defines constants for the ranking algorithm
**/
define("titleMatch",2);
define("infoboxMatch",0.8);
define("distanceWords",0.6);
define("totalMatch",0.5);
define("keywordMatch",1);
define("pageSizeWeight",1);
define("contextMatch",0.3);
define("distanceBetweenWords",3);

/**
*SpecialPage contains link for PurposeCentricSearch extension
**/
class PurposeCentricSearch extends SpecialPage {
	public function __construct() {
		parent::__construct( 'PurposeCentricSearch' );
	}
	
	function execute() {
		global  $wgOut, $wgRequest;
		if ( $wgRequest->getVal( 'do' ) == 'search' ) {
			$searchQuery = $wgRequest->getVal( 'searchquery' );
			//Error handling : Empty Query
			if( $this->emptyQuery( $searchQuery ) ) {
				$wgOut->addHTML('<b><center><div style="width:250px;color:Brown; border:2px solid black">'.$this->warningImage().wfMsg('PurposeCentricSearch-error1').'</div></center></b>'.$this->buildForm() );
			}
			else {
				$searchTitle = $wgRequest->getCheck( 'searchtitle',true );
				$searchContent = $wgRequest->getCheck( 'searchcontent',true );
				//Error handling : Search in Title or content
				if ( !( $searchTitle ) and !( $searchContent ) ) {
					$wgOut->addHTML('<b><center><div style="width:250px;color:Brown; border:2px solid black">'.$this->warningImage().wfMsg('PurposeCentricSearch-error2').'</div></center></b>'.$this->buildForm() );
				}
				else {
					$nameSpaces = $wgRequest->getArray( 'namespaces', array() );
					//Error handling : Namespace not specified
					if(empty($nameSpaces)) {
						$wgOut->addHTML('<b><center><div style="width:250px;color:Brown; border:2px solid black">'.$this->warningImage().wfMsg('PurposeCentricSearch-error3').'</div></center></b>'.$this->buildForm() );
					}
					else {
						$wgOut->addHTML($this->displayResults() );
					}
				}
			}
		}
		else {
			$wgOut->addHTML( $this->buildForm() );
		}
	}
	
	/**
	* returns warning image
	**/
	function warningImage() {
	global $wgStylePath;
	$warn = $wgStylePath.'/common/images/warning.jpg';
		$retval .=  Html::element( 'img',
			array(
				'src' => $warn,	
			)
		);
		return $retval;
	}
	
	/**
	* returns 0 if searchQuery is not empty, 1 otherwise
	**/
	function emptyQuery($searchQuery) {
		$searchQueryWord = explode(' ',$searchQuery);
		$flag = 1;
		foreach($searchQueryWord as $word) {
			if($word == null) {
				continue;
			}
			else{
				$flag = 0;
				break;
			}
		}
		return $flag;
	}
	
	/**
	* accepts the query and inputs given by user and displays search results
	* @return html page containing the search query form and results
	**/
	function displayResults() {   
		global  $wgRequest;
		$selectedPurposeValue =	$wgRequest->getVal( 'Purpose-value' );
		$level = $wgRequest->getVal( 'level' );
		$queryObject = $this->getInput();
		$retval = $queryObject->executeQuery();
		return $this->buildForm( true , true , $selectedPurposeValue , $level).$retval;		
	}
	
	/**
	* builds the search query form(HTML form) which includes elements like search button ,
	* dropdown for expertise and purpose of search , namespaces
	* @param $searchTitle : match the query against title if true
	* @param $searchContent : match the query against content if true
	* @param $selectedPurposeValue : previous value of purpose dropdown , initially default => consume 
	* @param $level : expertise related to query
	* @return returns the search query form
	**/
	protected function buildForm($searchTitle = true , $searchContent = true , $selectedPurposeValue ='' , $level = '' ) {
		global $wgScript,$wgRequest,$wgOut;
		$wgOut->addInlineScript(
			$this->checkboxActionJS() .
			$this->invertJS( 'caNamespaces', $this->namespaceCheckboxes() ) 
			
		);
		$retval .= Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript ) );
		$retval .= Html::Hidden( 'title', $this->getTitle()->getPrefixedDbKey() );
		$retval .= Html::Hidden( 'do', 'search' );
		$retval .= Xml::checkLabel( wfMsg( 'PurposeCentricSearch-searchin-title' ), 'searchtitle','searchtitle', $searchTitle );
		$retval .= Xml::checkLabel( wfMsg( 'PurposeCentricSearch-searchin-content' ), 'searchcontent','searchcontent', $searchContent );
		$retval .= Xml::openElement( 'fieldset', array( 'class' => 'nested' ) );
		$retval .= Xml::element( 'legend', array( 'class' => 'PurposeCentricSearchLegend' ), wfMsg( 'PurposeCentricSearch') );
		$retval .= wfMsg('PurposeCentricSearch-SearchQuery');
		$retval .= Xml::input( 'searchquery' , 70, $wgRequest->getVal( 'searchquery' , false ) ).'&nbsp&nbsp&nbsp';
		$retval .= Xml::submitButton( wfMsg( 'PurposeCentricSearch-submit' ) ).'<br>';
		$retval .= Xml::element( 'br' );
		$retval .= Xml::closeElement( 'fieldset');	
		$retval .= Xml::openElement( 'table' );	
		$retval .= Xml::openElement( 'tr' );
		$retval .= Xml::openElement( 'td',array( 'valign' => 'top' ) );
		$retval .= Xml::openElement( 'fieldset', array( 'class' => 'nested') );
		$retval .= Xml::element( 'legend', array( 'class' => 'PurposeCentricSearchLegend' ), wfMsg('PurposeCentricSearch-PurposeExpertise' ) );
		$retval .= wfMsg('Purpose');
		
		//$retval .= Xml::listDropDown('Purpose-value',wfMsg('purpose-dropdown'),"Consume",$selectedPurposeValue);
	
		$retval .= '<select id="Purpose-value" name="Purpose-value"  onchange=disableDropdown()  >
					<option>Consume</option>
					<option>Produce</option>
					<option>Maintain</option>
					</select>';
					
		$retval .= wfMsg('Expertise');
		$retval .= Xml::listDropdown ('level',wfMsg('level-dropdown'),"Beginner",$level);
		$retval .= Xml::element( 'br' );
		$retval .= Xml::element( 'br' );
		$retval .= Xml::closeElement( 'fieldset' );
		$retval .= Xml::closeElement( 'td' );
		$retval .= Xml::openElement( 'td',array( 'valign' => 'top' ) );
		$retval .= Xml::openElement( 'fieldset', array( 'class' => 'nested') );
		$retval .= Xml::element( 'legend', array( 'class' => 'PurposeCentricSearchLegend' ), wfMsg('PurposeCentricSearch-namespaces' ) );
		$retval .= $this->namespaceTable();
		$retval .= Xml::closeElement( 'fieldset' );
		$retval .= Xml::closeElement( 'td' );
		$retval .= Xml::closeElement( 'tr' );
		$retval .= Xml::closeElement( 'table' );
		$retval .= Xml::closeElement( 'form' );
	 	return $retval;	
	}
	
	public static function searchableNamespaces() {
		global $wgContLang;
		$retval = array();
		foreach ( $wgContLang->getFormattedNamespaces() as $ns => $value ) {
			if ( $ns >= NS_MAIN ) {
				$retval[$ns] = $value;
			}
		}
		return $retval;
	}
	
	protected function namespaceCheckboxes() {
		$retval = array();
		foreach ( self::searchableNamespaces() as $ns => $unused ) {
			$retval[] = "namespaces-$ns";
		}
		return $retval;
	}

	protected function invertJS( $func, $checkboxes ) {
		$retval = "function $func(action)\n{";
		foreach ( $checkboxes as $c ) {
			$retval .= "checkboxAction('$c', action);\n";
		}
		$retval .= "}\n";
		return $retval;
	}

	protected function checkboxActionJS() {
		return <<<ENDOFLINE
function checkboxAction( c, action ) {
	var obj = document.getElementById( c );
	switch( action ) {
		case 'all':
			obj.checked = true;
			break;
		case 'none':
			obj.checked = false;
			break;
		case 'invert':
			obj.checked = !obj.checked;
	}
}
ENDOFLINE;
	}

	protected function namespaceTable() {
		global $wgRequest, $wgUser;
		$i = 0;
		$j = 0;
		$cols = 3;
		$retval = Xml::openElement( 'table' );
		$nsarr = $wgRequest->getArray( 'namespaces', array() );
		foreach ( self::searchableNamespaces() as $ns => $display ) {
			$close = false;
			if ( $i == 0 ) {
				$retval .= Xml::openElement( 'tr' );
			}
			if ( $i == $cols - 1 ) {
				$i = 0;
				$j++;
				$close = true;
			}
			else {
				$i++;
			}
			$retval .= Xml::openElement( 'td' );
			if ( $display == '' ) {
				$display = wfMsg( 'blanknamespace' );
			}
			$checked = false;
			if ( in_array( $ns, $nsarr ) ) {
				$checked = true;
			}
			elseif ( empty( $nsarr ) ) {
				$checked = $wgUser->getOption( "searchNs$ns" );
			}
			$retval .= Xml::checkLabel( $display, 'namespaces[]', "namespaces-$ns",
					$checked, array( 'value' => $ns ) );
			$retval .= Xml::closeElement( 'td' );
			if ( $close ) {
				$retval .= Xml::closeElement( 'tr' );
			}
		}
		if ( !$close ) {
			$retval .= Xml::closeElement( 'tr' );
		}
		$retval .= Xml::openElement( 'tr' );
		$retval .= Xml::openElement( 'td', array( 'colspan' => 2 ) );
		$retval .= Xml::element( 'a', array( 'href' => 'javascript:caNamespaces(\'all\');' ), wfMsg( 'PurposeCentricSearch-selectall' ) );
		$retval .= ' / ';
		$retval .= Xml::element( 'a', array( 'href' => 'javascript:caNamespaces(\'none\');' ), wfMsg( 'PurposeCentricSearch-selectnone' ) );
		$retval .= Xml::closeElement( 'td' );
		$retval .= Xml::closeElement( 'tr' );
		$retval .= Xml::closeElement( 'table' );
		return $retval;
	}
	/**
	* gets all inputs from the search query form and constructs the object of QueryProcessor class
	* @return: object of QueryProcessor class
	**/
	protected function getInput() {
		global $wgRequest,$searchQuery;
		$searchTitle = $wgRequest->getCheck( 'searchtitle',true );
		$searchContent = $wgRequest->getCheck( 'searchcontent',true );
		$selectedPurposeValue =	$wgRequest->getVal( 'Purpose-value');
		$searchQuery=$wgRequest->getVal('searchquery');
		$searchQuery = trim($searchQuery);
		$nameSpaces=$wgRequest->getArray('namespaces', array() );
		$level=$wgRequest->getVal('level');
		$queryObject=new QueryProcessor( $searchTitle,$searchContent,$searchQuery,$nameSpaces,$level,$selectedPurposeValue);
		return $queryObject;
	}

	/**
	* This is a hooked function
	* Adds Expertise dropdown to editpage
	* Expertise is assigned to page using this dropdown
	* @param $article: WikiPage modified
	* @param $user: User performing the modification
	* @param $text: New content
	* @param $summary: Edit summary/comment
	* @param $isMinor: Whether or not the edit was marked as minor
	* @param $isWatch: (No longer used)
	* @param $section: (No longer used)
	* @param $flags: Flags passed to Article::doEdit()
	* @param $revision: New Revision of the article
	* @param $status: Status object about to be returned by doEdit()
	* @param $baseRevId: the rev ID (or false) this edit was based on
	* @return true on success
	**/
	public static function savepage( $article,$user,$text,$summary,$isminor,$iswatch,$section,$flags,$revision,$status,$baseRevId ) {
		global $wgRequest;
		$articleTitle=$article->getTitle();
		$getConLevel = $wgRequest->getVal( 'conLevel' );
		if($getConLevel == "other") {
			$getConLevel = "Beginner";
			}
		$aid = $articleTitle->getArticleID( Title::GAID_FOR_UPDATE );
		$db=wfGetDB( DB_SLAVE );
		$db->update('page',array('page_expertise' =>$getConLevel),array('page_id' =>$aid),__METHOD__ );
		return true;
	}
	
	/**
	* This is a hooked function
	* Hook 'GetPreferences': modifies user preferences
	* Adds preference : UserContext to User search preferences
	* @param :$user User whose preferences are being modified.
	* @param :&$preferences: Preferences description array, to be fed to an HTMLForm object
	* @return true on success
	**/
	public static function preferences( $user,&$preferences) {
	$preferences['searchContext'] = array(
			'type' => 'textarea',
			'label-message' => "PurposeCentricSearch-UserContext",
			'section' => 'searchoptions/advancedsearchoptions',
			);
		return true;
	}
	
	/**
	* This is a Hooked function
	* Hook 'EditPageBeforeEditToolbar': allows modifying the edit toolbar above the textarea in the edit form
	* @param :&$toolbar: The toolbar HTML
	* @return true on success
	**/
	public static function LevelInToolbar(&$toolbar) {
		global $wgOut ;
		$toolbar .=wfMsg('Page-Expertise');
		$toolbar .= Xml::listDropDown('conLevel',wfMsg('conLevel-dropdown'),"Beginner");
		$toolbar .= '<br>'.'<br>';
		return true;	
	}
}
class QueryProcessor {
		protected $QP_searchTitle,$QP_searchContent,$QP_searchQuery,$QP_nameSpaces,$QP_level,$QP_purposeValue;
		static $stopWords = array(
		'a', 'a\'s', 'able', 'about', 'above', 'according', 'accordingly',
		'across', 'actually', 'after', 'afterwards', 'again', 'against',
		'ain\'t', 'all', 'allow', 'allows', 'almost', 'alone', 'along',
		'already', 'also', 'although', 'always', 'am', 'among',
		'amongst', 'an', 'and', 'another', 'any', 'anybody', 'anyhow',
		'anyone', 'anything', 'anyway', 'anyways', 'anywhere', 'apart',
		'appear', 'appreciate', 'appropriate', 'are', 'aren\'t',
		'around', 'as', 'aside', 'ask', 'asking', 'associated', 'at',
		'available', 'away', 'awfully', 'be', 'became', 'because',
		'become', 'becomes', 'becoming', 'been', 'before', 'beforehand',
		'behind', 'being', 'believe', 'below', 'beside', 'besides',
		'best', 'better', 'between', 'beyond', 'both', 'brief', 'but',
		'by', 'c\'mon', 'c\'s', 'came', 'can', 'can\'t', 'cannot',
		'cant', 'cause', 'causes', 'certain', 'certainly', 'changes',
		'clearly', 'co', 'com', 'come', 'comes', 'concerning',
		'consequently', 'consider', 'considering', 'contain',
		'containing', 'contains', 'corresponding', 'could',
		'couldn\'t', 'course', 'currently', 'definitely', 'described',
		'despite', 'did', 'didn\'t', 'different', 'do', 'does',
		'doesn\'t', 'doing', 'don\'t', 'done', 'down', 'downwards',
		'during', 'each', 'edu', 'eg', 'eight', 'either', 'else',
		'elsewhere', 'enough', 'entirely', 'especially', 'et', 'etc',
		'even', 'ever', 'every', 'everybody', 'everyone', 'everything',
		'everywhere', 'ex', 'exactly', 'example', 'except', 'far',
		'few', 'fifth', 'first', 'five', 'followed', 'following',
		'follows', 'for', 'former', 'formerly', 'forth', 'four',
		'from', 'further', 'furthermore', 'get', 'gets', 'getting',
		'given', 'gives', 'go', 'goes', 'going', 'gone', 'got',
		'gotten', 'greetings', 'had', 'hadn\'t', 'happens', 'hardly',
		'has', 'hasn\'t', 'have', 'haven\'t', 'having', 'he', 'he\'s',
		'hello', 'help', 'hence', 'her', 'here', 'here\'s',
		'hereafter', 'hereby', 'herein', 'hereupon', 'hers', 'herself',
		'hi', 'him', 'himself', 'his', 'hither', 'hopefully', 'how',
		'howbeit', 'however', 'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'ie',
		'if', 'ignored', 'immediate', 'in', 'inasmuch', 'inc',
		'indeed', 'indicate', 'indicated', 'indicates', 'inner',
		'insofar', 'instead', 'into', 'inward', 'is', 'isn\'t', 'it',
		'it\'d', 'it\'ll', 'it\'s', 'its', 'itself', 'just', 'keep',
		'keeps', 'kept', 'know', 'knows', 'known', 'last', 'lately',
		'later', 'latter', 'latterly', 'least', 'less', 'lest', 'let',
		'let\'s', 'like', 'liked', 'likely', 'little', 'look',
		'looking', 'looks', 'ltd', 'mainly', 'many', 'may', 'maybe',
		'me', 'mean', 'meanwhile', 'merely', 'might', 'more',
		'moreover', 'most', 'mostly', 'much', 'must', 'my', 'myself',
		'name', 'namely', 'nd', 'near', 'nearly', 'necessary', 'need',
		'needs', 'neither', 'never', 'nevertheless', 'new', 'next',
		'nine', 'no', 'nobody', 'non', 'none', 'noone', 'nor',
		'normally', 'not', 'nothing', 'novel', 'now', 'nowhere',
		'obviously', 'of', 'off', 'often', 'oh', 'ok', 'okay', 'old',
		'on', 'once', 'one', 'ones', 'only', 'onto', 'or', 'other',
		'others', 'otherwise', 'ought', 'our', 'ours', 'ourselves',
		'out', 'outside', 'over', 'overall', 'own', 'particular',
		'particularly', 'per', 'perhaps', 'placed', 'please', 'plus',
		'possible', 'presumably', 'probably', 'provides', 'que',
		'quite', 'qv', 'rather', 'rd', 're', 'really', 'reasonably',
		'regarding', 'regardless', 'regards', 'relatively',
		'respectively', 'right', 'said', 'same', 'saw', 'say',
		'saying', 'says', 'second', 'secondly', 'see', 'seeing',
		'seem', 'seemed', 'seeming', 'seems', 'seen', 'self', 'selves',
		'sensible', 'sent', 'serious', 'seriously', 'seven', 'several',
		'shall', 'she', 'should', 'shouldn\'t', 'since', 'six', 'so',
		'some', 'somebody', 'somehow', 'someone', 'something',
		'sometime', 'sometimes', 'somewhat', 'somewhere', 'soon',
		'specified', 'specify', 'specifying', 'still', 'sub',
		'such', 'sup', 'sure', 't\'s', 'take', 'taken', 'tell',
		'tends', 'th', 'than', 'thank', 'thanks', 'thanx', 'that',
		'that\'s', 'thats', 'the', 'their', 'theirs', 'them',
		'themselves', 'then', 'thence', 'there', 'there\'s',
		'thereafter', 'thereby', 'therefore', 'therein', 'theres',
		'thereupon', 'these', 'they', 'they\'d', 'they\'ll',
		'they\'re', 'they\'ve', 'think', 'third', 'this', 'thorough',
		'thoroughly', 'those', 'though', 'three', 'through',
		'throughout', 'thru', 'thus', 'to', 'together', 'too', 'took',
		'toward', 'towards', 'tried', 'tries', 'truly', 'try',
		'trying', 'twice', 'two', 'un', 'under', 'unfortunately',
		'unless', 'unlikely', 'until', 'unto', 'up', 'upon', 'us',
		'use', 'used', 'useful', 'uses', 'using', 'usually', 'value',
		'various', 'very', 'via', 'viz', 'vs', 'want', 'wants', 'was',
		'wasn\'t', 'way', 'we', 'we\'d', 'we\'ll', 'we\'re', 'we\'ve',
		'welcome', 'well', 'went', 'were', 'weren\'t', 'what',
		'what\'s', 'whatever', 'when', 'whence', 'whenever', 'where',
		'where\'s', 'whereafter', 'whereas', 'whereby', 'wherein',
		'whereupon', 'wherever', 'whether', 'which', 'while',
		'whither', 'who', 'who\'s', 'whoever', 'whole', 'whom',
		'whose', 'why', 'will', 'willing', 'wish', 'with', 'within',
		'without', 'won\'t', 'wonder', 'would', 'would', 'wouldn\'t',
		'yes', 'yet', 'you', 'you\'d', 'you\'ll', 'you\'re', 'you\'ve',
		'your', 'yours', 'yourself', 'yourselves', 'zero', '' );
	
	function __construct($searchTitle,$searchContent,$searchQuery,$nameSpaces,$level,$purposeValue) {
		$this->QP_searchTitle=$searchTitle;
		$this->QP_searchContent=$searchContent;
		$this->QP_searchQuery=$this->parse($searchQuery);
		$this->QP_nameSpaces=$nameSpaces;
		$this->QP_level=$level;
		$this->QP_purposeValue = $purposeValue;
		if ( $this->QP_nameSpaces == array_keys( PurposeCentricSearch::searchableNamespaces() ) ) {
			$this->QP_nameSpaces = array();
		}
	    $this->mDb = wfGetDB( DB_SLAVE );
	}

	/**
	* Selects proper function for execution depending on the purpose value selected by user
	* @return : returns search results
	**/
	function executeQuery() {
		if(count($this->QP_searchQuery) == 0) {
			$retval .= '<b><span style="color:Red">'.wfMsg('empty').'</span></b>';
			return $retval;
		}
		$purpose = $this->QP_purposeValue;
		switch( $purpose ) {
			case "Produce": {
				$retval .= $this->executeProduce();
				break;
			}
			case "Maintain": {
				$retval .= $this->executeMaintain();
				break;
			}
			default :
				$retval .=$this->executeConsume();
		}
		return $retval ;
	}	
	
	/**
	* If purpose value is "Produce" i.e. to contribute then this function is called
	* @return : returns Properly ranked results along with appropriate metadata information
	**/
	function executeProduce() {
		global $wgRequest;
		$query=$this->structureQuery();
		$result=$this->mDb->select($query['tables'],$query['fields'],$query['conds'],__METHOD__,$options,$join_conds);
		$max=$result->numrows();
		if($max == 0) {
			$retval .='<b><div style=color:Brown>'.wfMsg('PurposeCentricSearch-noResultMessage').'</div></b>';
			return $retval;
		}
		$retval .='<b><span style="color:Brown">'.wfMsg('Search-Results').'<hr><hr></span></b>';
		$retval .= $this->wantedPages();
		$counter = 0;	
		while($counter < $max) {
			$row = $result->fetchObject();
			$resultSet[]=$row;
			$counter++;
			}
		$resultSet = $this->syntaxMatching($resultSet);
		$counter = 0;	
		while ( $counter<$max ) {	
			$row = $resultSet[0][$counter];
			$resultSet[8][$counter] = $this->PageWeightProduce($row);
			$counter++;
		}
		$rankedResults = $this->sortResultsProduce( $resultSet );
		$retval .= $this->produceReportsMatch( $rankedResults, $ImageArray );
		$retval .= $this->unusedFiles();
		//$retval .= $this->displayUncategorizedPages($rankedResults);
		return $retval;
	}
	/**
	* Builds the query structure
	* @return : returns array containing tables, fields, conditions, join conditions and options of the search query
	**/
	function structureQuery() {
		global $wgRequest ,$wgOut;
		$query['tables'][] = 'page';
		$query['tables'][] = 'searchindex';
		$query['fields'][] = 'page_id';
		$query['fields'][] = 'page_expertise';
		$query['fields'][] = 'si_text';
		$query['fields'][] = 'page_len';
		$query['fields'][] = 'page_counter';
		$query['fields'][] = 'page_touched';
		$query['fields'][] = 'page_namespace';
		$query['fields'][] = 'page_title';
		$query['conds'][] = 'page_id=si_page';
		$db = $this->mDb;
		$searchQuery = $db->strencode( $this->getMatchString( $this->QP_searchQuery ) );
		if ( !self::isEmpty( $this->QP_searchQuery ) ) {
			$titlecond = $contentcond = $cond = '';
			$level=$this->QP_level;
			if ( $this->QP_searchTitle ) {
				$titlecond = "MATCH (si_title) AGAINST ('$searchQuery' IN BOOLEAN MODE)";	
			}
			if ( $this->QP_searchContent ) {
				$contentcond = "MATCH (si_text) AGAINST ('$searchQuery' IN BOOLEAN MODE)";
			}
			if ( (!empty( $titlecond ) )&& (!empty( $contentcond ) )) {
				$tempCond = "$titlecond OR $contentcond";		
			}
			if(!empty( $titlecond ) && empty( $contentcond )) {
				$tempCond= $titlecond ;
			}
			if(empty( $titlecond ) && !empty( $contentcond )) {
				$tempCond= $contentcond;
			}	
			if(empty( $titlecond ) && empty( $contentcond )) {
				$tempCond="MATCH (si_title) AGAINST ('$searchQuery' IN BOOLEAN MODE) OR MATCH (si_text) AGAINST ('$searchQuery' IN BOOLEAN MODE)";
			}
			$cond = "$tempCond";
			if ( !empty( $cond ) ) {
				$query['conds'][] = $cond;
			}
			if ( !empty( $this->QP_nameSpaces ) ) {
				$query['conds']['page_namespace'] = $this->QP_nameSpaces;
			}
		}
		return $query;
	}

	/**
	* Assigns each page in the resultSet its Weight, Page Weight is used for ranking
	* @param :$resultSet obtained after query execution
	* @return : $reults, An array of pages and thier associated weights
	**/
	function syntaxMatching( $resultSet ) {
		$contextRatio = $this->userContextMatch($resultSet);
		$max=count($resultSet);
		if(count($this->QP_searchQuery) != 1) {
			$maxDis = $this->maximumDistance($resultSet);
		}
		$counter = 0;	
		while($counter < $max) {
			$row = $resultSet[$counter];
			$results[0][$counter]= $row;
			$results[1][$counter] = $this->titleTotalMatch( $row )*totalMatch + $this->titleKeywordMatch($row)*keywordMatch;
			$results[2][$counter] = $this->infoboxTotalMatch($row)*totalMatch + $this->infoboxKeywordMatch($row)*keywordMatch;
			if(count($this->QP_searchQuery) != 1) {
			$results[3][$counter] = $this->distanceBetweenWords($row,$maxDis);
			}
			$results[4][$counter] = $this->textTotalMatch( $row );
			$results[5][$counter] = $this->textKeywordMatch($row);
			$totalWeight = ($results[1][$counter])*titleMatch + ($results[2][$counter])*infoboxMatch + ($results[3][$counter])*distanceWords + ($results[4][$counter])*totalMatch + ($results[5][$counter])*keywordMatch+($contextRatio[1][$counter])*contextMatch;
			if($this->QP_purposeValue == "Produce") {
				if ($row->page_len < 1024) {
			$totalWeight = $totalWeight + 1 * pageSizeWeight;
			}
			}
			$results[6][$counter] = $totalWeight;
			if ( $results[1][$counter] == 0 and $results[3][$counter] == 0 and $results[4][$counter] < 0.01 and $results[5][$counter] < 0.4 ) {
				$results[7][$counter] = 0;
			}
			else {
				$results[7][$counter] = 1;
			}
			$results[8][$counter] = 0;
			$counter++;
		} 
		return $results;
	}
	/**
	* Checks if user context matches with the search results obtained
	* If it matches, then to what extent( ratio )
	* @param :$resultSet obtained after query execution
	* @return : $reults, An array of pages and thier associated context match ratio (0-1)
	**/
	function userContextMatch( $resultSet ) {
		global $wgUser;
		$user =$wgUser->getId();
		$context['tables'][] = 'user_properties';
		$context['fields'][] ='up_user';
		$context['fields'][] = 'up_value';
		$context['fields'][] = 'up_property';
		$context['conds'][] = "up_property IN ('searchContext')";
		$context['conds'][] = "up_user=$user";
		$result = $this->mDb->select($context['tables'],$context['fields'],$context['conds'],__METHOD__);
		$loop = $result->numRows();
		for ( $counter = 0 ; $counter < $loop ; $counter++ ) {
			$row = $result->fetchObject();
			$contextWords = $row->up_value;
			$contextWords = $this->parse( $contextWords );
			}	
		if ( count($contextWords ) != 0) {
		for ( $counter = 0 ; $counter < count( $resultSet ); $counter++ ){	
		$row = $resultSet[$counter];
		$r = $row->si_text;
		$pageWords = explode(' ',$r);
		$arrayLength = count($pageWords);
		$keywordCount = 0; // Number of keywords matched
			foreach( $contextWords as $keyword ) {
				foreach($pageWords as $word) {
					if(strcasecmp($word,$keyword) == 0){
						$keywordCount++;
						break;
					}
				}
			}
			$ratio = $keywordCount/count($contextWords);
			$contextRatio[0][$counter] = $row;
			$contextRatio[1][$counter] = $ratio;
			}
			return $contextRatio;
		}	
	else {
		return 0;
		}
		
	}
	/**
	* Computes the ratio- (total number of words matched in the page title) / ( number of words in search query )
	* @param :$row 
	* @return : returns calculated ratio 
	**/
	function titleTotalMatch($row) {
		$titleMatchCount = 0;
		$pageTitle = $row->page_title;
		$titleWords = explode('_',$pageTitle);
		foreach($this->QP_searchQuery as $searchTerm) {
			foreach($titleWords as $words) {
				if(strcasecmp($searchTerm ,$words) == 0) {
					$titleMatchCount++;
				}
			}
		}
		$titleTotalRatio = $titleMatchCount/count($titleWords);
		return $titleTotalRatio;
	}
	
	/**
	* Computes the ratio- (number of keywords matched in the page title) / ( number of words in search query )
	* @param :$row 
	* @return : returns calculated ratio 
	**/
	function titleKeywordMatch($row) {
		$titleMatchCount = 0;
		$pageTitle = $row->page_title;
		$titleWords = explode('_',$pageTitle);
		foreach($this->QP_searchQuery as $searchTerm) {
			foreach($titleWords as $words) {
				if(strcasecmp($searchTerm ,$words) == 0) {
					$titleMatchCount++;
					break;
				}
			}
		}
		$titleKeywordRatio = $titleMatchCount/count($this->QP_searchQuery);
		return $titleKeywordRatio;
	}
	/**
	* Computes the ratio- (total number of words matched in the infobox) / ( number of words in search query )
	* @param :$row 
	* @return : returns calculated ratio 
	**/
	function infoboxTotalMatch($row) {
		$infoboxMatchCount1 = 0;
		$completeText = $row->si_text;
		$textArray = explode('.',$completeText);
		$textArray[0] = preg_replace( '/u800/','', $textArray[0] );
		$infoboxContent = $textArray[0];
		$eachWord = explode(' ',$infoboxContent);	
		foreach($this->QP_searchQuery as $searchTerm) {
			foreach($eachWord as $infoboxWord) {
				if(strcasecmp($searchTerm ,$infoboxWord) == 0) {
					$infoboxMatchCount1++;
				}
			}
		}
		return $infoboxMatchCount1;
	}
	
	function infoboxKeywordMatch($row) {
		$infoboxMatchCount = 0;	
		$completeText = $row->si_text;
		$textArray = explode('.',$completeText);
		$textArray[0] = preg_replace( '/u800/','', $textArray[0] );
		$infoboxContent = $textArray[0];
		$eachWord = explode(' ',$infoboxContent);	
		foreach($this->QP_searchQuery as $searchTerm) {
			foreach($eachWord as $infoboxWord) {
				if(strcasecmp($searchTerm ,$infoboxWord) == 0) {
					$infoboxMatchCount++;
					break;
				}
			}
		}
		return $infoboxMatchCount;
	}
	
	function textTotalMatch( $row ) {
		$r = $row->si_text;
		$pageWords = explode(' ',$r);
		$arrayLength = count($pageWords);
		$keywordCount = 0; // Number of keywords matched
		foreach( $this->QP_searchQuery as $keyword ) {
			foreach($pageWords as $word) {
				if(strcasecmp($word,$keyword)==0){
					$keywordCount++;
				}
			}
		}
		$keywordPercentage = ($keywordCount/$arrayLength);
		return $keywordPercentage;
	}
	
	function  textKeywordMatch($row) {
		$r = $row->si_text;
		$pageWords = explode(' ',$r);
		$arrayLength = count($pageWords);
		$keywordCount = 0; // Number of keywords matched
		foreach( $this->QP_searchQuery as $keyword ) {
			foreach($pageWords as $word) {
				if(strcasecmp($word,$keyword)==0){
					$keywordCount++;
					break;
				}
			}
		}
		$ratio = $keywordCount/count($this->QP_searchQuery);
		return $ratio;
	}
	
	function distanceBetweenWords($row,$maxDis) {
		if($maxDis == 0) {
		 return 0;
		}
		$distanceCount = 0;
		$text = $row->si_text;
		$completeText = explode(' ',$text);
		$keywords = $this->QP_searchQuery;
		$counter = 0;
		for($i = 0;$i < count($keywords);$i++) {
			for($j = 0; $j<count($completeText);$j++) { 
				if($completeText[$j] == $keywords[$i]) {
					for($k = 1; $k <= distanceBetweenWords; $k++ ) {
						$nextWord = $completeText[$j+$k];
						if (strcasecmp($nextWord ,$keywords[$i+1]) == 0) {
							$distanceCount++;
						}
						else {
							continue;
						}
					}
				}
			}
		}
		return $distanceCount  / $maxDis ;
	}

// Maximum distance between words in the search query  present in the pages 

	function maximumDistance($resultSet) {
		for($m = 0;$m < count($resultSet);$m++) {
		$row = $resultSet[$m];
		$distanceCount = 0;
		$text = $row->si_text;
		$completeText = explode(' ',$text);
		$keywords = $this->QP_searchQuery;
		$t = 3;
		$counter = 0;
		for($i = 0;$i < count($keywords);$i++) {
			for($j = 0; $j<count($completeText);$j++) { 
					if($completeText[$j] == $keywords[$i]) {
						for($k = 1; $k <= 3; $k++ ) {
						$nextWord = $completeText[$j+$k];
						if (strcasecmp($nextWord ,$keywords[$i+1]) == 0) {
							$distanceCount++;
						}
						else {
							continue;
						}
					}
				}
			}
		}
		$distance[] = $distanceCount ;
		}
		if(count($distance)!=0) {
			return max($distance);
		}
		else 
			return 1;
	
	}
	
// Displays results according to the expertise chosen by the user
	
	function expertiseMatch( $rankedResults ) {
		$userLevel = $this->QP_level;
		if( $userLevel == "other" ) {
			$userLevel = "Beginner";
		}
		switch( $userLevel ) {
			case "Beginner" : {
				for($e = 0; $e < count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					$pageLevel = $row->page_expertise;
					if($rankedResults[$e][1] == 1 and $pageLevel == "Beginner" ){
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				for($e = 0; $e < count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					$pageLevel = $row->page_expertise;
					if($rankedResults[$e][1] == 1 and $pageLevel == "Intermediate" ){
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				for($e = 0; $e<count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					$pageLevel = $row->page_expertise;
					if($rankedResults[$e][1] == 1 and $pageLevel == "Expert" ){
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
						
					}
				}
				$flag = 0;
				for($e = 0; $e<count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					if( $rankedResults[$e][0] != 0 ){
						$flag++;
						if($flag == 1){
							$retval .='<b><span style="color:Brown">'.wfMsg('Irrelevant').'</span></b>'.'<hr><hr>';
						}
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				break;
			}
			case "Intermediate" : {
				for($e = 0; $e<count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					$pageLevel = $row->page_expertise;
					if($rankedResults[$e][1] == 1 and ($pageLevel == "Beginner" or $pageLevel == "Intermediate" )) {
						$row = $rankedResults[$e][0];
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				for($e = 0; $e<count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					$pageLevel = $row->page_expertise;
					if( $rankedResults[$e][1] == 1 and $pageLevel == "Expert" ) {
						$row = $rankedResults[$e][0];
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				$flag = 0;
				for($e = 0; $e<count($rankedResults); $e++) {
					$row = $rankedResults[$e][0];
					if( $rankedResults[$e][0] != 0 ) {
						$flag++;
						if($flag == 1){
							$retval .='<b><span style="color:Brown">'.wfMsg('Irrelevant').'</span></b>'.'<hr><hr>';
						}
						$row = $rankedResults[$e][0];
						$rankedResults[$e][0] = 0;
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				break;
			}
			case "Expert" : {
				for($e = 0; $e<count( $rankedResults ); $e++) {
					if( $rankedResults[$e][1] == 1 ) {
						$row = $rankedResults[$e][0];
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
				$flag = 0;
				
				for($e = 0; $e<count($rankedResults); $e++) {
					if( $rankedResults[$e][1] == 0 ) {
						$flag++;
						if($flag == 1){
							$retval .='<b><span style="color:Brown">'.wfMsg('Irrelevant').'</span></b>'.'<hr><hr>';
						}
						$row = $rankedResults[$e][0];
						$retval .= $this->formatRow( $row );
						$retval .= $this->metadataConsume( $row );
					}
				}
			}
			
		}	
		return $retval;
	}
	
	function executeConsume() {
		$query=$this->structureQuery();
		$result = $this->mDb->select($query['tables'],$query['fields'],$query['conds'],__METHOD__,$options,$join_conds);
		$max=$result->numrows();
		if($max == 0) {
			$retval .='<b><div style = color:Brown>'.wfMsg('PurposeCentricSearch-noResultMessage').'</div></b>';
			return $retval;
		}
		$retval .='<b><span style="color:Brown">'.wfMsg('Search-Results').'<hr><hr></span></b>';	
		$resultSet = $this->removeNewPage( $result );
		$resultSet = $this->syntaxMatching( $resultSet );
		$rankedResults = $this->sortResultsConsume( $resultSet );
		$retval .= $this->expertiseMatch( $rankedResults );
		return $retval;
	}
	
	function removeNewPage( $result ) {
		$max=$result->numrows();
		$i = 0;
		while($i < $max) {
			$row = $result->fetchObject();
			$new=$this->uncheckedPages($row);
			if($new == 0) {
				$resultSet[] = $row;
			}
			$i++;
		}
		return $resultSet;
	
	}
	function sortResultsConsume( $resultSet ) {
		$var = 0;
		$resultSetCount = count( $resultSet[0] );
		for( $index5 = 0; $index5 < $resultSetCount; $index5++ ) {
			$maxWeight = max($resultSet[6]);
				for ( $index6 = 0; $index6 < $resultSetCount; $index6++ ) {
					if( $maxWeight == $resultSet[6][$index6] ) {
						$row=$resultSet[0][$index6];
						$rankedResults[$var][0]= $row;
						$rankedResults[$var][1] = $resultSet[7][$index6];
						$var++;
						$tmp1 = $resultSet[6][$index6];
						$resultSet[6][$index6] = 0;
						break;
					}
				}
		}
		return $rankedResults;
	}
	
	
	function sortResultsProduce( $resultSet ) {
		$var = 0;
		$resultSetCount = count( $resultSet[0] );
			for( $index5 = 0; $index5 < $resultSetCount; $index5++ ) {
			$maxWeight = max($resultSet[6]);
			
				for ( $index6 = 0; $index6 < $resultSetCount; $index6++ ) {
					if( $maxWeight == $resultSet[6][$index6] ) {
						$row=$resultSet[0][$index6];
						$rankedResults[$var][0]= $row;
						$rankedResults[$var][1] = $resultSet[7][$index6];
						$rankedResults[$var][2] = $resultSet[8][$index6];
						$var++;
						$tmp1 = $resultSet[6][$index6];
						$resultSet[6][$index6] = 0;
						break;
					}
				}
				
		}
		return $rankedResults;
	}
	function sortResultsMaintain( $resultSet ) {
		$var = 0;
		$resultSetCount = count( $resultSet[0] );
		for( $index5 = 0; $index5 < $resultSetCount; $index5++ ) {
			$maxWeight = max($resultSet[6]);
			
				for ( $index6 = 0; $index6 < $resultSetCount; $index6++ ) {
					if( $maxWeight == $resultSet[6][$index6] ) {
						$row=$resultSet[0][$index6];
						$rankedResults[$var][0]= $row;
						$rankedResults[$var][1] = $resultSet[7][$index6];
						$rankedResults[$var][2] = $resultSet[8][$index6];
						$rankedResults[$var][3] = $resultSet[9][$index6];

						$var++;
						$tmp1 = $resultSet[6][$index6];
						$resultSet[6][$index6] = 0;
						break;
					}
				}
				
		}
		return $rankedResults;
	}
	
	function executeMaintain() {
		$query=$this->structureQuery();
		$result=$this->mDb->select($query['tables'],$query['fields'],$query['conds'],__METHOD__,$options,$join_conds);
		$max=$result->numrows();
		if($max == 0) {
			$retval .='<b><div style=color:Brown>'.wfMsg('PurposeCentricSearch-noResultMessage').'</div></b>';
			return $retval;
		}
		
		$retval .='<b><span style="color:Brown">'.wfMsg('Search-Results').'<hr><hr></span></b>';
		$i = 0;	
		while($i < $max) {
			$row = $result->fetchObject();
			$resultSet[]=$row;
			$i++;
			}
		$resultSet = $this->syntaxMatching($resultSet); 
		$i = 0;	
		while ( $i<$max ) {	
			$row = $resultSet[0][$i];
			$resultSet[8][$i] = $this->uncheckedPages($row);
			$resultSet[9][$i] = $this->pendingReviews($row);
			$i++;
		}
		$rankedResults = $this->sortResultsMaintain( $resultSet );
		$retval .= $this->maintainReportsMatch( $rankedResults );
		$retval .= $this->unusedFiles();
		return $retval;
	
	}
	
	function maintainReportsMatch( $rankedResults ) {
		$totalResult = count($rankedResults);
		for($e = 0; $e < $totalResult; $e++ ) {
			$row = $rankedResults[$e][0];
			//page is new
			if( $rankedResults[$e][2] == 1 and $rankedResults[$e][1] == 1) {
				$retval1 .= $this->formatRow( $row );
				$retval1 .= '<span style ="color:#860A4E">'.wfMsg('PurposeCentricSearch-PageNew').'</span>'.'<br>';
				$retval1 .= $this->metadataMaintain( $row );
			}
			//pending changes
			elseif( $rankedResults[$e][3] == 1 and $rankedResults[$e][1] == 1) {
				$retval2 .= $this->formatRow( $row );
				$retval2 .= '<span style ="color:#860A4E">'.wfMsg('PurposeCentricSearch-Pending').'</span>'.'<br>';
				$retval2 .= $this->metadataMaintain( $row );
			}
			elseif( ( $rankedResults[$e][2] == 0 or $rankedResults[$e][3] == 0 ) and $rankedResults[$e][1] == 1) {
				$retval3 .= $this->formatRow( $row );
				$retval3 .= $this->metadataMaintain( $row );
			}
			else {
				$retval4 .= $this->formatRow( $row );
				$retval4 .= $this->metadataMaintain( $row );
			}
		}
		$retval .= "$retval1.$retval2.$retval3";
		if($retval4 != null) {
			$retval .= '<b><span style="color:Brown">'.wfMsg('Irrelevant').'</span></b>'.'<hr>'.'<hr>'."$retval4";
		}
		return $retval;
	}
	function wantedPages() {
		$wanted=WantedPagesPage::getQueryInfo();
		$wanted_result=$this->mDb->select($wanted['tables'],$wanted['fields'],$wanted['conds'],__METHOD__,$wanted['options'],$wanted['join_conds']);
		$w = 0;
		$num_row = $wanted_result->numrows();
		$termCount = count($this->QP_searchQuery);
		while ( $w < $num_row ){
			$wantedCount = 0;
			$wanted_row = $wanted_result->fetchObject();
			$wanted_title = $wanted_row->title;
			$titleWords = explode("_",$wanted_title);
			for($x=0; $x< count($titleWords) ;$x++) {
				$words=$titleWords[$x];
				for($y=0;$y< $termCount;$y++) {
				$searchTerm=$this->QP_searchQuery[$y];
					$tmp = strcasecmp($searchTerm ,$words);
					if($tmp == 0) {
						$wantedCount = $wantedCount + 1;
					}
				}
			}
			
			if($wantedCount != 0) {
				$wantedArray[0][] = $wanted_row;
				$wantedArray[1][] = $wantedCount;
			}
			$w++;
		}
		$rankedWanted = $this->wantedSort( $wantedArray );
		$a = count( $rankedWanted );
		if ( $a > 25) {
			$a = 25 ;
		}
		if($a != 0){
			$retval .= '<b>'.wfMsg('PurposeCentricSearch-Wanted').'</b>';
			for( $b = 0; $b < $a; $b++ ) {
				$retval .= $this->formatRow($rankedWanted[$b]);
			}
			$retval .= '<hr><hr>';
		}
		return $retval;
	}
	
	function wantedSort($wantedArray) {
		$var = 0;
		$wantedCount = count( $wantedArray[0] );
		for( $index5 = 0; $index5 < $wantedCount; $index5++ ) {
			
			$maxWeight = max($wantedArray[1]);
			if( $maxWeight == 0 ) {
				return $rankedWanted;
			}
			for ( $index6 = 0; $index6 < $wantedCount; $index6++ ) {
				if( $maxWeight == $wantedArray[1][$index6] ) {
						$row = $wantedArray[0][$index6];
						$rankedWanted[$var]= $row;
						$var++;
						$tmp1 = $wantedArray[1][$index6];
						$wantedArray[1][$index6] = 0;
						
					}
				}
			}
			return $rankedWanted;
	}
	function wantedFileLinks($row) {
		$missingFile =0;
		$rowId = $row->page_id;
		$wantedFile['tables'][] = 'imagelinks';
		$wantedFile['fields'][] = 'il_from';
		$wantedFile['fields'][] = 'il_to';
		$wantedFile['conds'][] = "il_from=$rowId";
		$wantedFileResult=$this->mDb->select($wantedFile['tables'],$wantedFile['fields'],$wantedFile['conds'],__METHOD__,$wantedFile['options'],$wantedFile['join_conds']);
		$numberOfWantedFile = $wantedFileResult->numrows();

		$presentImage['tables'][] = 'image';
		$presentImage['fields'][] = 'img_name';
		$presentImageResult = $this->mDb->select($presentImage['tables'],$presentImage['fields'],$presentImage['conds'],__METHOD__,$wantedFile['options'],$presentImage['join_conds']);
		$numberOfPresentImage = $presentImageResult->numrows();
		$flag = 0;
		for($i = 0; $i<$numberOfWantedFile; $i++) {
			for($j = 0; $j<$numberOfPresentImage; $j++) {
				$wantedImage = $wantedFileResult->fetchObject();
				$wantedImgName = $wantedImage->il_to;
				$newImage = $presentImageResult->fetchObject();
				$presentImgName = $newImage->img_name;
				if(strcasecmp($wantedImgName, $presentImgName) != 0){
					$missingFile = 1;
					if($flag == 0) {
						$retval .= '<b>'.wfMsg('PurposeCentricSearch-missing').'</b><br>';
						$retval .= '<span style="color:#A00000 ">'.$wantedImage->il_to.'</span>';
						$flag ++;
					}
					elseif($flag == 3 ) {
						$retval .= '<b>'.wfMsg('PurposeCentricSearch-more').'<br></b>';
						return $retval;
					}
					elseif( $flag < $numberOfWantedFile){
							$retval .='<br><span style="color:#A00000 ">'.$wantedImage->il_to.'</span>';
							$flag++;
					}
				}
			}
		}
		if ($missingFile == 1) {
			$retval .='<br>';
		}
		return $retval;
	}
	

	function produceReportsMatch( $rankedResults ){
		$pageIdDeadEnd = $this->deadEndPages();	
		$pageIdOrphaned = $this->orphanedPages();
		$pageIdBrokenRedirects = $this->brokenRedirectsPages();
		$totalResult = count($rankedResults);
		for($e = 0;$e<$totalResult;$e++ ) {
			$row = $rankedResults[$e][0];
			if( $rankedResults[$e][2] == 2 and $rankedResults[$e][1] == 1) {
				$retval1 .= $this->formatRow( $row );
				$retval1 .= $this->pageCategory( $row,$pageIdDeadEnd,$pageIdOrphaned,$pageIdBrokenRedirects);
				$retval1 .= $this->wantedFileLinks($row);
				$retval1 .= $this->metadataProduce( $row );
			}
			elseif( $rankedResults[$e][2] == 1 and $rankedResults[$e][1] == 1) {
				$retval2 .= $this->formatRow( $row );
				$retval2 .= $this->pageCategory( $row,$pageIdDeadEnd,$pageIdOrphaned,$pageIdBrokenRedirects );
				$retval2 .= $this->wantedFileLinks($row);
				$retval2 .= $this->metadataProduce( $row );
			}
			elseif( $rankedResults[$e][2] == 0 and $rankedResults[$e][1] == 1) {
				$retval3 .= $this->formatRow( $row );
				$retval3 .= $this->pageCategory( $row,$pageIdDeadEnd,$pageIdOrphaned,$pageIdBrokenRedirects );
				$retval3 .= $this->wantedFileLinks($row);
				$retval3 .= $this->metadataProduce( $row );
			}
			else {
				$retval4 .= $this->formatRow( $row );
				$retval4 .= $this->pageCategory( $row,$pageIdDeadEnd,$pageIdOrphaned,$pageIdBrokenRedirects );
				$retval4 .= $this->wantedFileLinks($row);
				$retval4 .= $this->metadataProduce( $row );
			}
			
		}
		$retval .= $retval1.$retval2.$retval3;
		if($retval4 != null) {
			$retval .= '<b><span style="color:Brown">'.wfMsg('Irrelevant').'</span></b>'.'<hr>'.'<hr>'.$retval4;
		}
		return $retval;
	}
	
	
	function PageWeightProduce($row) {
		$pageWeight = 0;
		$id=$row->page_id;
		$pageIdDeadEnd = $this->deadEndPages();
		$value1 = count( $pageIdDeadEnd );
		for( $index1 = 0; $index1 < $value1; $index1++) {
			if($pageIdDeadEnd[$index1] == $id ) {
				$pageWeight++;
				break;
				}
		}	

		$pageIdBrokenRedirects = $this->brokenRedirectsPages();
		$value2= count( $pageIdBrokenRedirects );
		for( $index2 = 0; $index2 < $value2; $index2++) {
			if( $pageIdBrokenRedirects[$index2] == $id ) {
				$pageWeight++;
				break;
			}
		}
		
		$pageIdOrphaned = $this->orphanedPages();
		$value3= count( $pageIdOrphaned );
		for( $index3 = 0; $index3 < $value3; $index3++) {
			if( $pageIdOrphaned[$index3] == $id ) {
				$pageWeight++;
				break;
			}
		}
		
		return $pageWeight;
	}	
	
	function pageCategory( $row,$pageIdDeadEnd,$pageIdOrphaned,$pageIdBrokenRedirects) {
		$title = $row->page_title;
		$id = $row->page_id;
		if(count($pageIdDeadEnd)!=0) {
		
			foreach ( $pageIdDeadEnd as $deadPage ) {
				if( $id == $deadPage ) {
					$ret1 = wfMsg('PurposeCentricSearch-deadEnd').'<br>';
					break;
				}
			}
		}
		if(count($pageIdOrphaned)!=0) {
			foreach ( $pageIdOrphaned as $lonelyPage ) {
				if( $id == $lonelyPage ) {
					$ret2 = wfMsg('PurposeCentricSearch-Lonely').'<br>';
					break;
				}
			}
		}
		if(count($pageIdBrokenRedirects)!=0) {
			foreach ( $pageIdBrokenRedirects as $brokenPage ) {
				if( $title == $brokenPage ) {
					$ret3 = wfMsg('PurposeCentricSearch-Broken-redirects').'<br>';
					break;
				}
			}
		}
		
		return '<span style="color:#860A4E">'.$ret1.$ret2.$ret3.'</span>';
	}
	
	function metadataProduce( $row ) {
		global $wgStylePath;
		$pageLen = $row->page_len;
		if( $pageLen < 1024) {
			$retval .= '<span style="color:#860A4E">'.wfMsg('lessContent').'</span>'.'<br>';
		}
		$retval .= $this->metadataInfobox( $row );
		$retval .= '<span style="color:green">'.'<b>'.wfMsg('Expertise').'</b>';
		$retval .= $row->page_expertise;
		$retval .= '&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'<b>'.wfMsg('PurposeCentricSearch-page-vote').'</b>'.'&nbsp'.'&nbsp'.'&nbsp';

		$retval .= $this->userVotes($row);
		$retval .= wfMsg('percentage');
		$retval .='&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'<b>'.wfMsg('PurposeCentricSearch-page-modification-date').'</b>';
		$dateTime = wfTimestamp( TS_RFC2822, $row->page_touched );
		$retval .= $dateTime.'</span>';
		$retval .= Xml::element( 'br' );
		$retval .= Xml::element( 'br' );			
		return $retval;
	}

	function metadataMaintain( $row ) {
		$retval .= $this->metadataInfobox( $row );
		
		// modification  count 
		$rowId = $row->page_id;
		$revCount['tables'][] = 'revision';
		$revCount['fields'][] = 'COUNT(*) AS modCount';
		$revCount['conds'][] = "rev_page = $rowId";
		$revCount['options'][] = "GROUP BY => rev_page";
		$modifyCount=$this->mDb->select($revCount['tables'],$revCount['fields'],$revCount['conds'],__METHOD__,$revCount['options']);
		$retval .='<span style="color:green">'.wfMsg('PurposeCentricSearch-modification-count');
		$retval .= $modifyCount->fetchObject()->modCount;
		 
		// last modified date
		$retval .='&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'<b>'.wfMsg('PurposeCentricSearch-page-modification-date').'</b>';
		$dateTime = wfTimestamp( TS_RFC2822, $row->page_touched );
		$retval .= $dateTime.'</span>';	
		$retval .= '<br><br>';
		return $retval;
	}
	
	function userVotes($row) {
		// user votes
		$rowId = $row->page_id;
		$vote['tables'][] = 'w4grb_avg';
		$vote['fields'][] ='pid';
		$vote['fields'][] ='avg';
		$vote['fields'][] ='n';
		$vote['conds'][] ="pid = $rowId" ;
		$metadataVote=$this->mDb->select($vote['tables'],$vote['fields'],$vote['conds'],__METHOD__);	
		$avgVote =  $metadataVote->fetchObject()->avg;
		if( !$avgVote )
			$avgVote = 0;	
			return $avgVote;
		}	
	 
	function metadataInfobox($row) {
		$flag1 = 0;
		$flag2 = 0;
		$r = $row-> si_text;
		$r = preg_replace( '/u800/','', $r );
		$r = preg_replace( '/u82e/','.', $r );
		$tempArray = explode( '----------', $r );
		$tempArray[0] = preg_replace( '/u800/','', $tempArray[0] );
	
		$infoboxWord = explode( ' ', $tempArray[0] );
		foreach($infoboxWord as $word ) {
			if (strcasecmp( "infobox", $word ) != 0) {
				continue;
			}
			else {
				$flag1 = 1;
				break;
			}
		}
		if ($flag1 == 1 ) {
			$dataLabel = preg_split( '/label[0-9]+/', $tempArray[0] );
			$size = count($dataLabel);
			for( $j = 1; $j < $size; $j++ ) {
				$data = preg_split( '/data[0-9]+/', $dataLabel[$j] );
				if(($data[0] == ' ' and $data[1] == ' ') ) {
					continue;
				}
				else {
					$flag2 = 1;
					break;
				}
			}
			if($flag2 == 1) {
				for( $j = 1; $j < $size; $j++ ) {
					$data = preg_split( '/data[0-9]+/', $dataLabel[$j] );
					if(($data[0] == ' ' and $data[1] == ' ') ) {
						continue;
					}
					$retval .= '<b>'.$data[0].'</b>';
					$content = explode(' ',$data[1]);
					$countData = count($content);
					if($countData > 25) {
						$loop = 25 ;
					}
					else {
					$loop = $countData ;
					}
					for($i = 0; $i < $loop; $i++) {
						$retval .= $content[$i]." ";
					}
					if($countData > 25) {
						$retval .=  wfMsg('PurposeCentricSearch-more');
					}
					$retval .= Xml::element( 'br' );
				}
			}
			else {
				$text = explode(' ',$tempArray[1]);
					for($i = 0; $i < 25; $i++) {
						$retval .= $text[$i]." ";
					}
				$retval .= Xml::element( 'br' );
			}			
		}
		else{
				$text = explode(' ',$r);
					for($i = 0; $i < 25; $i++) {
						$retval .= $text[$i]." ";
					}
			$retval .= Xml::element( 'br' );
		}
		return $retval;
	}

	
	function metadataConsume( $row ) {
		global $wgStylePath;
		$retval .= $this->metadataInfobox( $row );
					
		// image
		$like = $wgStylePath.'/common/images/like.jpg';
		$retval .= '<sup>'. Html::element( 'img',
			array(
				'src' => $like,	
			)
		).'</sup>';
		
		$retval .= '<span style="color:green"><i>';
		$retval .= $this->userVotes($row);
		$retval .= wfMsg('percentage');
		$retval .='<b>'.wfMsg('PurposeCentricSearch-page-vote').'</b>'.'&nbsp'.'&nbsp'.'&nbsp';
					
		//metadata
		$retval .='<b>'.wfMsg('Expertise').'</b>';
		$retval .=$row->page_expertise;
		$retval .= '&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'&nbsp'.'<b>'.wfMsg('PurposeCentricSearch-page-modification-date').'</b>';
		$dateTime = wfTimestamp( TS_RFC2822, $row->page_touched );
		$retval .= $dateTime;
		$retval .= Xml::element( 'br' );
		$retval .= '</span></i>'.'<br>';
		return $retval;
	}
	
	function formatRow( $row ) {
		global $wgUser;
		$final = null;
		$title = Title::makeTitle( $row->page_namespace,$row->page_title);
		$link = $wgUser->getSkin()->makeLinkObj( $title, htmlspecialchars( $title->getPrefixedText() ) );
		$title1 = Title::makeTitle( $row->page_namespace,$row->title);
		$link1 = $wgUser->getSkin()->makeLinkObj( $title1, htmlspecialchars( $title1->getPrefixedText() ) );
		$title2 = Title::makeTitle( $row->page_namespace,$row->il_to);
		$link2 = $wgUser->getSkin()->makeLinkObj( $title2, htmlspecialchars( $title2->getPrefixedText() ) );
		$final = $link.$link1.$link2;
		return  '<span style="color:#3366FF">'.Xml::tags( 'li', null, $final ).'</span>'."\n";
	}
	
	/**
	 * Generate a MATCH condition
	 * @param $arr array with Root Words
	 * @return string A MATCH condition
	 */
	function getMatchString( $arr ) {
		$conds = array();
		$searchEngine = SearchEngine::create();
		foreach ( $arr as $a ) {
			$subconds = array();
			foreach ( (array)$a as $b ) {
				if ( is_array( $b ) ) {
					$m = $this->getMatchString( $b );
					if ( !empty( $m ) ) {
						$subconds[] = "+($m)";
					}
				}
				else {
					global $wgContLang;
					$s = $wgContLang->normalizeForSearch( $b );
					$s = $searchEngine->normalizeText( $s );
					$s = $this->mDb->strencode( $s );
					# If $s contains spaces or ( ) :, quote it
					if ( strpos( $s, ' ' ) !== false
						|| strpos( $s, '(' ) !== false
						|| strpos( $s, ')' ) !== false
						|| strpos( $s, ':' ) !== false
					) {
						$s = "\"$s\"";
					}
					if ( !empty( $s ) ) {
						$subconds[] = "+$s";
					}
				}
			}
			$sc = implode( ' ', $subconds );
			if ( !empty( $sc ) ) {
				$conds[] = "($sc)";
			}
		}
		return implode( ' ', $conds );
	}

	function parse( $text) {
		global $wgWordnetPath;
		$tempArray = explode( ' ', $text );
		$j=0;
		for ( $i = 0; $i < count( $tempArray ); $i++ ) {
			if ( in_array( $tempArray[$i], self::$stopWords ) ) {
						continue;
			}
			else {
						$searchQuery[$j]=$tempArray[$i];
						$j++;
			} 
		}
		
		$wordCountMax  = count($searchQuery);
		for($wordCount = 0;$wordCount < $wordCountMax ;$wordCount++)
		{
			$tmpVal = $searchQuery[$wordCount] ;
			$meaning = shell_exec("cd $wgWordnetPath && wn $tmpVal -entav");
			$rootWord = explode(' ',$meaning);
			if($rootWord[3] == null ) {
				$newArray[] = $tmpVal;
			}
			else {
				$rootWord[3] = preg_replace( '/\\n/','', $rootWord[3] );
				$rootWord[3] = preg_replace( '/ /','', $rootWord[3] );
				$newArray[] = $rootWord[3];
			}
		}
	return $newArray;	
		
	}
	
 // function for Checking for empty string 
	function isEmpty( $arr ) {
		if ( empty( $arr ) ) {		
			return true;
		}
		if ( !is_array( $arr ) ) {
			return false;
		}
		foreach ( $arr as $a ) {
			if ( !self::isEmpty( $a ) ) {		
				return false;
			}
		}
		return true;
	}
// Maintenance Reports  

		function uncheckedPages($row) {
		$rowId=$row->page_id;
		$pageNew['tables'][] = 'revision';
		$pageNew['fields'][] = 'rev_page';
		$pageNew['conds'][] = "rev_page = $rowId";
		$pageNewResult=$this->mDb->select($pageNew['tables'],$pageNew['fields'],$pageNew['conds'],__METHOD__);
		$numRes = $pageNewResult->numRows();
		
		$pageNewfr['tables'][] = 'flaggedrevs';
		$pageNewfr['fields'][] = 'fr_page_id';
		$pageNewfr['conds'][] = "fr_page_id = $rowId";
		$pageNewfrResult=$this->mDb->select($pageNewfr['tables'],$pageNewfr['fields'],$pageNewfr['conds'],__METHOD__);
		$numRes1 = $pageNewfrResult->numRows();
		
		if($numRes > 0 and $numRes1 == 0) {
		return 1;
		}
		else {
		return 0;
		}
	}

	function pendingReviews($row) {
		$rowId=$row->page_id;

		$pending['tables'][] = 'flaggedpage_pending';
		$pending['fields'][] = 'fpp_page_id';
		$pending['conds'][] = "fpp_page_id = $rowId";
		$pendingResult=$this->mDb->select($pending['tables'],$pending['fields'],$pending['conds'],__METHOD__);
		$numRes = $pendingResult->numRows();
		if($numRes > 0 ) {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	function unusedFiles() {
		$retval .= '<hr><hr>';
		$unused=UnusedimagesPage::getQueryInfo();
		$unused_result=$this->mDb->select($unused['tables'],$unused['fields'],$unused['conds'],__METHOD__,$unused['options'],$unused['join_conds']);
		$w = 0;
		$num_row = $unused_result->numrows();
		$termCount = count($this->QP_searchQuery);
		while ( $w < $num_row ){
			$unusedCount = 0;
			$unused_row = $unused_result->fetchObject();
			$title = $unused_row->title;
			$unused_title=explode(".",$title);
			$titleWords = explode("_",$unused_title[0]);
			for($x=0; $x< count($titleWords) ;$x++) {
				$words=$titleWords[$x];
				for($y=0;$y< $termCount;$y++) {
				$searchTerm=$this->QP_searchQuery[$y];
					$tmp = strcasecmp($searchTerm ,$words);
					if($tmp == 0) {
						$unusedCount = $unusedCount + 1;
					}
				}
			}
			
			if($unusedCount != 0) {
				$unusedArray[0][] = $unused_row;
				$unusedArray[1][] = $unusedCount;
				}
			$w++;
		}
		$purpose = $this->QP_purposeValue;
		$rankedunused = $this->wantedSort( $unusedArray );
		$a = count( $rankedunused );
		if($a != 0) {
			if($purpose == "Produce") {
			$retval .='<b>'.wfMsg('PurposeCentricSearch-unused-Produce').'</b>';
			}
			else {
			$retval.= '<b>'.wfMsg('PurposeCentricSearch-unused-Maintain').'</b>';
			}
			for( $b = 0; $b < $a; $b++ ) {
				$retval .= $this->formatRow($rankedunused[$b]);
			}
			
		}
		return $retval;
	
	}
	
	function deadEndPages() {
		$deadEnd=DeadEndPagesPage::getQueryInfo();
		$deadEnd_result=$this->mDb->select($deadEnd['tables'],$deadEnd['fields'],$deadEnd['conds'],__METHOD__,$deadEnd['options'],$deadEnd['join_conds']);
		$deadEndNum=$deadEnd_result->numrows();
		$counter = 0;
		while ( $counter<$deadEndNum ) {
			$deadEnd_row = $deadEnd_result->fetchObject();
			$pageIdDeadEnd[$counter] = $deadEnd_row->page_id;
			$counter++;
		}
		return $pageIdDeadEnd;
	}
	
	function brokenRedirectsPages() {
		$brokenRedirects = BrokenRedirectsPage::getQueryInfo();
		$brokenRedirects_result=$this->mDb->select($brokenRedirects['tables'],$brokenRedirects['fields'],$brokenRedirects['conds'],__METHOD__,$brokenRedirects['options'],$brokenRedirects['join_conds']);
		$brokenRedirectsNum = $brokenRedirects_result->numrows();
		$u = 0;
		while ( $u<$brokenRedirectsNum ) {
			$brokenRedirects_row = $brokenRedirects_result->fetchObject();
			$pageIdBrokenRedirects[$u] = $brokenRedirects_row->title;
			$u++;
		}
		return $pageIdBrokenRedirects;
	}
	
	function orphanedPages() {
		$lonely=LonelyPagesPage::getQueryInfo();
		$lonely_result=$this->mDb->select($lonely['tables'],$lonely['fields'],$lonely['conds'],__METHOD__,$lonely['options'],$lonely['join_conds']);
		$num_row2=$lonely_result->numrows();
		$l = 0;
		while ( $l<$num_row2 ) {
			$lonely_row = $lonely_result->fetchObject();
			$pageIdLonely[$l] = $lonely_row->page_id;
			$l++;
		}
		return $pageIdLonely;
	}
	
	


}
