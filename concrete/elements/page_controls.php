<?php  
defined('C5_EXECUTE') or die(_("Access Denied."));
if (isset($cp)) {

	$u = new User();
	$username = $u->getUserName();
	$vo = $c->getVersionObject();

	$statusMessage = '';
	if ($c->isCheckedOut()) {
		if (!$c->isCheckedOutByMe()) {
			$cantCheckOut = true;
			$statusMessage .= t("Another user is currently editing this page.");
		}
	}
	
	if ($c->getCollectionPointerID() > 0) {
		$statusMessage .= t("This page is an alias of one that actually appears elsewhere. ");
		$statusMessage .= "<a href='" . DIR_REL . "/" . DISPATCHER_FILENAME . "?cID=" . $c->getCollectionID() . "&ctask=approve-recent'>" . t('View/Edit Original') . "</a>";
		if ($cp->canApproveCollection()) {
			$statusMessage .= "&nbsp;|&nbsp;";
			$statusMessage .= "<a href='" . DIR_REL . "/" . DISPATCHER_FILENAME . "?cID=" . $c->getCollectionPointerOriginalID() . "&ctask=remove-alias'>" . t('Remove Alias') . "</a>";
		}
	} else {
	
		if (is_object($vo)) {
			if (!$vo->isApproved() && !$c->isEditMode()) {
				$statusMessage .= t("This page is pending approval.");
				if ($cp->canApproveCollection() && !$c->isCheckedOut()) {
					$statusMessage .= " <a href='" . DIR_REL . "/" . DISPATCHER_FILENAME . "?cID=" . $c->getCollectionID() . "&ctask=approve-recent'>" . t('Approve Version') . "</a>";
				}
			}
		}
		
		$pendingAction = $c->getPendingAction();
		if ($pendingAction == 'MOVE') {
			$statusMessage .= $statusMessage ? "&nbsp;|&nbsp;" : "";
			$statusMessage .= t("This page is being moved.");
			if ($cp->canApproveCollection() && (!$c->isCheckedOut() || ($c->isCheckedOut() && $c->isEditMode()))) {
				$statusMessage .= "<a href='" . DIR_REL . "/" . DISPATCHER_FILENAME . "?cID=" . $c->getCollectionID() . "&ctask=approve_pending_action'>" . t('Approve Move') . "</a>";
			}
		} else if ($pendingAction == 'DELETE') {
			$statusMessage .= $statusMessage ? "<br/>" : "";
			$statusMessage .= t("This page is marked for removal.");
			$children = $c->getNumChildren();
			if ($children > 0) {
				$pages = $children + 1;
				$statusMessage .= " " . t('This will remove %s pages.', $pages);
				if ($cp->canAdminPage()) {
					$statusMessage .= " <a href='" . DIR_REL . "/" . DISPATCHER_FILENAME . "?cID=" . $c->getCollectionID() . "&ctask=approve_pending_action'>" . t('Approve Delete') . "</a>";
				} else {
					$statusMessage .= " " . t('Only administrators can approve a multi-page delete operation.');
				}
			} else if ($children == 0 && $cp->canApproveCollection() && (!$c->isCheckedOut() || ($c->isCheckedOut() && $c->isEditMode()))) {
				$statusMessage .= " <a href='" . DIR_REL . "/" . DISPATCHER_FILENAME . "?cID=" . $c->getCollectionID() . "&ctask=approve_pending_action'>" . t('Approve Delete') . "</a>";
			}
		}
	
	}

	if ($cp->canWrite() || $cp->canAddSubContent() || $cp->canAdminPage()) { ?>

<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/jquery.form.2.0.2.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/jquery.ui.1.5.2.no_datepicker.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/jquery.ui.1.6b.datepicker.js"></script>
<?php   if (LANGUAGE != 'en') { ?>
	<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/i18n/ui.datepicker-<?php  echo LANGUAGE?>.js"></script>
<?php   } ?>


<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/ccm.dialog.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/ccm.base.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/tiny_mce_309/tiny_mce.js"></script>

<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_dialog.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_ui.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_calendar.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_menus.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_forms.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_asset_library.css";</style>

<div id="ccm-overlay"></div>
<div id="ccm-page-controls">
<div id="ccm-logo-wrapper"><img src="<?php  echo ASSETS_URL_IMAGES?>/logo_menu.png" width="49" height="49" id="ccm-logo" /></div>
<!--<img src="<?php  echo ASSETS_URL_IMAGES?>/logo_menu_throbber.gif" width="38" height="43" id="ccm-logo-loading" />//-->

<div id="ccm-system-nav-wrapper1">
<div id="ccm-system-nav-wrapper2">
<ul id="ccm-system-nav">
<li><a id="ccm-nav-dashboard" href="<?php  echo $this->url('/dashboard')?>"><?php  echo t('Dashboard')?></a></li>
<li><a id="ccm-nav-help" helpurl="<?php  echo MENU_HELP_URL?>" href="javascript:void(0)" ><?php  echo t('Help')?></a></li>
<li class="ccm-last"><a id="ccm-nav-logout" href="<?php  echo $this->url('/login', 'logout')?>"><?php  echo t('Sign Out')?></a></li>
</ul>
</div>
</div>

<ul id="ccm-main-nav">
<?php   if ($c->isArrangeMode()) { ?>
<li><a href="#" id="ccm-nav-save-arrange"><?php  echo t('Save Positioning')?></a></li>
<?php   } else if ($c->isEditMode()) { ?>
<li><a href="javascript:void(0)" id="ccm-nav-exit-edit"><?php  echo t('Exit Edit Mode')?></a></li>
<li><a href="javascript:void(0)" id="ccm-nav-properties"><?php  echo t('Properties')?></a></li>
<li><a href="javascript:void(0)" id="ccm-nav-design"><?php  echo t('Design')?></a></li>
<?php   if ($cp->canAdminPage()) { ?><li><a href="javascript:void(0)" id="ccm-nav-permissions"><?php  echo t('Permissions')?></a></li><?php   } ?>
<li><a href="javascript:void(0)" id="ccm-nav-versions"><?php  echo t('Versions')?></a></li>
<li><a href="javascript:void(0)" id="ccm-nav-mcd"><?php  echo t('Move/Delete')?></a></li>
<?php   } else { ?>
<li><?php   if ($cantCheckOut) { ?><span id="ccm-nav-edit"><?php  echo t('Edit Page')?></span><?php   } else { ?><a href="javascript:void(0)" id="ccm-nav-edit"><?php  echo t('Edit Page')?></a><?php   } ?></li>
<li><a href="javascript:void(0)" id="ccm-nav-add"><?php  echo t('Add Page')?></a></li>
<?php   } ?>
</ul>
</div>
<div id="ccm-page-detail"><div id="ccm-page-detail-l"><div id="ccm-page-detail-r"><div id="ccm-page-detail-content"></div></div></div>
<div id="ccm-page-detail-lower"><div id="ccm-page-detail-bl"><div id="ccm-page-detail-br"><div id="ccm-page-detail-b"></div></div></div></div>
</div>

<?php   if ($c->getCollectionParentID() > 0) { ?>
	<div id="ccm-bc">
	<div id="ccm-bc-inner">
	<?php  
		$nh = Loader::helper('navigation');
		$trail = $nh->getTrailToCollection($c);
		$trail = array_reverse($trail);
		$trail[] = $c;
	?>
	<ul>
	<?php   foreach($trail as $_c) { ?>
		<li><a href="#" onclick="javascript:location.href='<?php  echo $nh->getLinkToCollection($_c)?>'"><?php  echo $_c->getCollectionName()?></a></li>
	<?php   } ?>
	</ul>
	
	</div>
	</div>
<?php   }
	}
} ?>

<?php  
/* if ($statusMessage != '') {?>
	<div id="ccm-notification"><div id="ccm-notification-inner"><?php  echo $statusMessage?></div></div>
<?php   } */
?>