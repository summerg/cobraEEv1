<div class='Image clear <?php if ($cover == 1) echo 'PrimaryImage';?>'>
	<div class='iOne'> <?=$image_id?> </div>
	<div class='iTwo'> <a href='#' class='gIcon MoveIcon ImageMove'></a> </div>
	<div class='iThree'> <a href='<?=$big_img_url?>' class='ImgUrl' rel='ChannelImagesGal'><img src='<?=$small_img_url?>' width='50px'/></a> </div>
	<div class='iFour' rel='title'><?=$title?></div>
	<div class='iFive' rel='desc'><?=$description?></div>
	<div class='iSix' rel='category'><?=$category?></div>
	<div class='iSeven'> <a href='#' class='gIcon <?php if ($cover == 1) echo 'StarIcon'; else echo 'StarGreyIcon';?> ImageCover'></a> </div>
	<div class='iEight'> <a href='#' class='gIcon DeleteIcon ImageDel'></a> </div>
	<div class='hidden inputs'>
	<?php if ($image_id > 0):?>
		<input name="field_id_<?=$field_id?>[images][<?=$image_order?>][title]" value="<?=$title?>" class="title">
		<textarea name="field_id_<?=$field_id?>[images][<?=$image_order?>][desc]" class="desc"><?=$description?></textarea>
		<input name="field_id_<?=$field_id?>[images][<?=$image_order?>][category]" value="<?=$category?>" class="category">
		<input name="field_id_<?=$field_id?>[images][<?=$image_order?>][imageid]" value="<?=$image_id?>" class="imageid">
		<input name="field_id_<?=$field_id?>[images][<?=$image_order?>][filename]" value="<?=$filename?>" class="filename">
		<input name="field_id_<?=$field_id?>[images][<?=$image_order?>][cover]" value="<?=$cover?>" class="cover">
	<?php else:?>
		#REPLACE#
	<?php endif;?>
	</div>
</div>