<html xmlns:o="urn:schemas-microsoft-com:office:office" 
xmlns:x="urn:schemas-microsoft-com:office:excel" 
xmlns="http://www.w3.org/TR/REC-html40"> 

<head> 
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<style> 
.date { 
mso-number-format:"Short Date"; 
} 
.time { 
mso-number-format:"h\:mm\:ss\;\@"; 
} 
.duration {
mso-number-format:"h\:mm\;\@";
}
.decimal {
mso-number-format:Fixed;
}
</style> 
<!--[if gte mso 9]><xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Tabelle1</x:Name>
    <x:WorksheetOptions>
     <x:DefaultColWidth>10</x:DefaultColWidth>
     <x:Selected/>
     <x:Panes>
      <x:Pane>
       <x:Number>3</x:Number>
       <x:ActiveRow>4</x:ActiveRow>
       <x:ActiveCol>3</x:ActiveCol>
      </x:Pane>
     </x:Panes>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml><![endif]-->
</head> 

<body> 
<table> 
<thead><tr> 
<!-- column headers -->
    <?php global $kga ?>
<?php if (isset($this->columns['date'])):         ?> <td><?php echo $kga['dict']['datum']?></td>       <?php endif; ?>
<?php if (isset($this->columns['from'])):         ?> <td><?php echo $kga['dict']['in']?></td>          <?php endif; ?>
<?php if (isset($this->columns['to'])):           ?> <td><?php echo $kga['dict']['out']?></td>         <?php endif; ?>
<?php if (isset($this->columns['time'])):         ?> <td><?php echo $kga['dict']['time']?></td>        <?php endif; ?>
<?php if (isset($this->columns['dec_time'])):     ?> <td><?php echo $kga['dict']['timelabel']?></td>   <?php endif; ?>
<?php if (isset($this->columns['rate'])):         ?> <td><?php echo $kga['dict']['rate']?></td>        <?php endif; ?>
<?php if (isset($this->columns['wage'])):         ?> <td><?php echo $kga['dict']['wage']?>}</td>      <?php endif; ?>
<?php if (isset($this->columns['budget'])):       ?> <td><?php echo $kga['dict']['budget']?></td>      <?php endif; ?>
<?php if (isset($this->columns['approved'])):     ?> <td><?php echo $kga['dict']['approved']?></td>    <?php endif; ?>
<?php if (isset($this->columns['status'])):       ?> <td><?php echo $kga['dict']['status']?></td>      <?php endif; ?>
<?php if (isset($this->columns['billable'])):     ?> <td><?php echo $kga['dict']['billable']?></td>    <?php endif; ?>
<?php if (isset($this->columns['customer'])):     ?> <td><?php echo $kga['dict']['customer']?></td>    <?php endif; ?>
<?php if (isset($this->columns['project'])):      ?> <td><?php echo $kga['dict']['project']?></td>     <?php endif; ?>
<?php if (isset($this->columns['activity'])):     ?> <td><?php echo $kga['dict']['activity']?></td>    <?php endif; ?>
<?php if (isset($this->columns['description'])):  ?> <td><?php echo $kga['dict']['description']?></td> <?php endif; ?>
<?php if (isset($this->columns['comment'])):      ?> <td><?php echo $kga['dict']['comment']?></td>     <?php endif; ?>
<?php if (isset($this->columns['location'])):     ?> <td><?php echo $kga['dict']['location']?></td>   <?php endif; ?>
<?php if (isset($this->columns['ref_code'])): ?> <td><?php echo $kga['dict']['xpe_ref_code']?></td>  <?php endif; ?>
<?php if (isset($this->columns['user'])):         ?> <td><?php echo $kga['dict']['username']?></td>    <?php endif; ?>
<?php if (isset($this->columns['cleared'])):      ?> <td><?php echo $kga['dict']['cleared']?></td>     <?php endif; ?>
</tr> 
</thead> 
<?php foreach($this->exportData as $row): ?>
<tr> 

<?php if (isset($this->columns['date'])): ?>
                    <td class=date>
                        <?php  if ($this->custom_dateformat)
                            echo $this->escape(strftime($this->custom_dateformat,$row['time_in']));
                          else
                            echo $this->escape(strftime($kga['conf']['date_format_1'], $row['time_in']));
                        ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['from'])): ?>
                    <td align=right class=time>
                        <?php  if ($this->custom_timeformat)
                            echo $this->escape(strftime($this->custom_timeformat,$row['time_in']));
                          else
                            echo $this->escape(strftime("%H:%M", $row['time_in']));
                        ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['to'])): ?>
                    <td align=right class=time>
                    
<?php if ($row['time_out']): ?>
                        <?php  if ($this->custom_timeformat)
                            echo $this->escape(strftime($this->custom_timeformat,$row['time_out']));
                          else
                            echo $this->escape(strftime("%H:%M", $row['time_in']));
                        ?>
<?php else: ?>      
                        &ndash;&ndash;:&ndash;&ndash;
<?php endif; ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['time'])): ?>
                    <td align=right class=duration>
                        <?php echo $row['duration'] ? $row['formatted_duration'] : "&ndash;:&ndash;&ndash;" ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['dec_time'])): ?>
                    <td align=right class=decimal>
                        <?php echo $row['decimal_duration'] ?$row['decimal_duration'] : "&ndash;:&ndash;&ndash;" ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['rate'])): ?>
                    <td align=right class=decimal>
                            <?php echo $row['rate'] ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['wage'])): ?>
                    <td align=right class=decimal>
                        <?php echo $row['wage']? $row['wage'] : "&ndash;" ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['budget'])): ?>
                    <td>
                        <?php echo $this->escape($row['budget']); ?>
                    </td>
<?php endif; ?>



<?php if (isset($this->columns['approved'])): ?>
                    <td>
                        <?php echo $this->escape($row['approved']); ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['status'])): ?>
                    <td>
                        <?php echo $this->escape($row['status']); ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['billable'])): ?>
                    <td>
                        <?php echo $this->escape($row['billable']); ?>%
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['customer'])): ?>
                    <td>
                        <?php echo $this->escape($row['customer_name']); ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['project'])): ?>
                    <td>
                            <?php echo $this->escape($row['project_name']); ?>
                    </td>
<?php endif; ?>



<?php if (isset($this->columns['activity'])): ?>
                    <td>
                            <?php echo $this->escape($row['activity_name']); ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['description'])): ?>
                    <td>
                        <?php echo $this->escape($row['description']); ?>%
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['comment'])): ?>
                    <td>
                        <?php echo str_replace("\n", "&#10;", $this->escape($row['comment'])); ?>
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['location'])): ?>
                    <td>
                        <?php echo $this->escape($row['location']); ?>
                        
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['ref_code'])): ?>
                    <td>
                        <?php echo $this->escape($row['ref_code']); ?>
                        
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['user'])): ?>
                    <td>
                        <?php echo $this->escape($row['username']); ?>
                        
                    </td>
<?php endif; ?>


<?php if (isset($this->columns['cleared'])): ?>
          <td>
                      <?php if ($row['cleared']) echo "cleared" ?>
          </td>
<?php endif; ?>
          

                </tr>
               
<?php endforeach; ?>

</table> 

</body> 
</html>  
 
