<?php  
	defined('C5_EXECUTE') or die(_("Access Denied."));
	$cID = $b->getBlockCollectionID();
	$c = $b->getBlockCollectionObject();
	$btw = BlockType::getByID($b->getBlockTypeID());
	$bID = $b->getBlockID();
	$arHandle = $a->getAreaHandle();
	$isAlias = $b->isAlias();
	$u = new User();
	$numChildren = (!$isAlias) ? $b->getNumChildren() : 0;
	if ($isAlias) {
		//$message = 'This item is an alias. Editing it will create a new instance of this block.';
		$deleteMessage = 'Do you want to delete this block?';
	} else if ($numChildren) {
		$editMessage = 'This block is aliased by other blocks.\nIf you edit this block, your changes will effect those other blocks.\n\nAre you sure you want to edit this block?';
		$deleteMessage = 'Do you want to delete this block? This item is an original. If you delete it, you will delete all blocks aliased to it';
	} else {
		$deleteMessage = 'Do you want to delete this block?';
	}
	if ($_GET['step']) {
		$step = "&step={$_GET['step']}";
	}
?>
	

<script type="text/javascript">
<?php  $id = $bID . $a->getAreaID(); ?>

ccm_menuObj<?php echo $id?> = new Object();
ccm_menuObj<?php echo $id?>.type = "BLOCK";
ccm_menuObj<?php echo $id?>.arHandle = '<?php echo $arHandle?>';
ccm_menuObj<?php echo $id?>.aID = <?php echo $a->getAreaID()?>;
ccm_menuObj<?php echo $id?>.bID = <?php echo $bID?>;
<?php  if ($b->isEditable() && $p->canWrite()) { ?>
ccm_menuObj<?php echo $id?>.canWrite =true;
ccm_menuObj<?php echo $id?>.width = <?php echo $btw->getBlockTypeInterfaceWidth()?>;
ccm_menuObj<?php echo $id?>.height = <?php echo $btw->getBlockTypeInterfaceHeight()?>;
<?php  }
if ($p->canAdminBlock() && PERMISSIONS_MODEL != 'simple') { ?>
ccm_menuObj<?php echo $id?>.canModifyGroups = true;
<?php  }
if ($p->canAdminBlock()) { ?>
ccm_menuObj<?php echo $id?>.canAdmin = true;
<?php  }
if ($p->canDeleteBlock()) { ?>
ccm_menuObj<?php echo $id?>.canDelete = true;
ccm_menuObj<?php echo $id?>.deleteMessage = "<?php echo $deleteMessage?>";
<?php  }
if ($c->isMasterCollection()) { ?>
ccm_menuObj<?php echo $id?>.canAliasBlockOut = true;
<?php  } 
if ($p->canWrite()) {  ?>
	ccm_menuObj<?php echo $id?>.canArrange = true;
<?php  
}
if ($editMessage) { ?>
ccm_menuObj<?php echo $id?>.editMessage = "<?php echo $editMessage?>";
<?php  } ?>
$(function() {ccm_menuInit(ccm_menuObj<?php echo $id?>)});

</script>