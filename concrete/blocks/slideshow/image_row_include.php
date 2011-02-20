<?php  defined('C5_EXECUTE') or die(_("Access Denied.")); ?> 
<div id="ccm-slideshowBlock-imgRow<?php echo $imgInfo['slideshowImgId']?>" class="ccm-slideshowBlock-imgRow" >
	<div class="backgroundRow" style="background: url(<?php echo $imgInfo['thumbPath']?>) no-repeat left top; padding-left: 100px">
		<div class="cm-slideshowBlock-imgRowIcons" >
			<div style="float:right">
				<a onclick="SlideshowBlock.moveUp('<?php echo $imgInfo['slideshowImgId']?>')" class="moveUpLink"></a>
				<a onclick="SlideshowBlock.moveDown('<?php echo $imgInfo['slideshowImgId']?>')" class="moveDownLink"></a>									  
			</div>
			<div style="margin-top:4px"><a onclick="SlideshowBlock.removeImage('<?php echo $imgInfo['slideshowImgId']?>')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/delete_small.png" /></a></div>
		</div>
		<strong><?php echo $imgInfo['origfileName']?></strong><br/><br/>
		Duration: <input type="text" name="duration[]" value="<?php echo intval($imgInfo['duration'])?>" style="vertical-align: middle; width: 30px" />
		&nbsp;
		Fade Duration: <input type="text" name="fadeDuration[]" value="<?php echo intval($imgInfo['fadeDuration'])?>" style="vertical-align: middle; width: 30px" />
		&nbsp;
		Set Number: <input type="text" name="groupSet[]" value="<?php echo intval($imgInfo['groupSet'])?>" style="vertical-align: middle; width: 30px" /><br/>
		<div style="margin-top:4px">
		Link URL (optional): <input type="text" name="url[]" value="<?php echo $imgInfo['url']?>" style="vertical-align: middle; font-size: 10px; width: 140px" />
		<input type="hidden" name="imgBIDs[]" value="<?php echo $imgInfo['image_bID']?>">
		<input type="hidden" name="fileNames[]" value="<?php echo $imgInfo['fileName']?>">
		<input type="hidden" name="thumbPaths[]" value="<?php echo $imgInfo['thumbPath']?>">
		<input type="hidden" name="imgHeight[]" value="<?php echo $imgInfo['imgHeight']?>">
		<input type="hidden" name="origfileNames[]" value="<?php echo $imgInfo['origfileName']?>">		
		</div>
	</div>
</div>