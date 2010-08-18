<div class="size clear">
	<div class="one"> <input type="text" value="<?=$name?>" name="channels[<?=$cId?>][image_size][][name]"/> </div>
	<div class="two"> <input type="text" value="<?=$sizes['w']?>" name="channels[<?=$cId?>][image_size][][width]"/> </div>
	<div class="three"> <input type="text" value="<?=$sizes['h']?>" name="channels[<?=$cId?>][image_size][][height]"/> </div>
	<div class="four"> <input type="text" value="<?=$sizes['q']?>" name="channels[<?=$cId?>][image_size][][quality]"/> </div>
	<div class="five"> <input type="checkbox" name="channels[<?=$cId?>][image_size][][grey]" value="y" <?php if (isset($sizes['g']) == TRUE AND $sizes['g'] == 'y') echo 'checked'; ?> /> </div>
	<a class="DelSize" href="#">&nbsp;</a>
</div>
