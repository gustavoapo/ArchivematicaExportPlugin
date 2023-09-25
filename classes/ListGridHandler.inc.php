<?php
/**
 * @file ListGridHandler.inc.php
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

class ListGridHandler extends GridHandler {
	var $request;
	var $dao;
	var $issueId;

	/**
	 * Constructor
	 */
	function __construct($request, $args) {
		parent::__construct();
		$this->request = $request;
	}

	/**
	 * Implemented methods from GridHandler.
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		return true; // Nothing is selected by default

	}

	/**
	 * @copydoc GridHandler::getSelectName()
	 */
	function getSelectName() {
		return 'selectedIssues';
	}

	/**
	 * Get DAO reference
	 * Return DAO
	 */
	function getDao(){
		return $this->dao;
	}

    /**
     * Set DAO
      @param $dao Object
     */
	function setDao($dao){
		$this->dao = $dao;
	}

	/**
	 * Get DAO reference
	 * Return $issueId
	 */
	function getIssueId(){
		return $this->issueId;
	}

    /**
     * Set IssueId
      @param $issueId
     */
	function setIssueId($issueId){
		$this->issueId = $issueId;
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$journal = $request->getJournal();
		$dao = $this->dao;
		if($dao['type'] == 'issue'){
			$issueDao = $this->dao["dao"];
			return $issueDao->getIssues($journal->getId(), $this->getGridRangeInfo($request, $this->getId()));
		}else if($dao['type'] == 'submission'){
			$submissionDao = $this->dao["dao"];
            if (get_class($submissionDao)=="SubmissionDAO")
                return $submissionDao->getExportable(
                    '1'/*contextId*/
                    ,null/*pubIdType*/
                    ,null/*title*/
                    ,null/*author*/
                    ,$this->getIssueId()/*issueId*/
                    ,null/*pubIdSettingName*/
                    ,null/*pubIdSettingValue*/
                    ,null/*$rangeInfo*/
                );
            else
                return $submissionDao->getByPublicationId($this->getIssueId(),null);
		}
	}

    /**
     * Set template
      @param $template
     */
	function setTemplate($template) {
		$this->_template = $template;
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new SelectableItemsFeature(), new PagingFeature());
	}

	/**
	 * Get the row handler - override the parent row handler. We do not need grid row actions.
	 * @return GridRow
	 */
	protected function getRowInstance() {
		$gridRow = new GridRow();
		return $gridRow;
	}


	function setUrls($request, $extraUrls = array()) {
		
	}
}


