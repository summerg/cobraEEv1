<?php $editMode = (isset($tab_name)) ? TRUE : FALSE; ?>
<?php if (!$editMode) $cId = ''; ?>

<div class="WeblogSection DevForm" <?php if ($editMode) echo "rel='{$cId}'"; ?> >
	<div class="Section">
		<div class="sHead"><?php if ($editMode) echo $channel_name; ?></div>
<!-- 	<div class="Elem clear">
			<div class="left"> <label><?=lang('ci:tab_name')?></label> </div>
			<div class="right"> <input name="channels[<?=$cId?>][tab_name]" type="text" class="text" <?php if ($editMode) echo "value=\"{$tab_name}\" ";  ?> /></div>
		</div> -->
		<div class="Elem clear">
			<div class="left"> <label><?=lang('ci:location_path')?></label> - <a href="#" class="VerifyPath gIcon DiscIcon"><?=lang('ci:verify_path')?></a> </div>
			<div class="right"> <input name="channels[<?=$cId?>][location_path]" type="text" class="text" <?php if ($editMode) echo "value=\"{$location_path}\" "; else echo "value=\"{$default_locpath}\" ";?> /> </div>
		</div>
		<div class="Elem clear">
			<div class="left"> <label><?=lang('ci:location_url')?></label> </div>
			<div class="right"> <input name="channels[<?=$cId?>][location_url]" type="text" class="text" <?php if ($editMode) echo "value=\"{$location_url}\" "; else echo "value=\"{$default_locurl}\" ";?> /> </div>
		</div>
		<div class="Elem clear">
			<div class="left"> <label><?=lang('ci:categories')?></label> (<?=lang('ci:categories_explain')?>) </div>
			<div class="right"> <input name="channels[<?=$cId?>][categories]" type="text" class="text" <?php if ($editMode) echo 'value="' . implode(',',$categories) .'" ';  ?> /> </div>
		</div>
		<div class="Elem clear">
			<div class="left"> <label><?=lang('ci:image_sizes')?></label> </div>
			<div class="right ImageSizes"> <br />
				<div class="header clear">
					<div class="one"><?=lang('ci:name')?></div>
					<div class="two"><?=lang('ci:width_px')?></div>
					<div class="three"><?=lang('ci:height_px')?></div>
					<div class="four"><?=lang('ci:quality')?></div>
					<div class="five"><?=lang('ci:greyscale')?></div>
				</div>
				<div class="ImageSizesResult">
					<?=$image_sizes?>
					<a title="Add new image size" rel="<?=$cId?>" class="AddSize" href="#"><?=lang('ci:add_size')?></a>
				</div>
			</div>
		</div>
	</div>
</div>