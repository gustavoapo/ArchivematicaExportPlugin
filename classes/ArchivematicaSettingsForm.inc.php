  <?php

  /**
   * @file ArchivematicaSettingsForm.inc.php
   *
   * @class ArchivematicaSettingsForm
   * @brief Form class for save settings
   */

  import('lib.pkp.classes.form.Form');
  class ArchivematicaSettingsForm extends Form {

    //
	// Private properties
	//
	/** @var integer */
	var $_contextId;

	/**
	 * Get the context ID.
	 * @return integer
	 */
	function _getContextId() {
		return $this->_contextId;
	}

	/** @var ArchivematicaExportPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return ArchivematicaExportPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}

	//
	// Constructor
	//
    /**
     * Constructor
     * @param $plugin ArchivematicaExportPlugin
     */

    public function __construct($plugin,$contextId) {
      $this->_contextId = $contextId;
		  $this->_plugin = $plugin;      
      
      parent::__construct($plugin->getTemplateResource('settings.tpl'));

      $this->addCheck(new FormValidatorPost($this));
      $this->addCheck(new FormValidatorCSRF($this));
      $this->addCheck(new FormValidatorUrl($this, 'ArchivematicaStorageServiceUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.archivematica.invalidUrl'));
      $this->addCheck(new FormValidator($this, 'ArchivematicaStorageServiceSpaceUUID', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.archivematica.fieldRequired'));
      $this->addCheck(new FormValidator($this, 'ArchivematicaStorageServiceUser', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.archivematica.fieldRequired'));
      $this->addCheck(new FormValidator($this, 'ArchivematicaStorageServicePassword', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.archivematica.fieldRequired'));

      $this->initData();
    }


    //
    // Implement template methods from Form
    //
    /**
     * @copydoc Form::initData()
     */
    function initData() {
      $contextId = $this->_plugin->context->getId();
      $this->setData('ArchivematicaStorageServiceUrl', $this->_plugin->getSetting($contextId, 'ArchivematicaStorageServiceUrl'));
      $this->setData('ArchivematicaStorageServiceSpaceUUID', $this->_plugin->getSetting($contextId, 'ArchivematicaStorageServiceSpaceUUID'));
      $this->setData('ArchivematicaStorageServiceUser', $this->_plugin->getSetting($contextId, 'ArchivematicaStorageServiceUser'));
      $this->setData('ArchivematicaStorageServicePassword', $this->_plugin->getSetting($contextId, 'ArchivematicaStorageServicePassword'));
      parent::initData();
    }

    /**
     * @copydoc Form::readInputData()
     */
    function readInputData() {
      $this->setData('ArchivematicaStorageServiceUrl', $this->_plugin->request->_requestVars['ArchivematicaStorageServiceUrl']);
      $this->setData('ArchivematicaStorageServiceSpaceUUID', $this->_plugin->request->_requestVars['ArchivematicaStorageServiceSpaceUUID']);
      $this->setData('ArchivematicaStorageServiceUser', $this->_plugin->request->_requestVars['ArchivematicaStorageServiceUser']);
      $this->setData('ArchivematicaStorageServicePassword', $this->_plugin->request->_requestVars['ArchivematicaStorageServicePassword']);
      parent::readInputData();
    }


    /**
     * Display the form
     */
    public function fetch($request, $template = null, $display = false) {
      $templateMgr = TemplateManager::getManager($request);
      $templateMgr->assign('pluginName', $this->_plugin->getName());
      return parent::fetch($request, $template, $display);
    }

    /**
     * Save settings
     */
    public function execute(...$functionArgs) {
      $plugin = $this->_getPlugin();
		  $contextId = $this->_getContextId();
      $this->_plugin->updateSetting($contextId, 'ArchivematicaStorageServiceUrl', $this->getData('ArchivematicaStorageServiceUrl'));
      $this->_plugin->updateSetting($contextId, 'ArchivematicaStorageServiceSpaceUUID', $this->getData('ArchivematicaStorageServiceSpaceUUID'));
      $this->_plugin->updateSetting($contextId, 'ArchivematicaStorageServiceUser', $this->getData('ArchivematicaStorageServiceUser'));
      $this->_plugin->updateSetting($contextId, 'ArchivematicaStorageServicePassword', $this->getData('ArchivematicaStorageServicePassword'));

      import('classes.notification.NotificationManager');
      $notificationMgr = new NotificationManager();
      $notificationMgr->createTrivialNotification(
        $plugin->request->getUser()->getId(),
        NOTIFICATION_TYPE_SUCCESS,
        ['contents' => __('common.changesSaved')]
      );
      return parent::execute(...$functionArgs);
    }

    //
	// Public helper methods
	//
	/**
	 * Get form fields
	 * @return array (field name => field type)
	 */
	function getFormFields() {
		return array(
			'ArchivematicaStorageServiceUrl' => 'string',
			'ArchivematicaStorageServiceSpaceUUID' => 'string',
			'ArchivematicaStorageServiceUser' => 'string',
			'ArchivematicaStorageServicePassword' => 'string'
		);
	}
} 