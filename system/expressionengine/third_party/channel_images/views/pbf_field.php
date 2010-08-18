<?php if ($dupe_field == TRUE): ?>
<span style="font-weight:bold; color:red;"> <?=lang('ci:dupe_field')?> </span>
<input name="field_id_<?=$field_id?>[skip]" type="hidden" value="y" />
<?php elseif ($missing_settings == TRUE): ?>
<span style="font-weight:bold; color:red;"> <?=lang('ci:missing_settings')?> </span>
<input name="field_id_<?=$field_id?>[skip]" type="hidden" value="y" />
<?php else: ?>

<div id="CImagesField">
	<div class="TopBar">
		<div class="Buttons">
			<span class="SelectBtn"><em id="ChannelImagesSelect"></em></span>
			<a class="UploadBtn"><?=lang('ci:upload_images')?></a>
			<div class="UploadProgress hidden"> <div class="progress"><span><strong></strong>&nbsp;&nbsp;&nbsp;<em></em></span></div> </div>
		</div>
		<div class="Files clear">  </div>
		<input name="field_id_<?=$field_id?>[key]" type="hidden" value="<?=$temp_key?>" id="CITempKey"/>
		<input name="field_id_<?=$field_id?>[field_id]" type="hidden" value="<?=$field_id?>" id="CIFieldID"/>
		<input type="hidden" value="<?=$site_id?>" id="CISiteID"/>
	</div>

	<div class="Assigned">
		<div class="bar">
			<strong><?=lang('ci:assigned_images')?> (<span><?=$num_images?></span>)</strong>
			<div class="Headers clear">
				<div class="iOne"> <?=lang('ci:id')?> </div>
				<div class="iTwo"> <?=lang('ci:actions:move')?> </div>
				<div class="iThree"> <?=lang('ci:image')?> </div>
				<div class="iFour"> <?=lang('ci:title')?> </div>
				<div class="iFive"> <?=lang('ci:desc')?> </div>
				<div class="iSix"> <?=lang('ci:category')?> </div>
				<div class="iSeven"> <?=lang('ci:actions:cover')?> </div>
				<div class="iEight"> <?=lang('ci:actions:del')?> </div>
			</div>
		</div>
		<div class="Images clear"> <?=$assigned_images?> </div>
		<?php if ($num_images == 0):?> <p class="NoImages"><?=lang('ci:no_images')?></p> <?php endif; ?>
	</div>
</div>
<input name="field_id_<?=$field_id?>[skip]" type="hidden" value="n" />
<?php endif; ?>
