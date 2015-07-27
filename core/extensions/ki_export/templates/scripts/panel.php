<?php global $kga ?>
<script type="text/javascript"> 
        $(document).ready(function() {
            xpo_ext_onload();
        }); 
    </script>

<div id="xpo_panel">
	<div class="w">
		<div class="c">
			<div class="w">
				<div class="c">
					
					<div id="xpo_ext_tab_filter">
						<select id="xpo_ext_tab_filter_cleared" name="cleared" onChange="xpo_ext_reload()">
						  <option value="-1" <?php if (!$kga['pref']['hide_cleared_entries']):?> selected="selected"<?php endif;?>> <?php echo $kga['lang']['export_extension']['cleared_all']?></option>
						  <option value="1"><?php echo $kga['lang']['export_extension']['cleared_cleared']?></option>
						  <option value="0" <?php if ($kga['pref']['hide_cleared_entries']):?> selected="selected" <?php endif; ?>> <?php echo $kga['lang']['export_extension']['cleared_open']?></option>
						</select>
						<select id="xpo_ext_tab_filter_refundable" name="refundable" onChange="xpo_ext_reload()">
						  <option value="-1" selected="selected"><?php echo $kga['lang']['export_extension']['refundable_all']?></option>
						  <option value="0"><?php echo $kga['lang']['export_extension']['refundable_refundable']?></option>
						  <option value="1"><?php echo $kga['lang']['export_extension']['refundable_not_refundable']?></option>
						</select>
                        <select id="xpo_ext_tab_filter_type" name="type" onChange="xpo_ext_reload()">
                         <option value="-1" selected="selected"><?php echo $kga['lang']['export_extension']['times_and_expenses']?></option>
                         <option value="0"><?php echo $kga['lang']['export_extension']['times']?></option>
                         <option value="1"><?php echo $kga['lang']['export_extension']['expenses']?></option>
                       </select>
					</div>
					
					<div id="xpo_ext_tab_timeformat">
						<span><?php echo $kga['lang']['export_extension']['timeformat']?>:<a href="#" class="helpfloater"><?php echo $kga['lang']['export_extension']['export_timeformat_help']?></a></span>
						<input type="text" name="time_format" value="<?php echo $this->escape($this->timeformat)?>" id="xpo_ext_timeformat" onChange="xpo_ext_reload()">
						<span><?php echo $kga['lang']['export_extension']['dateformat']?>:<a href="#" class="helpfloater"><?php echo $kga['lang']['export_extension']['export_timeformat_help']?></a></span>
						<input type="text" name="date_format" value="<?php echo $this->escape($this->dateformat)?>" id="xpo_ext_dateformat" onChange="xpo_ext_reload()">
					</div>
					
					<div id="xpo_ext_tab_location">
						<span><?php echo $kga['lang']['export_extension']['stdrd_location']?></span>
						<input type="text" name="std_loc" value="" id="xpo_ext_default_location" onChange="xpo_ext_reload()">
					</div>
					
				</div>
			</div>
			<div class="l">&nbsp;</div><div class="r">&nbsp;</div>
		</div>
	</div>
	<div class="l">
		<div class="w">
			<div class="c">
				<a id="xpo_ext_select_filter"     title="<?php echo $kga['lang']['tip']['xpo_fltr_location']; ?>" href="#" class="select_btn"><?php echo $kga['lang']['filter']?></a>
				<a id="xpo_ext_select_location"   title="<?php echo $kga['lang']['tip']['xpo_fltr_values']; ?>" href="#" class="select_btn"><?php echo $kga['lang']['export_extension']['stdrd_location']?></a>
				<a id="xpo_ext_select_timeformat" title="<?php echo $kga['lang']['tip']['xpo_format']; ?>" href="#" class="select_btn"><?php echo $kga['lang']['export_extension']['timeformat']?></a>
			</div>
		</div>
		<div class="l">&nbsp;</div>
	</div>
	<div class="r">
		<div class="w">
			<div class="c">
				<a id="xpo_ext_export_pdf" title="<?php echo $kga['lang']['tip']['xpo_to_pdf']; ?>" href="#" class="output_btn"><?php echo $kga['lang']['export_extension']['exportPDF']?></a>
				<a id="xpo_ext_export_xls" title="<?php echo $kga['lang']['tip']['xpo_to_xls']; ?>" href="#" class="output_btn"><?php echo $kga['lang']['export_extension']['exportXLS']?></a>
				<a id="xpo_ext_export_csv" title="<?php echo $kga['lang']['tip']['xpo_to_cvs']; ?>" href="#" class="output_btn"><?php echo $kga['lang']['export_extension']['exportCSV']?></a>
				<a id="xpo_ext_print"      title="<?php echo $kga['lang']['tip']['xpo_to_html']; ?>" href="#" class="output_btn"><?php echo $kga['lang']['export_extension']['print']?></a>
			</div>
		</div>
		<div class="l">&nbsp;</div><div class="r">&nbsp;</div>
	</div>
</div>






