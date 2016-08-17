<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesCMSMainExtension extends LeftAndMainExtension {

	private static $allowed_actions = array(
		'addSite'
	);

	public function getCMSTreeTitle() {
		return _t('Multisites.SITES', 'Sites');
	}

	
	/**
	 * init (called from LeftAndMain extension hook)
	 **/
	public function init(){
		// set the htmleditor "content_css" based on the active site
		$htmlEditorConfig = HtmlEditorConfig::get_active();
		$site = Multisites::inst()->getActiveSite();
		if($site && $theme = $site->getSiteTheme()){
			$cssFile = THEMES_DIR . "/$theme/css/editor.css";
			if(file_exists(BASE_PATH . '/' . $cssFile)){
				$htmlEditorConfig->setOption('content_css', $cssFile);
				
				if($this->owner->getRequest()->isAjax() && $this->owner->class == 'CMSPageEditController'){
					// Add editor css path to header so javascript can update ssTinyMceConfig.content_css
					$this->owner->getResponse()->addHeader('X-HTMLEditor_content_css', $cssFile);	
				}
				
			}	
		}
	}



	/**
	* LinkSiteAdd
	 * @return String
	 **/
	public function LinkSiteAdd($params = null) {
	    $link = singleton("CMSMain")->Link('addSite');
	
	    if($params) {
	        $link = Controller::join_links ($link, $params);
	    }
	
	    return $link;
	}


	/**
	 * addSite action to add a new site
	 **/
	public function addSite() {
		$site = $this->owner->getNewItem('new-Site-0', false);
		$site->write();

		return $this->owner->redirect(
			singleton('CMSPageEditController')->Link("show/$site->ID")
		);
	}

	/**
	 * If viewing 'Site', disable preview panel.
	 */
	public function updateEditForm($form) {
        $classNameField = $form->Fields()->dataFieldByName('ClassName');
        if ($classNameField) {
            $className = $classNameField->Value();
            if ($className === 'Site') 
            {
            	$form->Fields()->removeByName(array('SilverStripeNavigator'));
                $form->removeExtraClass('cms-previewable');
            }
        }
    }

	/**
	 * Adds a dropdown field to the search form to filter searches by Site
	 **/
	public function updateSearchForm(Form $form) {
		$cms = $this->owner;
		$req = $cms->getRequest();

		$sites = Site::get()->sort(array(
			'IsDefault' => 'DESC',
			'Title'     => 'ASC'
		));

		$site = new DropdownField(
			'q[SiteID]',
			_t('Multisites.SITE', 'Site'),
			$sites->map(),
			isset($req['q']['SiteID']) ? $req['q']['SiteID'] : null
		);
		$site->setEmptyString(_t('Multisites.ALLSITES', 'All sites'));

		$form->Fields()->insertAfter($site, 'q[Term]');
	}
	
	
	/**
	 * Makes the default page id the first child of the current site
	 * This makes the site tree view load with the current site open instead of just the first one
	 **/
	public function updateCurrentPageID(&$id){
		if (!$id) {
			if($site = Multisites::inst()->getCurrentSite()){
				$id = $site->Children()->first();
			}
		}
	}

}
