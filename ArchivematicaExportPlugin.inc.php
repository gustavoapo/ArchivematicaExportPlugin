<?php

/**
 * @file ArchivematicaExportPlugin.inc.php
 *
 * @class ArchivematicaExportPlugin
 * @brief Archivematica deposit plugin
*/


import('lib.pkp.classes.plugins.ImportExportPlugin');

class ArchivematicaExportPlugin extends ImportExportPlugin {
	var $context;
	var $submission;
	var $request;
	var $_plugin;
	var $objects = array();
    var string $error = '';

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ArchivematicaExportPlugin';
	}

	/**
	 * Get the display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.importexport.archivematica.displayName');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.archivematica.description');
	}


	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'ArchivematicaExport';
	}


	/**
	 * Display the plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */

	function display($args, $request) {
		import('plugins.importexport.archivematicaExport.classes.ArchivematicaSettingsForm');
		
		parent::display($args, $request);
		$templateMgr = TemplateManager::getManager($request);
		$journal = $request->getJournal();
		$context = $request->getContext();
		$this->context = $request->getContext();
		$this->request = $request;

		switch (array_shift($args)) {
			case 'index': //Index page
			case '':
				$apiUrl = $request->getDispatcher()->url($request, ROUTE_API, $context->getPath(), 'submissions');
				$submissionsListPanel = new \APP\components\listPanels\SubmissionsListPanel(
					'submissions',
					__('common.publications'),
					[
						'apiUrl' => $apiUrl,
						'count' => 100,
						'getParams' => new stdClass(),
						'lazyLoad' => true,
					]
				);
				$submissionsConfig = $submissionsListPanel->getConfig();
				$submissionsConfig['addUrl'] = '';
				$submissionsConfig['filters'] = array_slice($submissionsConfig['filters'], 1);
				$templateMgr->setState([
					'components' => [
						'submissions' => $submissionsConfig,
					],
				]);
				$templateMgr->assign([
					'pageComponent' => 'ImportExportPage',
				]);
				$templateMgr->display($this->getTemplateResource('exportPage.tpl'));
				break;

			case 'saveSettings':
				$form = new ArchivematicaSettingsForm($this,$context->getId());
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					return new JSONMessage(true);
				}else{
					return new JSONMessage(false);
				}
				break;

			case 'loadSettings':
				$form = new ArchivematicaSettingsForm($this,$context->getId());
				$form->initData();
				return new JSONMessage(true, $form->fetch($request));
				break;

			case 'exportSubmissions': //Send compressed XML file with articles  in native format.
				$items = array();
				$submissionDao = DAORegistry::getDAO('IssueDAO');
				$issueIds = $request->getUserVar('selectedIssues');
				$exportXml = array();
				if(!empty($issueIds)){
					foreach ($issueIds as $issueId) {
						$issue = $submissionDao->getById($issueId, $context->getId());
						if ($issue) $issues[] = $issue;
						$sended = $this->deposit($issues);
					}

				}else{
					$items = (array) $request->getUserVar('selectedSubmissions');
					$sended = $this->deposit($items);
				}

				$json = new JSONMessage(false, ($sended > 0 ? '(' . $sended . ')  ' . __('plugins.importexport.archivematica.articleExportSuccess') : "" ) . ($sended == 0 ? $this->error. __('plugins.importexport.archivematica.errorSendingFile') .  __('plugins.importexport.archivematica.errorContactAdmin') : ""));
				header('Content-Type: application/json');
				echo $json->getString();
				break;

			case 'getGrid':
				$gridType = $args[0];
				$this->getGrid($gridType, $args, $request);
				break;

			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
				break;
		}
	}


	/**
	 * Get the Grid for articles or issues.
	 * @param $gridType Type of de the Grid
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function getGrid($gridType, $args, $request){
		$offset = '';
		$this->import("classes.ListGridHandler");
		$this->import("classes.IssueGridCellProviderArchiveMatica");
		$this->import("classes.SubmissionGridCellProvider");

		if(!(empty($args))){
			$args = $args[0];
			if($args == "articles"){
				$issueId = $request->_requestVars['issueId'];
				$journal = $request->getJournal();
				$submissionDao = DAORegistry::getDAO('SubmissionDAO');
				$submissionGridCellProvider = new SubmissionGridCellProvider();
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getById($issueId);

				$gh =  new ListGridHandler($request, $args);
				$gh->setId("submission-grid");
				$gh->setIssueId($issueId);
				$submissionDao = array('type' => 'submission', 'dao' => $submissionDao);
				$gh->setDao($submissionDao);

				$gh->addColumn(new GridColumn('select',
					'plugins.importexport.archivematica.select',
					null,
					null,
					$submissionGridCellProvider,
					array('width' => 8, 'anyhtml' => true)
				));

				$gh->addColumn(new GridColumn('article',
					'plugins.importexport.archivematica.art',
					null,
					null,
					$submissionGridCellProvider,
					array('width' => 60)
				));

				$gh->addColumn(new GridColumn('status',
					'plugins.importexport.archivematica.status',
					null,
					null,
					$submissionGridCellProvider,
					array('width' => 5, 'anyhtml' => true)
				));
				$json = $gh->fetchGrid($args, $request);

				$url = explode("/", $request->getRequestPath());
				unset($url[count($url) - 1]);
				unset($url[count($url) - 1]);
				$url = implode("/", $url);
				$url = $url . '/' . 'exportSubmissions';

				$form = '<script type="text/javascript">$(function(){$("#exportSubmissionXmlForm").pkpHandler("$.pkp.controllers.form.AjaxFormHandler");});</script><form id="exportSubmissionXmlForm" class="pkp_form" action="' . $url . '" method="post">';
				$formClose = '<input type="hidden" name="issueId" value="' . $issueId . '" /><div class="section formButtons form_buttons "><button class="pkp_button submitFormButton submissionButton" type="submit" id="submitFormButton-submissions-1" >'. __('plugins.importexport.archivematica.exportSubmissions') . '</button><span class="pkp_spinner"></span></div></form>';

				$jsons = $form . $json->_content . $formClose;
				$json->_content = $jsons;
				echo $json->getString();

			}else if($args == "issues"){
				$journal = $request->getJournal();
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$dataProvider = $issueDao->getIssues($journal->getId());
				$issueGridCellProvider = new IssueGridCellProviderArchiveMatica();

				$gh =  new ListGridHandler($request, $args);
				$gh->setId("issues-grid");

				$issueDao = array('type' => 'issue', 'dao' => $issueDao);
				$gh->setDao($issueDao);

				$gh->addColumn(new GridColumn('select',
					'plugins.importexport.archivematica.select',
					null,
					null,
					$issueGridCellProvider,
					array('width' => 8, 'anyhtml' => true)
				));

				$gh->addColumn(new GridColumn('export_articles_button',
					'plugins.importexport.archivematica.exportSelectedArticles',
					null,
					null,
					$issueGridCellProvider,
					array('width' => 20, 'anyhtml' => true)
				));

				$gh->addColumn(new GridColumn('number',
					'plugins.importexport.archivematica.number',
					null,
					null,
					$issueGridCellProvider,
					array('width' => 40)
				));

				$gh->addColumn(new GridColumn('article',
					'plugins.importexport.archivematica.article',
					null,
					null,
					$issueGridCellProvider,
					array('width' => 10)
				));

				$gh->addColumn(new GridColumn('status',
					'plugins.importexport.archivematica.status',
					null,
					null,
					$issueGridCellProvider,
					array('width' => 5, 'anyhtml' => true)
				));

				echo $gh->fetchGrid($args, $request)->getString();

			}
		}
	}


	/**
	 * Get deposited files from storage service by IssueId
	 * @return int
	 */
	static function getDepositedFilesByIssueId($issueId): int
	{
		$submissionDAO = DAORegistry::getDAO('SubmissionDAO');
		$count = $submissionDAO->countRecords('SELECT p.* FROM publications p INNER JOIN publication_settings ps on p.publication_id = ps.publication_id INNER JOIN submission_settings ss ON ss.submission_id = p.submission_id WHERE ps.setting_value = ? AND ss.setting_name = ? ', array($issueId, 'depositUUID'));
		return $count;
	}

	/**
	 * Get deposited files from storage service by SubmissionId
	 * @return int
	 */
	static function isDepositedFilesBySubmissionId($submissionId): bool
	{
		$submissionDAO = DAORegistry::getDAO('SubmissionDAO');
		$count = $submissionDAO->countRecords('SELECT s.* FROM submissions s
			INNER JOIN submission_settings ss ON ss.submission_id = s.submission_id
			WHERE s.submission_id = ? AND ss.setting_name = ? '
			  , array($submissionId, 'depositUUID')
		);
		return $count;
	}

	/**
	 * Deposit to Archivemativa Storage Service
	 * @param $articleIds array of Ids
	 */
	function deposit($articleIds){
		$this->import('classes.DepositWrapper');
		$request = $this->request;
		$context = $request->getContext();
		$issueId = $request->getUserVar('issueId');
		$publishedArticleDao = DAORegistry::getDAO('SubmissionDAO');

		$depositCount = 0;
		foreach ($articleIds as $articleId) {
			$publishedArticles = $publishedArticleDao->getExportable(
                                                            $this->context->getId()/*contextId*/
                                                            ,null/*pubIdType*/
                                                            ,null/*title*/
                                                            ,null/*author*/
                                                            ,$issueId/*issueId*/
                                                            ,'issueId'/*pubIdSettingName*/
                                                            ,null/*pubIdSettingValue*/
                                                            ,null/*$rangeInfo*/
                                                        )->toArray();

            if(!($publishedArticle=$this->getByArticleId($publishedArticles, $articleId)))
                break;


			$exportXml = $this->exportSubmissions(
				$articleId,
				$context,
				$request->getUser(),
				$issueId
			);


			try {
                $deposit = new DepositWrapper($publishedArticle);
				$deposit->setNativeXML($exportXml);
				$deposit->setMetadata($request);
				$deposit->addGalleys();
				$deposit->createPackage($publishedArticle);
				$response = $this->handleDeposit($deposit);
				$data = $publishedArticleDao->retrieve('SELECT * FROM submission_settings WHERE submission_id= ? AND setting_name= ?  ', array($articleId, 'depositUUID'))->current();
				$uuid = (string)$response->id;
				$uuid = trim($uuid);
				if(strlen($uuid) > 3){
					if(!($data)){
						$publishedArticleDao->update('INSERT INTO submission_settings (submission_id, setting_name, setting_value) VALUES (?, ?, ?)', array($articleId, 'depositUUID', $uuid));
						$this->handlePackage($deposit, $uuid, false);
					}else{
						$this->handlePackage($deposit, $data->setting_value, true);
					}
					$depositCount++;
				}
				$deposit->cleanup();
			}
			catch (Exception $e) {
				$errors[] = array(
					'title' => $publishedArticle->getLocalizedTitle(),
					'message' => $e->getMessage(),
				);
			}
		}
		return $depositCount;
	}

    public function getByArticleId(array $submissions, int $articleId): ?Submission
    {
        foreach ($submissions as $submission){
            if($submission->_data['id']==$articleId){
                return $submission;
            }
        }
        $this->showError('CODE: Find Articles - ');
        return null;
    }

    public function showError($error)
    {
        $this->error = $error;
    }

	/**
	 * Generate the transfer using curl
	 * @param $deposit class containing mets file
	 */
	function handleDeposit($deposit){

		$settings = $this->getSettings();
		$package = $deposit->getPackage();
		$sac_root_out = $package->sac_root_out;
		$sac_dir_in = $package->sac_dir_in;
		$sac_metadata_filename = $package->sac_metadata_filename;

		$mets_file = $sac_root_out . DIRECTORY_SEPARATOR . $sac_dir_in . DIRECTORY_SEPARATOR . $sac_metadata_filename;

		$sac_url = $settings["ArchivematicaStorageServiceUrl"] . '/api/v1/location/' . $settings["ArchivematicaStorageServiceSpaceUUID"] . '/sword/collection/';
		$sac_u = $settings["ArchivematicaStorageServiceUser"];
		$sac_p = $settings["ArchivematicaStorageServicePassword"];

		$sac_curl = $deposit->curl_init($sac_url, $sac_u, $sac_p);

		$headers = array();
		curl_setopt($sac_curl, CURLOPT_POST, 1);

		curl_setopt($sac_curl, CURLOPT_USERAGENT, 'curl/7.64.0');
		array_push($headers, "In-Progress: true");
		array_push($headers, "Packaging: METS");
		array_push($headers, "Content-Disposition: attachment; filename=mets.xml");

		curl_setopt($sac_curl, CURLOPT_POSTFIELDS, file_get_contents($mets_file));
		curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

		$sac_resp = curl_exec($sac_curl);
		$sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
		curl_close($sac_curl);

		if (($sac_status >= 200) && ($sac_status < 300)) {
			try {
				$sac_xml = @new SimpleXMLElement($sac_resp);
				return $sac_xml;

			} catch (Exception $e) {
				throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
			}
		}

	}

	/**
	 * Send the package
	 * @param $deposit class containing package
	 * @param $uuid unique string generated by the Archivematica Storage Service
	 */
	function handlePackage($deposit, $uuid, $update){
		$settings = $this->getSettings();
		$package = $deposit->getPackage();

		$sac_root_out = $package->sac_root_out;
		$sac_dir_in = $package->sac_dir_in;
		$sac_file_out = $package->sac_file_out;

		$output = $sac_root_out . DIRECTORY_SEPARATOR . $sac_file_out;

        $sac_url = $settings["ArchivematicaStorageServiceUrl"] . '/api/v1/file/' . $uuid . '/sword/media/';

		$sac_u = $settings["ArchivematicaStorageServiceUser"];
		$sac_p = $settings["ArchivematicaStorageServicePassword"];
		$sac_curl = $deposit->curl_init($sac_url, $sac_u, $sac_p);

		$headers = array();
        curl_setopt($sac_curl, $update?CURLOPT_PUT:CURLOPT_POST, 1);

		curl_setopt($sac_curl, CURLOPT_USERAGENT, 'curl/7.64.0');
		array_push($headers, "Content-Disposition: attachment; filename=" .  $sac_file_out);

		curl_setopt($sac_curl, CURLOPT_POSTFIELDS, file_get_contents($output));
		curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

		$sac_resp = curl_exec($sac_curl);
		$sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
		curl_close($sac_curl);
		if (($sac_status >= 200) && ($sac_status < 300) || $sac_status==100) {
			try {
				//$sac_xml = @new SimpleXMLElement($sac_resp);
				$this->closeTransfer($deposit, $uuid);
			} catch (Exception $e) {
				throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
			}
		}
	}


	/**
	 * Close the transfer
	 * @param $uuid unique string generated by the Archivematica Storage Service
	 */
	function closeTransfer($deposit, $uuid){
		$settings = $this->getSettings();
		$sac_url = $settings["ArchivematicaStorageServiceUrl"] . '/api/v1/file/' . $uuid . '/sword/';
		$sac_u = $settings["ArchivematicaStorageServiceUser"];
		$sac_p = $settings["ArchivematicaStorageServicePassword"];
		$sac_curl = $deposit->curl_init($sac_url, $sac_u, $sac_p);


		$headers = array();
		array_push($headers, "In-Progress: false");
		curl_setopt($sac_curl, CURLOPT_POST, 1);
		curl_setopt($sac_curl, CURLOPT_USERAGENT, 'curl/7.64.0');
		curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

		$sac_resp = curl_exec($sac_curl);
		$sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
		curl_close($sac_curl);
	}

	/**
	 * Get the settings to authenticate and send transfer
	 */
	function getSettings(){
		$settingsArr = array();
		$contextId = $context = $this->context->getId();
		$plugin = PluginRegistry::getPlugin('importexport', 'ArchivematicaExportPlugin');
		
		$settingsArr["ArchivematicaStorageServiceUrl"] = $plugin->getSetting($contextId, 'ArchivematicaStorageServiceUrl');
		$settingsArr["ArchivematicaStorageServiceSpaceUUID"] = $plugin->getSetting($contextId, 'ArchivematicaStorageServiceSpaceUUID');
		$settingsArr["ArchivematicaStorageServiceUser"] = $plugin->getSetting($contextId, 'ArchivematicaStorageServiceUser');
		$settingsArr["ArchivematicaStorageServicePassword"] = $plugin->getSetting($contextId, 'ArchivematicaStorageServicePassword');
		return $settingsArr;
	}


	/**
	 * Get the XML for a set of submissions.
	 * @param $submissionIds array Array of submission IDs
	 * @param $context Context
	 * @param $user User|null
	 * @return string XML contents representing the supplied submission IDs.
	 */
	function exportSubmissions($submissionId, $context, $user, $issueId) {
		$submissionDao = Application::getSubmissionDAO();
		$xml = null;
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('article=>native-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new NativeImportExportDeployment($context, $user));


		$submission = $submissionDao->getById($submissionId, $context->getId());
		$submission = array($submission);
		$submissionXml = $exportFilter->execute($submission, true);

		$nodes = $submissionXml->getElementsByTagName("submission_file");
		$domArray = array();

		foreach($nodes as $node) {
			$domArray[] = $node;
		}

		foreach($domArray as $node){
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$fileId = $node->getAttribute('id');
			$submissionFile = $submissionFileDao->getRevisions($fileId)[0];

			$node->setAttribute('src', $submissionFile->path);

	        foreach ( $node->childNodes as $cnodes ) {
	        	foreach ($cnodes->childNodes as $childs) {
		            if ( $childs->nodeName == 'embed' ) {
		                if ( strlen( $childs->nodeValue ) ) {
		                	$cnodes->removeChild($childs);
		                }
		            }

	        	}

	        }

		}

		$xml = $submissionXml->saveXml();
		return $xml;
	}


    /**
      * @copydoc PKPImportExportPlugin::usage
    */
    function usage($scriptName) {
    	echo __('plugins.importexport.marcalycImporter.cliUsage', array(
    		'scriptName' => $scriptName,
    		'pluginName' => $this->getName()
    	)) . "\n";
    }


    /**
     * @see PKPImportExportPlugin::executeCLI()
    */
    function executeCLI($scriptName, &$args) {
    	$this->usage($scriptName);
    }
}


