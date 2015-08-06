    <script type="text/javascript"> 
        $(document).ready(function() {
            adm_ext_onload();
        }); 
    </script>

<div id="adm_ext_panel">

<!-- edit customers -->

    <div id="adm_ext_sub6">
        <div class="adm_ext_panel_header">
            <a onClick="adm_ext_subtab_expand(6)">
                <span class="adm_ext_accordeon_triangle"></span>
                <?php echo $GLOBALS['kga']['dict']['customers']?>
            </a>
        </div>
        <div id="adm_ext_s6" class="adm_ext_subtab adm_ext_subject">
            <?php echo $this->customer_display?>
        </div>
    </div>

<!-- edit projects -->

    <div id="adm_ext_sub7">
        <div class="adm_ext_panel_header">
            <a onClick="adm_ext_subtab_expand(7)">
                <span class="adm_ext_accordeon_triangle"></span>
                <?php echo $GLOBALS['kga']['dict']['projects']?>
            </a>
        </div>
        <div id="adm_ext_s7" class="adm_ext_subtab adm_ext_subject">
            <?php echo $this->project_display?>
        </div>
    </div>

<!-- edit activities -->

<div id="adm_ext_sub8">
    <div class="adm_ext_panel_header">
        <a onClick="adm_ext_subtab_expand(8)">
            <span class="adm_ext_accordeon_triangle"></span>
            <?php echo $GLOBALS['kga']['dict']['activities']?>
        </a>
    </div>
    <div id="adm_ext_s8" class="adm_ext_subtab adm_ext_subject">
        <?php echo $this->activity_display ?>
    </div>
</div>

<!-- edit users -->
	<div id="adm_ext_sub1">
		<div class="adm_ext_panel_header">
			<a onClick="adm_ext_subtab_expand(1)">
			    <span class="adm_ext_accordeon_triangle"></span>
			    <?php echo $GLOBALS['kga']['dict']['users']?>
			</a>
		</div>
		<div id="adm_ext_s1" class="adm_ext_subtab">
			<?php echo $this->admin['users']; ?>
		</div>
	</div>

<!-- edit groups -->

	<div id="adm_ext_sub2">
		<div class="adm_ext_panel_header">
			<a onClick="adm_ext_subtab_expand(2)">
			    <span class="adm_ext_accordeon_triangle"></span>
			    <?php echo $GLOBALS['kga']['dict']['groups']?>
			</a>
		</div>
		<div id="adm_ext_s2" class="adm_ext_subtab">
			<?php echo $this->admin['groups']?>
		</div>
	</div>

<!-- edit global roles -->

        <div id="adm_ext_sub9">
                <div class="adm_ext_panel_header">
                        <a onClick="adm_ext_subtab_expand(9)">
                            <span class="adm_ext_accordeon_triangle"></span>
                            <?php echo $GLOBALS['kga']['dict']['globalRoles']?>
                        </a>
                </div>
                <div id="adm_ext_s9" class="adm_ext_subtab">
                        <?php echo $this->globalRoles_display?>
                </div>
        </div>

<!-- edit membership roles -->

        <div id="adm_ext_sub10">
                <div class="adm_ext_panel_header">
                        <a onClick="adm_ext_subtab_expand(10)">
                            <span class="adm_ext_accordeon_triangle"></span>
                            <?php echo $GLOBALS['kga']['dict']['membershipRoles']?>
                        </a>
                </div>
                <div id="adm_ext_s10" class="adm_ext_subtab">
                        <?php echo $this->membershipRoles_display?>
                </div>
        </div>
	
<!-- edit status -->

	<div id="adm_ext_sub3">
		<div class="adm_ext_panel_header">
			<a onClick="adm_ext_subtab_expand(3)">
			    <span class="adm_ext_accordeon_triangle"></span>
			    <?php echo $GLOBALS['kga']['dict']['status']?>
			</a>
		</div>
		<div id="adm_ext_s3" class="adm_ext_subtab">
			<?php echo $this->admin['status'] ?>
		</div>
	</div>
	
<?php if ($this->showAdvancedTab): ?>
    <!-- advanced -->
	<div id="adm_ext_sub4">
		<div class="adm_ext_panel_header">
			<a onClick="adm_ext_subtab_expand(4)">
			    <span class="adm_ext_accordeon_triangle"></span>
			    <?php echo $GLOBALS['kga']['dict']['advanced']?>
			</a>
		</div>
		<div id="adm_ext_s4" class="adm_ext_subtab">
			<?php echo $this->admin['advanced']?>
		</div>
	</div>
<?php endif; ?>

<?php if (isset($this->admin['database'])): ?>
    <!-- DB -->
    <div id="adm_ext_sub5">
        <div class="adm_ext_panel_header">
            <a onClick="adm_ext_subtab_expand(5)">
                <span class="adm_ext_accordeon_triangle"></span>
                <?php echo $GLOBALS['kga']['dict']['database']?>
            </a>
        </div>
        <div id="adm_ext_s5" class="adm_ext_subtab">
            <?php echo $this->admin['database']?>
        </div>
    </div>
<?php endif; ?>

</div>
