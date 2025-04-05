<? extend('master.php') ?>
<? startblock('extra_head') ?>


<script type="text/javascript" src="<?php echo base_url(); ?>assets/ck/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/ck/ckfinder/ckfinder.js"></script>

</script>


<script>
$ (document).ready(function(){
    CKEDITOR.replace('template');
});
</script>

<script>
	$(document).ready(function(){
		$('.custom-file-input').change(function() {
		  var file = $('.custom-file-input')[0].files[0].name;
		  $('#label_0').html(file);
		});
		$("#welcomeDiv button").on("click",function(){
			$("#file ~ .custom-file-label").text("");
			document.getElementById("file").value = null;
		});
	});
	function getfilename(cnt){
		var file = document.getElementById('file['+cnt+']').files[0].name;
		$('#label_'+cnt).html(file);
		uploadfile(cnt);
	}
	

</script>

<script type="text/javascript" src="<?php echo base_url(); ?>assets/markup/markitup/sets/default/set.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		jQuery('#myform').submit(function(){
	  	jQuery("input[type=file]").bind(function(){
		    jQuery(this).rules("add", {
		       size: '10000',
		       messages: {
		         accept :"Only jpeg, jpg or png images"
		       }
		     });
	   });
	});
	});
</script>
<script type="text/javascript">

    
function addFile(){
var file_name = document.getElementById("file").value;
var file_size=0;
$('.fileupload').each(function() { 
	if(this.files[0] == undefined){
		$("#attach_more_error").html("Please select file to attach more").css("color","red");
	}
	file_size=file_size+(this.files[0].size/1048576);
	});
if(file_size > 20)
{
	alert("Attachment size can not more than 20 MB.");
}
else{
if(file_name == ''){
	alert("Please select Attach file");

}
else{
  document.getElementById('welcomeDiv').style.display = "none";
  var cnt = $('#mytab').find('input:file').length;
	if(cnt == 4){
	alert('You have upload maximum four file in Attachment');
	}
        if (cnt < 4){

var root=document.getElementById('mytab').getElementsByTagName('tr')[0].parentNode;
var oR = cE('tr');var oC = cE('td');var oI = cE('input'); var oS=cE('span');var oA=cE('p');oL=cE('p');nR=cE('tr');nC=cE('td');
var attachLabel = cE('label'); var attachDiv = cE('div');var attachBtnDivRemove = cE('div');var attachRowDiv = cE('div');
cA(oC,'class','col-12');cA(oR,'class','col-12');cA(attachRowDiv,'class','col-12 mt-2');cA(attachDiv,'class','col-md-6 float-left');cA(attachLabel,'class','custom-file-label btn-primary btn-file text-left');cA(attachLabel,'id','label_'+cnt);cA(attachLabel,'for','file['+cnt+']');cA(oI,'type','file');cA(oI,'name','file[]');cA(oI,'id','file['+cnt+']');cA(oI,'class','p-0 h-100 custom-file-input form-control col-md-12 fileupload');cA(attachBtnDivRemove,'class','col-md-6 float-left py-2 px-2');cA(oS,'class','btn btn-danger mx-2');cA(oI,'onchange','getfilename('+cnt+')');
cA(oA,'style','color:red;display:none;');cA(oA,'id','attach_file_'+cnt);
oS.style.cursor='pointer';
        }    
oS.onclick=function(){
	if(cnt == 1){
	   document.getElementById('welcomeDiv').style.display = "block";
	
	}

  $("#attach_file_"+cnt).hide();
	document.getElementById("attach_file_"+cnt).innerHTML="";
  this.parentNode.parentNode.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode.parentNode.parentNode);
}
oS.appendChild(document.createTextNode('Remove'));
oC.appendChild(attachRowDiv);attachRowDiv.appendChild(attachDiv);attachDiv.appendChild(oI);attachDiv.appendChild(attachLabel);attachRowDiv.appendChild(attachBtnDivRemove);attachBtnDivRemove.appendChild(oS);oR.appendChild(oC);root.appendChild(oR);
attachRowDiv.appendChild(oA);
}
}
}
function cE(el){
this.obj =document.createElement(el);
return this.obj

}
function cA(obj,att,val){
obj.setAttribute(att,val);
return this.obj
}
function uploadfile(cnt){
 
	 $("#attach_more_error").html("");
 $("#attach_file").hide();
 $("#attach_file_"+cnt).hide();
var file_size=0;
$('.fileupload').each(function() {
	
	var ext=this.files[0].name.split(".");
	ext=ext[ext.length-1].toLowerCase();
	var arrayExtensions = ["jpg" , "jpeg", "png", "pdf" , "xlsx" , "doc", "docx", "img",  "txt"];
 
    if (arrayExtensions.lastIndexOf(ext) == -1) {
        
       var attach_file="Only allows file types of jpg, jpeg, png, pdf, xlsx, txt, docx and doc format.";
        if(cnt==undefined){
			$("#attach_file").show();
			document.getElementById("attach_file").innerHTML = attach_file;
			$("#file").val("");
		}else{
			$("#attach_file_"+cnt).show();
			document.getElementById("attach_file_"+cnt).innerHTML = attach_file;
			document.getElementById("file["+cnt+"]").value="";
			
		}
        
        

        
    }
	file_size=file_size+(this.files[0].size/1048576);
	
	});
if(file_size > 20)
{
	alert("Attachment size can not more than 20 MB.");
}
}

</script>
 
<script type="Text/JavaScript">
	
    $(document).ready(function(){
		 
  $('#subject').change(function(){
    $('#une').text('');
    return false;
  });
  $('#departmentid').change(function(){
    $('#department_error').text('');
    return false;
  });
  $('#template1').change(function(){
    $('#template2').text('');
    return false;
  });
   $('#template1').change(function(){
    $('#template').text('');
    return false;
  });
  $("#myfile1").change(function(){
  $('#uneifer').text('');
    return false;
 });
  $("#myfile2").change(function(){
    $('#uneifera').text('');
    return false;
  });
  $("#template").change(function(){
    $('#body').text('');
    return false;
  });
  $("#to").change(function(){
    $('#to').text('');
    return false;
  });
  $("#from").change(function(){
    $('#from').text('');
    return false;
  });
});


function regvalidate(){   
	if((document.myform.departmentid.value=="")){
	  document.getElementById('department_error').innerHTML = '<i style="color:#D95C5C; padding-left: 3px; padding-top: 10px;" class="fa fa-exclamation-triangle"></i><span class="popup_error error  no-padding"> Please select department</span>';
	  myform.departmentid.focus();
	  document.getElementById("department_error").style.display = "block"; 
	  return(false);
	}
		if((document.myform.subject.value=="")){
	  document.getElementById('une').innerHTML = '<i style="color:#D95C5C; padding-left: 3px; padding-top: 10px;" class="fa fa-exclamation-triangle"></i><span class="popup_error error  no-padding"> Please Enter subject</span>'
	  myform.subject.focus();
	  document.getElementById("une").style.display = "block"; 
	  return(false);
	}
							
	if(document.myform.departmentid.value !=="" && document.myform.subject.value !==""){
		$('.overlay').show();
	}
	
	if((document.myform.to.value=="")){
	  document.getElementById('to').innerHTML = "Please Enter Atleast One Value";
	  myform.to.focus();
	  return(false);
	}
	if((document.myform.from.value=="")){
	  document.getElementById('from').innerHTML = "Please Enter Atleast One Value";
	  myform.from.focus();
	  return(false);
	}
	var file_size=0;
	$('.fileupload').each(function() { 
		file_size=file_size+(this.files[0].size/1048576);
	});
	if(file_size > 20){
		alert("Attachment size can not more than 20 MB.");
		return false;
	}
	var usermessage = document.forms["myform"]["template"].value;
    if(usermessage === ''){
       document.getElementById('body').innerHTML = "Please Enter Message";
       document.getElementById('template').focus();
       return false;
    }else{
        return true;
    }
 }
</script>
<script>
function showDiv() {
	
 document.getElementById('location').style.display = "none";
			
    document.getElementById('welcomeDiv').style.display = "block";

}
</script>
<script>
function here_hide() {
    document.getElementById('welcomeDiv').style.display = "none";

}
</script>
<style>
table {
   
 border-radius: 10px;
 border:"1";
   }
#mceu_22  {
display :none;
}
#mceu_38-body  {
display :none;
}
#mceu_42-body{
display :none;
}

textarea.ckeditor {
visibility: visible !important;
display: block !important;
height: 0px !important;
border: none !important;
resize:none;
overflow: hidden; }
.form-control{
	
	padding-bottom: 6px!important;
}
</style>
<? endblock() ?>
<? startblock('page-title') ?><?=$page_title; ?><? endblock() ?>
<? startblock('content') ?>


	<div class="">
		<section class="slice color-three">
			<div class="w-section inverse pb-4">
				<div class="content">
					<div class="col-md-12 p-0">
						<? if(isset($error) && !empty($error)) {
							echo "<span class='row alert alert-danger m-2'>".$error."</span>";
						}?> 
						<div class="pop_md col-12">
							<form method="post" action="<?= base_url()?>supportticket/supportticket_details_save/" enctype="multipart/form-data" name="myform" id="myform" onsubmit="return(regvalidate())">
								<div class="card" id="floating-label">
									
										<h3 class="bg-secondary text-light p-3 rounded-top"><?php echo gettext("Open New Ticket"); ?></h3>
										<div class="col-md-12 col-sm-6">
											<div class="row">

									<?php
																				if ($this->session->userdata('logintype') == '-1' || $this->session->userdata('logintype') == '2' || $this->session->userdata('logintype') == '1') {
																			?>
											<div class="col-md-6 form-group">
												<label class="control-label p-0"><?php echo gettext("To"); ?> * </label>
												
													<?
													 $this->db->where('type <>','-1');
													 $account_id = form_dropdown('account_id', $this->db_model->build_dropdown_invoices("id,first_name,last_name,number,type", " accounts", "where_arr", array("reseller_id" => "0","status"=>"0","deleted" => "0","type <>" => "2")), '');
													 echo $account_id;
													?>
											</div>
											<div class="col-md-12 p-0 error_div" style="margin-left: 329px;"> 
												<div class="col-md-3"> </div>
											</div> 
									<?php }?>
											<div class="col-md-6 form-group">
												<label class="control-label p-0"><?php echo gettext("Department"); ?> * </label>
											
												<?php $reseller_id = ($account_data['type'] == '-1' || $account_data['type'] == 2 )?0:$account_data['id']; 
												 $department=form_dropdown('departmentid',$this->supportticket_model->build_concat_dropdown_departmnet("id,name,email_id", " department", "where_arr", array("status"=>"0","reseller_id"=> $reseller_id)), '');
													 echo $department;
												?>

										
											<div class="tooltips error_div pull-left no-padding display_none text-danger" id="department_error"></div>	
											</div>
										
										<div class="col-md-12 no-padding error_div"> 
											<div class="col-md-3 col-sm-12"> </div>
										</div>
										<div class="col-md-6 form-group">
												<label class="control-label p-0"><?php echo gettext("Priority"); ?> * </label>
											
												<select class='col-md-12 form-control form-control-lg selectpicker' data-live-search="true" name="priority">
													<option value="0"><?php echo gettext("High"); ?></option>
													<option value="1" ><?php echo gettext("Normal"); ?></option>
													<option value="2"><?php echo gettext("Low"); ?></option>
												</select>
											
										</div>

										<?php 	
										if ($this->session->userdata('logintype') == '-1' || $this->session->userdata('logintype') == '2') {
										?>
											<div class="col-md-6 form-group">
												<label class="control-label p-0"><?php echo gettext("Status"); ?> </label>
													<select class='col-md-12 form-control form-control-lg selectpicker' data-live-search="true" name="ticket_type">
														<option value="0" selected="selected"><?php echo gettext("Open"); ?></option>
														<option value="1"><?php echo gettext("Answerd"); ?></option>
														<option value="2"><?php echo gettext("Customer-Reply"); ?></option>
														<option value="3" ><?php echo gettext("On-hold"); ?></option>
														<option value="4"><?php echo gettext("Progress"); ?></option>
														<option value="5"><?php echo gettext("Close"); ?></option>
													</select>
											</div>
											<div class="col-md-12 no-padding error_div"> 
												<div class="col-md-3"> </div>
											</div>
									<?php }else{ ?>
											<input type="hidden" name="ticket_type" value="0">
									<?php } ?>
										<div class="col-md-12 form-group">
												<label class="control-label p-0"><?php echo gettext("Subject"); ?> * </label>
												<input type="text" name="subject"  id="subject" size="80" value="" maxlength="200"class="col-md-12 form-control form-control-lg"/>
												<div class="tooltips error_div pull-left no-padding display_none text-danger" id="une"></div>
										</div>
											
											
										


										
											<div class="col-md-12 form-group h-auto">
												<label class="control-label p-0"><?php echo gettext("Reply"); ?> </label>
												<div class="col-12 p-0">
													<textarea name="template" id ="template" size ="0" class="Emailtemplate">
														<?php 
															$template = (isset($template) && $template !="") ? $template : "";
															echo $template; ?>
													</textarea>
													
												</div>
												
											</div>
											
											<input type="hidden" name="temp" value=""> 
										
										<p class="error"></p>
									

 -->


									
									
									<div class="col-md-12 mb-4">
										<table id="mytab" class="col-12">
											<tbody class="col-12">
											 <tr class="col-12">
												<td bgcolor="#FFFFFF" class="col-12">
												  <div class="col-12 mt-4">
													<div class="col-md-6 float-left" data-ripple="">													
														<input type="file" name="file[]"  id="file" class="text field large upload_input fileupload custom-file-input"  style="" onclick="showDiv()" onchange='uploadfile()'>
														<label class="custom-file-label btn-primary btn-file text-left" id="label_0" for="file"> </label>
														<input type="hidden" name="add_new" id="add_new" value="0">            
													 </div>
													 
													 <div class="col-md-6 float-left py-2 px-2">
														<span id="welcomeDiv"  style="display:none;" class="answer_list" onclick="here_hide();">
															<button type="button" title="Remove" onClick="return reset_file()" class="btn btn-danger " style="cursor: pointer;"><?php echo gettext("Remove"); ?></button>
														</span>
													 </div>
													 <div class="col-md-12">		
														<div style="color:red;" id="attach_file"></div> 
														<div style="color:red;" id="location"></div> 
													</div>	
												 </td>
											</tr>
											</tbody>
										</table>
										<div>
											<span onclick="addFile()" style="cursor:pointer;" class='btn alert-success my-2'><i class="fa fa-plus"></i> <?php echo gettext("Attach more files"); ?></span> 
										</div>
										<span id="attach_more_error"></span> 
									</div> 

									<div class="col-sm-12 pb-4">
											<center>								
												<a href='".base_url()."email/email_client_get/'><input type='submit' class='btn btn-success'  value=<?php echo gettext("Submit"); ?>></a>
												<a href='/supportticket/supportticket_list/'><input type='button' class='btn btn-secondary ml-2'  value=<?php echo gettext("Cancel"); ?>></a>
											</center>
										</div>
									
								</div>
								</div>
								</div>
							</form>
						</div>
					</div>
				</div>
             </div>
          </section>
       </div>   


<? endblock() ?>
<? startblock('sidebar') ?>
<? endblock() ?>
<? end_extend() ?>
