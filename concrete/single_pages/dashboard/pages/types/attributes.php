<?php  if (isset($key)) { ?>

<h1><span><?php echo t('Edit Page Attribute')?></span></h1>
<div class="ccm-dashboard-inner">

<h2><?php echo t('Attribute Type')?></h2>

<strong><?php echo $type->getAttributeTypeName()?></strong>
<br/><br/>


<form method="post" action="<?php echo $this->action('edit')?>" id="ccm-attribute-key-form">

<?php  Loader::element("attribute/type_form_required", array('category' => $category, 'type' => $type, 'key' => $key)); ?>

</form>	

</div>

<h1><span><?php echo t('Delete Attribute')?></span></h1>

<div class="ccm-dashboard-inner">
	<div class="ccm-spacer"></div>
	<?php 
	$valt = Loader::helper('validation/token');
	$ih = Loader::helper('concrete/interface');
	$delConfirmJS = t('Are you sure you want to remove this attribute?');
	?>
	<script type="text/javascript">
	deleteAttribute = function() {
		if (confirm('<?php echo $delConfirmJS?>')) { 
			location.href = "<?php echo $this->url('/dashboard/pages/types/attributes', 'delete', $key->getAttributeKeyID(), $valt->generate('delete_attribute'))?>";				
		}
	}
	</script>
	<?php  print $ih->button_js(t('Delete Attribute'), "deleteAttribute()", 'left');?>

	<div class="ccm-spacer"></div>
</div>

<?php  } else { ?>

<h1><span><?php echo t('Add Page Attribute')?></span></h1>
<div class="ccm-dashboard-inner">

<h2><?php echo t('Choose Attribute Type')?></h2>

<form method="get" action="<?php echo $this->action('select_type')?>" id="ccm-attribute-type-form">

<?php echo $form->select('atID', $types)?>
<?php echo $form->submit('submit', t('Go'))?>

</form>

<?php  if (isset($type)) { ?>
	<br/>

	<form method="post" action="<?php echo $this->action('add')?>" id="ccm-attribute-key-form">

	<?php  Loader::element("attribute/type_form_required", array('category' => $category, 'type' => $type)); ?>

	</form>	
<?php  } ?>

</div>

<?php  } ?>