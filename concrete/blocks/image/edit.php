<?php   
defined('C5_EXECUTE') or die(_("Access Denied."));
$includeAssetLibrary = true; 
$assetLibraryPassThru = array(
	'type' => 'image'
);
	$al = Loader::helper('concrete/asset_library');

$bf = null;
$bfo = null;

if ($controller->getFileID() > 0) { 
	$bf = $controller->getFileObject();
}
if ($controller->getFileOnstateID() > 0) { 
	$bfo = $controller->getFileOnstateObject();

}

?>
<div class="ccm-block-field-group">
<h2><?php  echo t('Image')?></h2>
<?php  echo $al->image('ccm-b-image', 'fID', t('Choose Image'), $bf);?>
</div>
<div class="ccm-block-field-group">
<h2><?php  echo t('Image On-State')?> (<?php  echo t('Optional')?>)</h2>
<?php  echo $al->image('ccm-b-image-onstate', 'fOnstateID', t('Choose Image On-State'), $bfo);?>
</div>

<div class="ccm-block-field-group">
<h2><?php  echo t('Image Links to URL')?></h2>
<?php  echo  $form->text('externalLink', $externalLink, array('style' => 'width: 250px')); ?>
</div>

<div class="ccm-block-field-group">
<h2><?php  echo t('Alt Text/Caption')?></h2>
<?php  echo  $form->text('altText', $altText, array('style' => 'width: 250px')); ?>
</div>