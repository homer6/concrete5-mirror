<?php 

defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Contains the page type object.
 * @package Pages
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

/**
 * The CollectionType or PageType object represents reusable types of pages that can
 * be added to a Concrete site.
 * @package Pages
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */	
	class CollectionType extends Object {

		var $ctID;
		var $addCTUArray = array();
		var $addCTGArray = array();
		var $akIDArray = array();
		
		/**
		 * @description returns a collection type object for the given CollectionType handle
		 * @param string $ctHandle
		 * @return CollectionType
		*/
		public static function getByHandle($ctHandle) {
			$ct = Cache::get('pageTypeByHandle', $ctHandle);
			if (!is_object($ct)) {
				$db = Loader::db();
				$q = "SELECT ctID, ctHandle, ctName, ctIcon, pkgID from PageTypes where ctHandle = ?";
				$r = $db->query($q, array($ctHandle));
				if ($r) {
					$row = $r->fetchRow();
					$r->free();
					if (is_array($row)) {
						$ct = new CollectionType; 
						$row['mcID'] = $db->GetOne("select cID from Pages where ctID = ? and cIsTemplate = 1", array($row['ctID']));
						$ct->setPropertiesFromArray($row);
					}					
				}
				Cache::set('pageTypeByHandle', $ctHandle, $ct);
			}
			return $ct;
		}
		
		public function setPropertiesFromArray($row) {
			if (!$row['ctIcon']) {
				$row['ctIcon'] = FILENAME_COLLECTION_TYPE_DEFAULT_ICON;
			}
			parent::setPropertiesFromArray($row);
		}
		
		public function getMasterTemplate() {
			$db = Loader::db();
			$cID = $db->getOne("select cID from Pages where cIsTemplate = 1 and ctID = ?", array($this->ctID));
			return Page::getByID($cID, "RECENT");
		}

		public static function getByID($ctID, $obj = null) {
			if ($obj == null) {
				$ct = Cache::get('pageTypeByID', $ctID);
				if (is_object($ct)) {
					return $ct;
				}
			}
			
			$db = Loader::db();
			$q = "SELECT ctID, ctHandle, ctName, ctIcon, pkgID from PageTypes where PageTypes.ctID = ?";
			$r = $db->query($q, array($ctID));
			if ($r) {
				$row = $r->fetchRow();
				$r->free();
				if (is_array($row)) {
					$row['mcID'] = $db->GetOne("select cID from Pages where ctID = ? and cIsTemplate = 1", array($row['ctID']));
					$ct = new CollectionType; 
					$ct->setPropertiesFromArray($row);
					if ($obj) {
						$ct->limit($obj);
					} else {
						Cache::set('pageTypeByID', $ctID, $ct);
					}
				}
			}
			
			return $ct;
		}
		
		public function delete() {
			$db = Loader::db();
			$template_cID = $db->getOne("SELECT cID FROM Pages WHERE cIsTemplate = 1 and ctID = ?",array($this->ctID));
			
			if($template_cID) {
				$template = Page::getByID($template_cID);
				if($template->getCollectionID() > 1) {
					$template->delete();	
				}
			}
			
			$db->query("DELETE FROM PageTypes WHERE ctID = ?",array($this->ctID));
			$db->query("DELETE FROM PageTypeAttributes WHERE ctID = ?",array($this->ctID));
			$this->refreshCache();
		}
		
		public function limit($obj) {
			// $obj is most likely a collection. We're going to get an array of users and an array of
			// groups who can add this collection type beneath this particular collection
			$db = Loader::db();
			if ($obj instanceof Page) {
				$cpobj = $obj->getPermissionsCollectionObject();
				$v = array($cpobj->getCollectionID(), $this->getCollectionTypeID());
				$q = "select uID, gID from PagePermissionPageTypes where cID = ? and ctID = ?";
				$r = $db->query($q, $v);
				while ($row = $r->fetchRow()) {
					if ($row['uID'] != 0) {
						$this->addCTUArray[] = $row['uID'];
					}
					if ($row['gID'] != 0) {
						$this->addCTGArray[] = $row['gID'];
					}
				}
			}
		}
		
		public static function getListByPackage($pkg) {
			$db = Loader::db();
			$list = array();
			$r = $db->Execute('select ctID from PageTypes where pkgID = ? order by ctName asc', array($pkg->getPackageID()));
			while ($row = $r->FetchRow()) {
				$list[] = CollectionType::getByID($row['ctID']);
			}
			$r->Close();
			return $list;
		}	
		

		public function refreshCache() {
			Cache::delete('pageTypeByID', $this->ctID);
			Cache::delete('pageTypeByHandle', $this->ctHandle);
			Cache::delete('pageTypeList', false);
		}
		
		public static function getList($limiterType = null) {
			$db = Loader::db();

			// the purpose for this class? Well, we get an array of collection type objects,
			// we don't do a join because on big sites it's actually slower
			$mcIDs = $db->GetAll("select cID, ctID from Pages where cIsTemplate = 1");
			$masterCollectionIDs = array();
			foreach($mcIDs as $mc) {
				$masterCollectionIDs[$mc['ctID']] = $mc['cID'];
			}
			$q = "select ctID, ctHandle, ctIcon, ctName, pkgID from PageTypes order by ctName asc";
			$r = $db->query($q);

			if ($r) {
				while ($row = $r->fetchRow()) {
					$ct = new CollectionType;
					$row['mcID'] = $masterCollectionIDs[$row['ctID']];
					$ct->setPropertiesFromArray($row);
					
					if (is_array($limiterType)) {
						// an array of collection type handles that we should check against
						if (in_array($row['ctHandle'], $limiterType)) {
							$ctArray[] = $ct;
						}
					} else if (is_object($limiterType)) {
						$ct->limit($limiterType);
						$ctArray[] = $ct;
					} else {
						$ctArray[] = $ct;
					}
				}
				$r->free();
			}
			
			return $ctArray;
		}
		
		public static function add($data, $pkg = null) {
			$db = Loader::db();
			$pkgID = ($pkg == null) ? 0 : $pkg->getPackageID();
			$ctIcon = FILENAME_COLLECTION_TYPE_DEFAULT_ICON;
			if (isset($data['ctIcon'])) {
				$ctIcon = $data['ctIcon'];
			}
			$v = array($data['ctHandle'], $data['ctName'], $ctIcon, $pkgID);
			$q = "insert into PageTypes (ctHandle, ctName, ctIcon, pkgID) values (?, ?, ?, ?)";
			$r = $db->prepare($q);
			$res = $db->execute($r, $v);
			if ($res) {
				$ctID = $db->Insert_ID();
				// metadata
				
				if (is_array($data['akID'])) {
					foreach($data['akID'] as $ak) {
						$v2 = array($ctID, $ak);
						$db->query("insert into PageTypeAttributes (ctID, akID) values (?, ?)", $v2);
					}
				}
				
				// now that we've created the collection type, we create the master collection
				$dh = Loader::helper('date');
				$cDate = $dh->getSystemDateTime();
				
				$cobj = Collection::add($data);
				$cID = $cobj->getCollectionID();
				
				$mcName = ($data['cName']) ? $data['cName'] : "MC: {$data['ctName']}";
				$mcDescription = $data['cDescription'] ? $data['cDescription'] : "Master Collection For {$data['ctName']}";
				$v2 = array($cID, $ctID, 1, $pkgID);
				$q2 = "insert into Pages (cID, ctID, cIsTemplate, pkgID) values (?, ?, ?, ?)";
				$r2 = $db->prepare($q2);
				$res2 = $db->execute($r2, $v2);
				if ($res2) {
					Cache::delete('pageTypeList', false);
					return CollectionType::getByID($ctID);
				}
			}
		}
		
		public function getPages() {
			// returns an array of pages of this type. Does not check permissions
			// since this can get pretty long it actually returns a limited amount of data;
			
			$db = Loader::db();
			$pages = array();
			$r = $db->query("select Pages.cID, Collections.cDateAdded, Collections.cDateModified, max(cvID) as cvID, cvName from Pages inner join Collections on Collections.cID = Pages.cID inner join CollectionVersions on Pages.cID = CollectionVersions.cID where ctID = ? and cIsTemplate = 0 group by CollectionVersions.cID order by cvName asc;", array($this->getCollectionTypeID()));
			while ($row = $r->fetchRow()) {
				$p = new Page;
				$p->setPropertiesFromArray($row);
				$p->vObj = new CollectionVersion();
				$p->vObj->cvID = $row['cvID'];
				$p->vObj->cvName = $row['cvName'];
				$pages[] = $p;
			}
			
			return $pages;
		}
		
		public function update($data) {
			$db = Loader::db();
			$v = array($data['ctName'], $data['ctHandle'], $data['ctIcon'], $this->ctID);
			$r = $db->prepare("update PageTypes set ctName = ?, ctHandle = ?, ctIcon = ? where ctID = ?");
			$res = $db->execute($r, $v);
			
			// metadata
			$v2 = array($this->getCollectionTypeID());
			$db->query("delete from PageTypeAttributes where ctID = ?", $v2);
			
			if (is_array($data['akID'])) {
				foreach($data['akID'] as $ak) {
					$v3 = array($this->getCollectionTypeID(), $ak);
					$db->query("insert into PageTypeAttributes (ctID, akID) values (?, ?)", $v3);
				}
			}
			
			$this->refreshCache();
		}
		
		public function assignCollectionAttribute($ak) {
			// object
			$db = Loader::db();
			$db->query("insert into PageTypeAttributes (ctID, akID) values (?, ?)", array($this->getCollectionTypeID(), $ak->getAttributeKeyID()));
		}
		
		public function populateAvailableAttributeKeys() {
			$db = Loader::db();
			$v = array($this->getCollectionTypeID());
			$q = "select akID from PageTypeAttributes where ctID = ?";
			$r = $db->query($q, $v);
			if ($r) {
				while ($row = $r->fetchRow()) {
					$this->akIDArray[] = $row['akID'];
				}
			}
		}
		
		public function getIcons() {
			$f = Loader::helper('file');
			Loader::model('file_list');
			Loader::model('file_set');
			$fileList = new FileList();
			$fs = FileSet::getByName(t('Page Type Icons'));		
			if(!$fs) {
				return $f->getDirectoryContents(DIR_FILES_COLLECTION_TYPE_ICONS);				
			} else { 
				$fileList->filterBySet($fs);
				$icons = $fileList->get(100);
				if(!count($icons)) {
					$icons = $f->getDirectoryContents(DIR_FILES_COLLECTION_TYPE_ICONS);
				}
				return $icons;
			}
		}

		public function getAvailableAttributeKeys() {
			if (count($akIDArray) == 0) {
				$this->populateAvailableAttributeKeys();
			}
			$objArray = array();
			foreach($this->akIDArray as $akID) {
				$obj = CollectionAttributeKey::getByID($akID);
				if (is_object($obj)) {
					$objArray[] = $obj;
				}
			}
			return $objArray;
		}
		
		public function isAvailableCollectionTypeAttribute($akID) {
			return in_array($akID, $this->akIDArray);
		}		

		public function canAddSubCollection($obj) {
			switch(strtolower(get_class($obj))) {
				case 'group':
					return in_array($obj->getGroupID(), $this->addCTGArray);
					break;
				case 'userinfo':
					return in_array($obj->getUserID(), $this->addCTUArray);
					break;
			}
		}
		
		/**
		 * returns the complete html img tag for this collection type's icon
		*/
		public function getCollectionTypeIconImage() {
			Loader::helper('concrete/file');
			$html = Loader::helper('html');
			$icon = $this->getCollectionTypeIcon();
			if(is_numeric($icon) && $icon) {
				$f = File::getByID($icon);
				$fv = $f->getApprovedVersion(); 
				$src = $fv->getRelativePath();	
			} else { // retain support for legacy file type images
				$src = REL_DIR_FILES_COLLECTION_TYPE_ICONS.'/'.$icon;
			}
			$iconImg = '<img src="'.$src.'" height="' . COLLECTION_TYPE_ICON_HEIGHT . '" width="' . COLLECTION_TYPE_ICON_WIDTH . '" alt="'.$this->getCollectionTypeName().'" title="'.$this->getCollectionTypeName().'" />';
				
			return $iconImg;
		}
		
		
		public function getCollectionTypeID() { return $this->ctID; }
		public function getCollectionTypeName() { return $this->ctName; }
		public function getCollectionTypeHandle() { return $this->ctHandle; }
		public function getMasterCollectionID() { return $this->mcID; }
		public function getCollectionTypeIcon() {return $this->ctIcon;}
		public function getPackageID() {return $this->pkgID;}
		public function getPackageHandle() {
			return PackageList::getHandle($this->pkgID);
		}

	}
?>