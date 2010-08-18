<form method="post" action="<?=$base_url?>P=update_settings">

<strong><?=lang('ci:choose_weblog');?></strong>
<div class="TopStrip">
	<?php foreach ($channels as $cId => $cName):?>
	<div class="Section  <?php if (isset($settings['channels'][$cId])) echo 'Assigned'; ?> ">
		<input type="checkbox" value="<?=$cId?>"  <?php if (isset($settings['channels'][$cId])) echo 'checked'; ?> name="bla[]" >
		<label><?=$cName?></label>
	</div>
	<?php endforeach; ?>
</div>

<div>
	<div id="WeblogsCollector"> <?=$aChannels?> </div>
	<br class="clear" />
</div>



<br />
<div><button class="SubmitBtn SaveSettings submit"> <em class="LoadingIcon hidden">&nbsp;</em>Save Settings </button></div>

<div class="SettingsSaved hidden"> <?=lang('ci:settings_saved')?> </div>

</form>



<div id="BlankWeblog" class="hidden"><?=$BlankWeblog?></div>
<div class="size clear hidden" id="BaseSize">
	<div class="one"> <input name="channels[ID][image_size][][name]"  value="" type="text"> </div>
	<div class="two"> <input name="channels[ID][image_size][][width]"  value="" type="text"> </div>
	<div class="three"> <input name="channels[ID][image_size][][height]"  value="" type="text"> </div>
	<div class="four"> <input name="channels[ID][image_size][][quality]"  value="" type="text"> </div>
	<a href="#" class="DelSize">&nbsp;</a>
</div>