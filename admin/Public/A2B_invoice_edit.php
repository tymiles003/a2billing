<?php
include ("../lib/admin.defines.php");
include ("../lib/admin.module.access.php");
include ("../lib/admin.smarty.php");
include ("../lib/support/classes/invoice.php");
include ("../lib/support/classes/invoiceItem.php");

if (! has_rights (ACX_INVOICING)){
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}


getpost_ifset(array('id','action','price','description','vat','idc'));

if (empty($id))
{
Header ("Location: A2B_entity_invoice.php?atmenu=payment&section=13");
}


  if (!empty($action))
  {
  	echo $action;
    switch ($action)
    {
      case 'add':
		  $DBHandle  = DbConnect();
		  $invoice = new Invoice($id);
		  $invoice->insertInvoiceItem($description,$price,$vat);
		  Header ("Location: A2B_invoice_edit.php?"."id=".$id);
       case 'edit':
       	  if(!empty($idc) && is_numeric($idc)){
			  $DBHandle  = DbConnect();
			  $instance_sub_table = new Table("cc_invoice_item", "*");
	          $result=$instance_sub_table -> Get_list($DBHandle, "id = $idc" );
	          if(!is_array($result) || (sizeof($result)==0)){
	          	 Header ("Location: A2B_invoice_edit.php?"."id=".$id);
	          }else{
	          	$description=$result[0]['description'];
	          	$vat=$result[0]['VAT'];
	          	$price=$result[0]['price'];
	          	$date =$result[0]['date'];
	          }
       	  }
           break;
        case 'delete':
        	 if(!empty($idc) && is_numeric($idc)){
				  $DBHandle  = DbConnect();
				  $instance_sub_table = new Table("cc_invoice_item", "*");
		          $instance_sub_table -> Delete_Selected($DBHandle, "id = $idc" );
        	 }
	          Header ("Location: A2B_invoice_edit.php?"."id=".$id);
           break;
      
    }
  }




$invoice = new invoice($id);
$items = $invoice->loadItems();


$smarty->display('main.tpl');

?>
<table class="invoice_table" >
	<tr class="form_invoice_head">
	    <td width="75%"><font color="#FFFFFF"><?php echo gettext("INVOICE: "); ?></font><font color="#FFFFFF"><b><?php echo $invoice->getTitle();  ?></b></font></td>
	    <td width="25%"><font color="#FFFFFF"><?php echo gettext("REF: "); ?> </font><font color="Red"> <?php echo $invoice->getReference(); ?></font></td>
	</tr>
	<tr>
		<td>
		&nbsp;
		</td>
	</tr>
	<tr>
		<td >
		 <font style="font-weight:bold; " ><?php echo gettext("FOR : "); ?></font>  <?php echo $invoice->getUsernames();  ?>

		</td>
		<td>
		<font style="font-weight:bold; " ><?php echo gettext("DATE : "); ?></font>  <?php echo $invoice->getDate();  ?>
		</td>
	</tr>
	<tr>
		<td>
		 <?php if($invoice->getStatusDisplay()==0) $color="color:#5FA631;";
		 	   else $color="color:#EE6564;"    ?>
		 <font style="font-weight:bold;" ><?php echo gettext("STATUS : "); ?></font> <font style="<?php echo $color; ?>" >  <?php echo $invoice->getStatusDisplay();  ?> </font>
		 </td>
	</tr>
	<tr>
		<td colspan="2">
		<?php if($invoice->getStatusDisplay()==0) $color="color:#EE6564;";
		 	   else $color="color:#5FA631;"    ?>
		 <font style="font-weight:bold;" ><?php echo gettext("PAID STATUS : "); ?></font> <font style="<?php echo $color; ?>" > <?php echo $invoice->getPaidStatusDisplay();  ?> </font>

		</td>
	</tr>
	<tr>
		<td colspan="2">
		<br/>
		<font style="font-weight:bold; " ><?php echo gettext("DESCRIPTION : "); ?></font>  <br/> <?php echo $invoice->getDescription();  ?></td>
	</tr>
	
	<tr >
    <td colspan="2">
    	<table width="100%" cellspacing="10">
			<tr>
			  <th  width="10%">
			      &nbsp;
			  </th>
			  <th  width="35%">
			  	&nbsp;
			  </th>
			  <th align="right" width="17%">
			  	<font style="font-weight:bold; " >     
			  		<?php echo gettext("PRICE EXCL. VAT"); ?>
			  	</font>
			  </th>
			  <th align="right" width="10%">
			  	<font style="font-weight:bold; " >     
			  		<?php echo gettext("VAT"); ?>
			  	</font>
			  </th>
			   <th align="right" width="17%">
			  	<font style="font-weight:bold; " >     
			  		<?php echo gettext("PRICE INCL. VAT"); ?>
			  	</font>
			  </th>
			  <th  width="10%">
			  &nbsp;
			  </th>
			</tr> 
			
			<?php foreach ($items as $item){ ?>
			<tr style="vertical-align:top;" >
				<td>
					<?php echo $item->getDate(); ?>
				</td>
				<td >
					<?php echo $item->getDescription(); ?>
				</td>
				<td align="right">
					<?php echo money_format('%.2n',round($item->getPrice(),2)); ?>
				</td>
				<td align="right">
					<?php echo money_format('%.2n',round($item->getVAT(),2)); ?>
				</td>
				<td align="right">
					<?php echo money_format('%.2n',round($item->getPrice()*(1+($item->getVAT()/100)),2)); ?>
				</td>
				<td align="center">
					<a href="<?php echo $PHP_SELF ?>?id=<?php echo $id; ?>&action=edit&idc=<?php echo $item->getId();?>"><img src="<?php echo Images_Path ?>/edit.png" title="<?php echo gettext("Edit Item") ?>" alt="<?php echo gettext("Edit Item") ?>" border="0"></a>
					<a href="<?php echo $PHP_SELF ?>?id=<?php echo $id; ?>&action=delete&idc=<?php echo $item->getId();?>"><img src="<?php echo Images_Path ?>/delete.png" title="<?php echo gettext("Delete Item") ?>" alt="<?php echo gettext("Delete Item") ?>" border="0"></a>
				</td>
			</tr>  
			 <?php } ?>	
			 
			 
			<tr>
	    	 	<td colspan="6">
	    	 		&nbsp;
	    	 	</td>
    	 	</tr>	 
    	<?php
		$price_without_vat = 0;
		$price_with_vat = 0;
		$vat_array = array();
    	foreach ($items as $item){  
    	 	$price_without_vat = $price_without_vat + $item->getPrice();
    		$price_with_vat = $price_with_vat + ($item->getPrice()*(1+($item->getVAT()/100)));
    		if(array_key_exists("".$item->getVAT(),$vat_array)){
    			$vat_array[$item->getVAT()] = $vat_array[$item->getVAT()] + $item->getPrice()*($item->getVAT()/100) ;
    		}else{
    			$vat_array[$item->getVAT()] =  $item->getPrice()*($item->getVAT()/100) ;
    		}
    	 } 
    	
    	 ?>
    	 	<tr>
	    	 	<td colspan="2">
	    	 		&nbsp;
	    	 	</td>
	    	 	<td colspan="2" align="right">
	    	 		<?php echo gettext("TOTAL EXCL. VAT") ?>&nbsp;:
	    	 	</td>
	    	 	<td align="right" >
	    	 		<?php echo money_format('%.2n',round($price_without_vat,2)); ?>
	    	 	</td>
	    	 	<td >
	    	 		&nbsp;
	    	 	</td>
    	 	</tr>
    	 	<?php foreach ($vat_array as $key => $val) { ?>
    	 		
    	 	<tr>
	    	 	<td colspan="2">
	    	 		&nbsp;
	    	 	</td>
	    	 	<td colspan="2" align="right">
	    	 		<?php echo gettext("TOTAL VAT ($key%)") ?>&nbsp;:
	    	 	</td>
	    	 	<td align="right" >
	    	 		<?php echo money_format('%.2n',round($val,2)); ?>
	    	 	</td>
	    	 	<td >
	    	 		&nbsp;
	    	 	</td>
    	 	</tr>
    	 	
    	 	<?php } ?>
    	 	<tr>
	    	 	<td colspan="2">
	    	 		&nbsp;
	    	 	</td>
	    	 	<td colspan="2" align="right">
	    	 		<?php echo gettext("TOTAL INCL. VAT") ?>&nbsp;:
	    	 	</td>
	    	 	<td align="right">
	    	 		<?php echo money_format('%.2n',round($price_with_vat,2)); ?>
	    	 	</td>
	    	 	<td >
	    	 		&nbsp;
	    	 	</td>
    	 	</tr>
    	 
    	</table>
    	
    	 
	</td>
	</tr>
</table>

<br/>


  <form action="<?php echo $PHP_SELF.'?id='.$invoice->getId(); ?>" method="post" >
 	<input id="action" type="hidden" name="action" value="<?php if(!empty($idc)) echo "edit"; else echo "add" ?>"/>
	<input id="idc" type="hidden" name="idc" value="<?php if(!empty($idc)) echo $idc;?>"/>
	<table class="invoice_table">
		<tr class="form_invoice_head">
	    	<td colspan="2" align="center"><font color="#FFFFFF"><?php echo gettext("ADD INVOICE ITEM "); ?></font></td>
		</tr>
		<tr >
	    	<td colspan="2">&nbsp;</td>
		</tr>
		<?php
			if(empty($date)){
				$date = date("Y-m-d H:i:s");
			}
		?>
		<tr>
			<td ><font style="font-weight:bold; " ><?php echo gettext("DATE : "); ?>
			 </td>
			 <td>
			 <input type="text" class="form_input_text" name="price" size="20" maxlength="20" <?php if(!empty($date)) echo 'value="'.$date.'"';?>/>
			 </td>
		</tr>
		<tr>
			<td ><font style="font-weight:bold; " ><?php echo gettext("PRICE : "); ?>
			 </td>
			 <td>
			 <input type="text" class="form_input_text" name="price" size="10" maxlength="10" <?php if(!empty($price)) echo 'value="'.$price.'"';?>/>
			 </td>
		</tr>
		<tr>
			<td ><font style="font-weight:bold; " ><?php echo gettext("VAT : "); ?>
			 </td>
			 <td>
			 <input type="text" class="form_input_text" name="vat" size="5" maxlength="5" <?php if(!empty($vat)) echo 'value="'.$vat.'"';?> />
			 </td>
		</tr>
		<tr>
			<td ><font style="font-weight:bold; " ><?php echo gettext("DESCRIPTION : "); ?>
			 </td>
			<td>
			 <textarea class="form_input_textarea" name="description" cols="50" rows="5"><?php if(!empty($description)) echo $description ;?></textarea>
			 </td>
		</tr>
		<tr>
			<td colspan="2" align="right">
				<input class="form_input_button" type="submit" value="<?php if(!empty($idc)) echo "UPDATE"; else echo "ADD" ?>"/>
			 </td>
		</tr>

	</table>
  </form>

