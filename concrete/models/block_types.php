<?php  

defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * Contains the blocktype object, the block type list (which is just a wrapper for querying the system for block types, and the block type
 * DB wrapper for ADODB.
 * @package Blocks
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
 
/**
*
* The block type list object holds types of blocks, takes care of querying the file system for newly available
* @author Andrew Embler <andrew@concrete5.org>
* @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
* @license    http://www.concrete5.org/license/     MIT License
* @package Blocks
* @category Concrete
*/	

	class BlockTypeList extends Object {
	
		var $btArray = array();
		
		function BlockTypeList($allowedBlocks = null) {
			$db = Loader::db();
			$this->btArray = array();
						
			$q = "select btID, btHandle, pkgID, btIncludeAll, btInterfaceWidth, btInterfaceHeight, btCopyWhenPropagate, btName, btDescription, btActiveWhenAdded from BlockTypes where btIsInternal = 0 ";
			if ($allowedBlocks != null) {
				$q .= ' and btID in (' . implode(',', $allowedBlocks) . ') ';
			}
			$q .= ' order by btID asc';
			
			$r = $db->query($q);
	
			if ($r) {
				while ($row = $r->fetchRow()) {
					$bt = new BlockType;
					$bt->setPropertiesFromArray($row);
					$this->btArray[] = $bt;
				}
				$r->free();
			}
											
			return $this;
		}
		
		function getBlockTypeList() {
			return $this->btArray;
		}

		// This function only surveys the web/ directory - it's not looking at the package level.		
		public static function getAvailableList() {
			$blocktypes = array();
			$dir = DIR_FILES_BLOCK_TYPES;
			$db = Loader::db();
			
			$btHandles = $db->GetCol("select btHandle from BlockTypes");
			
			$aDir = array();
			if (is_dir($dir)) {
				$handle = opendir($dir);
				while(($file = readdir($handle)) !== false) {
					if (strpos($file, '.') === false) {
						$fdir = $dir . '/' . $file;
						if (is_dir($fdir) && !in_array($file, $btHandles) && file_exists($fdir . '/' . FILENAME_BLOCK_CONTROLLER)) {
							$bt = new BlockType;
							$bt->btHandle = $file;
							$class = $bt->getBlockTypeClassFromHandle($file);
							
							require_once($fdir . '/' . FILENAME_BLOCK_CONTROLLER);
							if (!class_exists($class)) {
								throw new Exception(t("%s not found. Please check that the block controller file contains the correct class name.", $class));
							}
							$bta = new $class;
							$bt->btName = $bta->getBlockTypeName();
							$bt->btDescription = $bta->getBlockTypeDescription();
							$bt->hasCustomViewTemplate = file_exists(DIR_FILES_BLOCK_TYPES . '/' . $file . '/' . FILENAME_BLOCK_VIEW);
							$bt->hasCustomEditTemplate = file_exists(DIR_FILES_BLOCK_TYPES . '/' . $file . '/' . FILENAME_BLOCK_EDIT);
							$bt->hasCustomAddTemplate = file_exists(DIR_FILES_BLOCK_TYPES . '/' . $file . '/' . FILENAME_BLOCK_ADD);
							
							
							$btID = $db->GetOne("select btID from BlockTypes where btHandle = ?", array($file));
							$bt->installed = ($btID > 0);
							$bt->btID = $btID;
							
							$blocktypes[] = $bt;
							
						}
					}				
				}
			}
			
			return $blocktypes;
		}

			
		public static function getInstalledList() {
			$db = Loader::db();
			$r = $db->query("select * from BlockTypes order by btName asc");
			$btArray = array();
			while ($row = $r->fetchRow()) {
				$pkg = new BlockType;
				$pkg->setPropertiesFromArray($row);
				$btArray[] = $pkg;
			}
			return $btArray;
		}
		
		function getBlockTypeArray() {
			$db = Loader::db();
			$q = "select btID, pkgID, btHandle, btCopyWhenPropagate, btName, btActiveWhenAdded, btIncludeAll, btIsInternal, btInterfaceWidth, btInterfaceHeight from BlockTypes order by btID asc";
			$r = $db->query($q);
	
			if ($r) {
				while ($row = $r->fetchRow()) {
					$bt = new BlockType();
					$bt->setPropertiesFromArray($row);
					$btArray[] = $bt;
				}
				$r->free();
			}
			
			return $btArray;
		}
		
		function getAreaBlockTypes(&$a, &$cp) {
			$btl = new BlockTypeList();
			$btlaTMP = $btl->getBlockTypeList();
			$btla = array();
			foreach($btlaTMP as $bt) {
				$bt->setAreaPermissions($a, $cp);
				$btla[] = $bt;
			}
			return $btla;
		}
		
		function getBlockTypeAddAction(&$a) {
			$step = ($_REQUEST['step']) ? '&step=' . $_REQUEST['step'] : '';
			$arHandle = $a->getAreaHandle();
			$c = $a->getAreaCollectionObject();
			$cID = $c->getCollectionID();
			$str = DIR_REL . "/" . DISPATCHER_FILENAME . "?cID={$cID}&amp;areaName={$arHandle}&amp;mode=edit&amp;btask=add" . $step;
			return $str;			
		}
		
		function getBlockTypeAliasAction(&$a) {
			$step = ($_REQUEST['step']) ? '&step=' . $_REQUEST['step'] : '';
			$arHandle = $a->getAreaHandle();
			$c = $a->getAreaCollectionObject();
			$cID = $c->getCollectionID();
			$str = DIR_REL . "/" . DISPATCHER_FILENAME . "?cID={$cID}&amp;areaName={$arHandle}&amp;mode=edit&amp;btask=alias" . $step;
			return $str;			
		}
			
	}

/**
*
* @access private
*/	
	class BlockTypeDB extends ADOdb_Active_Record {
		var $_table = 'BlockTypes';
	}

/**
*
* Any type of content that can be added to pages is represented as a type of block, and thereby a block type object.
* @package Blocks
* @author Andrew Embler <andrew@concrete5.org>
* @license    http://www.concrete5.org/license/     MIT License
* @package Blocks
* @category Concrete
*/		
	class BlockType extends Object {
		
		var $addBTUArray = array();
		var $addBTGArray = array();
		public $controller;
		
		public static function getByHandle($handle) {
			$where = 'btHandle = ?';
			return BlockType::get($where, array($handle));			
		}
		
		public static function getByID($btID) {
			$where = 'btID = ?';
			return BlockType::get($where, array($btID));			
		}
		
		private static function get($where, $properties) {
			$db = Loader::db();
			
			$q = "SELECT btID, btName, btDescription, btHandle, pkgID, btActiveWhenAdded, btIsInternal, btCopyWhenPropagate, btIncludeAll, btInterfaceWidth, btInterfaceHeight from BlockTypes where {$where}";
			$r = $db->query($q, $properties);
			
			if ($r->numRows() > 0) {
				$row = $r->fetchRow();
				$bt = new BlockType;
				$bt->setPropertiesFromArray($row);
				$bt->controller = Loader::controller($bt);
				return $bt;
			}
			
		}
		
		function isBlockTypeInternal() {return $this->btIsInternal;}
		
		function getBlockTypeInterfaceWidth() {return $this->btInterfaceWidth;}
		function getBlockTypeInterfaceHeight() {return $this->btInterfaceHeight;}
		public function getPackageID() {return $this->pkgID;}
		public function getPackageHandle() {
			return PackageList::getHandle($this->pkgID);
		}
		
		function canAddBlock($obj) {
			switch(strtolower(get_class($obj))) {
				case 'group':
					return in_array($obj->getGroupID(), $this->addBTGArray);
					break;
				case 'userinfo':
					return in_array($obj->getUserID(), $this->addBTUArray);
					break;
			}
		}
		
		/** 
		 * Returns the number of unique instances of this block
		 */
		public function getCount() {
			$db = Loader::db();
			$count = $db->GetOne("select count(btID) from Blocks where btID = ?", array($this->btID));
			return $count;
		}
		
		/**
		 * Not a permissions call. Actually checks to see whether there are 0 instances of this block, AS well as
		 * that this block is not an internal one.
		 */
		public function canUnInstall() {
			$cnt = $this->getCount();
			if ($cnt > 0 || $this->isBlockTypeInternal()) {
				return false;
			}
			return true;
		}
		
		function getBlockTypeDescription() {
			return $this->btDescription;
		}
		
		function getBlockTypeCustomTemplates() {
			$btHandle = $this->getBlockTypeHandle();
			$pkgHandle = $this->getPackageHandle();

			$templates = array();
			$fh = Loader::helper('file');
			
			if (file_exists(DIR_FILES_BLOCK_TYPES . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES)) {
				$templates = array_merge($templates, $fh->getDirectoryContents(DIR_FILES_BLOCK_TYPES . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES));
			}
			if ($pkgHandle != null) {
				if (is_dir(DIR_PACKAGES . '/' . $pkgHandle)) {
					$templates = array_merge($templates, $fh->getDirectoryContents(DIR_PACKAGES . "/{$pkgHandle}/" . DIRNAME_BLOCKS . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES));
				} else {
					$templates = array_merge($templates, $fh->getDirectoryContents(DIR_PACKAGES_CORE . "/{$pkgHandle}/" . DIRNAME_BLOCKS . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES));
				}
			}
			if (file_exists(DIR_FILES_BLOCK_TYPES_CORE . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES)) {
				$templates = array_merge($templates, $fh->getDirectoryContents(DIR_FILES_BLOCK_TYPES_CORE . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES));
			}
	
			return $templates;
		}
		
		function setAreaPermissions(&$area, &$cp) {
			$db = Loader::db();
			if ($area->overrideCollectionPermissions()) {
				$setBlocksVia = "AREA";
			} else {
				if ($area->getAreaCollectionInheritID() > 0) {
					// see if the area/page we're supposed to be getting these from actually has a record
					$arOverrideCollectionPermissions = $db->getOne("select arOverrideCollectionPermissions from Areas where cID = ? and arHandle = ?", array($area->getAreaCollectionInheritID(), $area->getAreaHandle()));
					if ($arOverrideCollectionPermissions) {
						$setBlocksVia = "AREA";
					}
				}
				
				if (!isset($setBlocksVia)) {
					$setBlocksVia = "PAGE";
				}				
			}
			
			if ($setBlocksVia == "AREA") { 
				$c = $area->getAreaCollectionObject();
				$cpID = ($area->getAreaCollectionInheritID() > 0) ? $area->getAreaCollectionInheritID() : $c->getCollectionID();
				$v = array($cpID, $area->getAreaHandle(), $this->getBlockTypeID());
				$q = "select uID, gID from AreaGroupBlockTypes where cID = ? and arHandle = ? and btID = ?";
				$r = $db->query($q, $v);
				while ($row = $r->fetchRow()) {
					if ($row['uID'] != 0) {
						$this->addBTUArray[] = $row['uID'];
					}
					if ($row['gID'] != 0) {
						$this->addBTGArray[] = $row['gID'];
					}
				}
			} else {
				$cID = $area->getCollectionID();
				// we grab all the uID/gID combos from PagePermissions that can edit the page
				// then we allow them to add all the blocks they want
				$cInheritCID = $db->getOne('select cInheritPermissionsFromCID from Pages where cID = ?', array($cID));
				
				$v = array($cInheritCID);
				$q = "select uID, gID, cgPermissions from PagePermissions where cID = ?";
				$r = $db->query($q, $v);
				while ($row = $r->fetchRow()) {
					if ($row['uID'] != 0 && strpos($row['cgPermissions'], 'wa') !== false) {
						$this->addBTUArray[] = $row['uID'];
					}
					if ($row['gID'] != 0 && strpos($row['cgPermissions'], 'wa') !== false) {
						$this->addBTGArray[] = $row['gID'];
					}
				}
			}
		}

		function installBlockTypeFromPackage($btHandle, $pkg, $btID = 0) {
			$dir1 = DIR_PACKAGES . '/' . $pkg->getPackageHandle() . '/' . DIRNAME_BLOCKS;
			$dir2 = DIR_PACKAGES_CORE . '/' . $pkg->getPackageHandle() . '/' . DIRNAME_BLOCKS;
			
			if (file_exists($dir1)) {
				$dir = $dir1;
			} else {
				$dir = $dir2;
			}
			
			$bt = new BlockType;
			$bt->btHandle = $btHandle;
			$bt->pkgHandle = $pkg->getPackageHandle();
			$bt->pkgID = $pkg->getPackageID();
			return BlockType::doInstallBlockType($btHandle, $bt, $dir, $btID);
		}
		
		function installBlockType($btHandle, $btID = 0) {
		
			if ($btID == 0) {
				// then we don't allow one to already exist
				$db = Loader::db();
				$cnt = $db->GetOne("select btID from BlockTypes where btHandle = ?", array($btHandle));
				if ($cnt > 0) {
					return false;
				}
			}
			
			if (is_dir(DIR_FILES_BLOCK_TYPES_CORE . '/' . $btHandle)) {
				$dir = DIR_FILES_BLOCK_TYPES_CORE;
			} else {
				$dir = DIR_FILES_BLOCK_TYPES;
			}
			
			$bt = new BlockType;
			$bt->btHandle = $btHandle;
			$bt->pkgHandle = null;
			$bt->pkgID = 0;
			return BlockType::doInstallBlockType($btHandle, $bt, $dir, $btID);
		}
		
		/** 
		 * Renders a particular view of a block type, using the public $controller variable as the block type's controller
		 */
		public function render($view) {
			$bv = new BlockView();
			$bv->setController($this->controller);
			$bv->render($this, $view);
		}			
		
		private function doInstallBlockType($btHandle, $bt, $dir, $btID = 0) {
			$db = Loader::db();
			
			if (file_exists($dir . '/' . $btHandle . '/' . FILENAME_BLOCK_CONTROLLER)) {
				$class = $bt->getBlockTypeClassFromHandle();
				
				$path = $dir . '/' . $btHandle;
				if (!class_exists($class)) {
					require_once($dir . '/' . $btHandle . '/' . FILENAME_BLOCK_CONTROLLER);
				}
				
				Localization::setDomain($dir . '/' . $btHandle);

				$bta = new $class;
				
				// first run the subclass methods. If they work then we install the block
				$r = $bta->install($path);
				if (!$r->result) {
					return $r->message;
				}
				
				$btd = new BlockTypeDB();
				$btd->btHandle = $btHandle;
				$btd->btName = $bta->getBlockTypeName();
				$btd->btDescription = $bta->getBlockTypeDescription();
				$btd->btActiveWhenAdded = $bta->isActiveWhenAdded();
				$btd->btCopyWhenPropagate = $bta->isCopiedWhenPropagated();
				$btd->btIncludeAll = $bta->includeAll();
				$btd->btIsInternal = $bta->isBlockTypeInternal();
				$btd->btInterfaceHeight = $bta->getInterfaceHeight();
				$btd->btInterfaceWidth = $bta->getInterfaceWidth();
				$btd->pkgID = $bt->getPackageID();
				
				if ($btID > 0) {
					$btd->btID = $btID;
					$r = $btd->Replace();
				} else {
					$r = $btd->save();
				}
				
				if (!$r) {
					return $db->ErrorMsg();
				}
			} else {
				return t("No block found by that name in the core blocks directory.");
			}
		}
		
		/*
		 * Returns a path to where the block type's files are located.
		 * @access public
		 * @return string $path
		 */
		 
		public function getBlockTypePath() {
			if ($this->getPackageID() > 0) {
				$pkgHandle = $this->getPackageHandle();
				$dirp = (is_dir(DIR_PACKAGES . '/' . $pkgHandle)) ? DIR_PACKAGES : DIR_PACKAGES_CORE;
				$dir = $dirp . '/' . $pkgHandle . '/' . DIRNAME_BLOCKS . '/' . $this->getBlockTypeHandle();
			} else {
				if (is_dir(DIR_FILES_BLOCK_TYPES_CORE . '/' . $this->getBlockTypeHandle())) {
					$dir = DIR_FILES_BLOCK_TYPES_CORE . '/' . $this->getBlockTypeHandle();
				} else {
					$dir = DIR_FILES_BLOCK_TYPES . '/' . $this->getBlockTypeHandle();
				}
			}
			return $dir;	
		}
		
		 
		/*
		 * @access private
		 *
		 */
		private function _getClass() {
			$btHandle = $this->btHandle;
			$pkgHandle = $this->getPackageHandle();
			if ($pkgHandle == null) {
				if (file_exists(DIR_FILES_BLOCK_TYPES . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER)) {
					$classfile = DIR_FILES_BLOCK_TYPES . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER;
				} else if (file_exists(DIR_FILES_BLOCK_TYPES_CORE . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER)) {
					$classfile = DIR_FILES_BLOCK_TYPES_CORE . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER;
				}

			} else {
				if (file_exists(DIR_PACKAGES . "/{$pkgHandle}/" . DIRNAME_BLOCKS . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER)) {
					$classfile = DIR_PACKAGES . "/{$pkgHandle}/" . DIRNAME_BLOCKS . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER;
				} else if (file_exists(DIR_PACKAGES_CORE . "/{$pkgHandle}/" . DIRNAME_BLOCKS . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER)) {
					$classfile = DIR_PACKAGES_CORE . "/{$pkgHandle}/" . DIRNAME_BLOCKS . "/{$btHandle}/" . FILENAME_BLOCK_CONTROLLER;
				}
			}
			
			if ($classfile) {
				require_once($classfile);
				
				// takes the handle and performs some magic to get the class;
				$btHandle = $this->getBlockTypeHandle();
				// split by underscores or dashes
				$words = preg_split('/\_|\-/', $btHandle);
				for ($i = 0; $i < count($words); $i++) {
					$words[$i] = ucfirst($words[$i]);
				}
				
				$class = implode('', $words);
				$class = $class . 'BlockController';
				return $class;
			}
		}
		
		public function inc($file, $args = array()) {
			extract($args);
			$bt = $this;
			global $c;
			if ($this->getPackageID() > 0) {
				if (is_dir(DIR_PACKAGES . '/' . $this->getPackageHandle())) {
					include(DIR_PACKAGES . '/' . $this->getPackageHandle() . '/' . DIRNAME_BLOCKS . '/' . $this->getBlockTypeHandle() . '/' . $file);
				} else {
					include(DIR_PACKAGES_CORE . '/' . $this->getPackageHandle() . '/' . DIRNAME_BLOCKS . '/' . $this->getBlockTypeHandle() . '/' . $file);
				}
			} else if (file_exists(DIR_FILES_BLOCK_TYPES . '/' . $this->getBlockTypeHandle() . '/' . $file)) {
				include(DIR_FILES_BLOCK_TYPES . '/' . $this->getBlockTypeHandle() . '/' . $file);
			} else {
				include(DIR_FILES_BLOCK_TYPES_CORE . '/' . $this->getBlockTypeHandle() . '/' . $file);
			}
		}
		
		public function getBlockTypeClass() {
			$btHandle = $this->getBlockTypeHandle();
			return $this->_getClass($btHandle);
		}
		
		public function getBlockTypeClassFromHandle() {
			return $this->_getClass();
		}
		
		/** 
		 * Removes the block type. ONLY removes the block type from the blocktypes table - doesn't do any kind of 
		 * content deactivation. The frontend ensures that only blocks with no instances are removed.
		 */
		public function delete() {
			$db = Loader::db();
			$db->Execute("delete from BlockTypes where btID = ?", array($this->btID));
		}
		
		/** 
		 * Allows block types to be updated
		 * @param array $data
		 */
		 
		 public function update($data) {
		 	$db = Loader::db();
		 	$btHandle = $this->btHandle;
		 	$btName = $this->btName;
		 	$this->btDescription = $this->btDescription;
		 	if (isset($data['btHandle'])) {
		 		$btHandle = $data['btHandle'];
		 	}
		 	if (isset($data['btName'])) {
		 		$btName = $data['btName'];
		 	}
		 	if (isset($data['btDescription'])) {
		 		$btDescription = $data['btDescription'];
		 	}
		 	$db->Execute('update BlockTypes set btHandle = ?, btName = ?, btDescription = ? where btID = ?', array($btHandle, $btName, $btDescription, $this->btID));
		 }
		 
		 
		/* new addBlock action in blocktype just adds a block to the system without adding it to a collection */
		/* returns block object, which can then be assigned to a collection higher up */
		
		public function add($data) {
			$db = Loader::db();
			
			$u = new User();
			if (isset($data['uID'])) {
				$uID = $data['uID'];
			} else { 
				$uID = $u->getUserID();
			}
			$btID = $this->btID;
			$dh = Loader::helper('date');
			$bDate = $dh->getLocalDateTime();
			$bIsActive = ($this->btActiveWhenAdded == 1) ? 1 : 0;
			
			$v = array($_POST['bName'], $bDate, $bDate, $bIsActive, $btID, $uID);
			$q = "insert into Blocks (bName, bDateAdded, bDateModified, bIsActive, btID, uID) values (?, ?, ?, ?, ?, ?)";
			
			$r = $db->prepare($q);
			$res = $db->execute($r, $v);

			$bIDnew = $db->Insert_ID();

			// we get the block object for the block we just added

			if ($res) {
				$nb = Block::getByID($bIDnew);
	
				$btHandle = $this->getBlockTypeHandle();
				
				$class = $this->getBlockTypeClass();
				$bc = new $class($nb);
				$bc->save($data);
				
				return Block::getByID($bIDnew);
				
			}
			
		}
		
		// getBlockAddAction vs. getBlockTypeAddAction() - The difference is very simple. We call getBlockTypeAddAction() to grab the
		// action properties for the form that presents the drop-down select menu for selecting which type of block to add. We call the other
		// function when we've already chosen a type to add, and we're interested in actually adding the block - content completed - to the database
		
		function getBlockTypeID() {
			return $this->btID;
		}
		
		function getBlockTypeHandle() {
			return $this->btHandle;
		}
		
		function getBlockAddAction(&$a, $alternateHandler = null) {
			// Note: This is fugly, since we're just grabbing query string variables, but oh well. Not _everything_ can be object oriented
			$btID = $this->btID;
			$step = ($_REQUEST['step']) ? '&step=' . $_REQUEST['step'] : '';			
			$c = $a->getAreaCollectionObject();
			$cID = $c->getCollectionID();
			$arHandle = $a->getAreaHandle();
			
			if ($alternateHandler) {
				$str = $alternateHandler . "?cID={$cID}&arHandle={$arHandle}&btID={$btID}&mode=edit" . $step;
			} else {
				$str = DIR_REL . "/" . DISPATCHER_FILENAME . "?cID={$cID}&arHandle={$arHandle}&btID={$btID}&mode=edit" . $step;
			}
			return $str;			
		}
		
		
		function getBlockTypeName() {
			return $this->btName;
		}
		
		function isInstalled() {
			return $this->installed;
		}
		
		function getBlockTypeActiveWhenAdded() {
			return $this->btActiveWhenAdded;
		}
		
		function isCopiedWhenPropagated() {
			return $this->btCopyWhenPropagate;
		}

		function includeAll() {
			return $this->btIncludeAll;
		}
		
		function hasCustomEditTemplate() {
			return $this->hasCustomEditTemplate;
		}
		
		function hasCustomViewTemplate() {
			return $this->hasCustomViewTemplate;
		}
		
		function hasCustomAddTemplate() {
			return $this->hasCustomAddTemplate;
		}

	}
?>