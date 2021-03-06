/**
 * @package     Droideforms.Module
 * @subpackage  droideforms
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author 		André Luiz Pereira <[<andre@next4.com.br>]>
 */

var sendDroideForms = {
	next_erro:[],
	ob_form:'',
	id_form:'',
	fm_data:'',
	success_disable_forms:true, /* recommend true */
	alert_class:'alert alert-',
	tipe_erros_class:{
		danger:'danger',
		warning:'warning',
		info:'info',
		success:'success'
	},
	init: function(id_form){

			sendDroideForms.ob_form = j(id_form);
			sendDroideForms.id_form = id_form;

			j(id_form).submit(function(event){
				sendDroideForms.fm_data = new FormData(this);
				event.preventDefault();
				sendDroideForms.next_erro = [];
				$formdata 	= j(this).data('droidevalid');

			//validate
			j.each($formdata,function(index, el) {
				//console.log(el);
				mensagem = el['mensagem'];
				condition = el['condition'];
				//delete el['mensagem'];
				validador = Object.keys(el).join(",");

				validador = validador.replace(',mensagem','');

				validador = validador.replace(',condition','');
				//name do validader nome / email / teste
				name = el[validador];


				//procurar nos campos
				j.each(sendDroideForms.ob_form.find('[name]'),function(index, el) {
					//var sear = j(el).prop('name').indexOf("["+name+"]");
					//console.log(j(el).prop('name')+' = '+name);

					if( j(el).prop('name') == name ){
						sendDroideForms._validate(validador,j(el),mensagem,condition);
					}
				});
				//fim do procurar por campos
			});
			//fim de procurar validacao

			if(sendDroideForms.next_erro.length != 0){
				sendDroideForms.alert('danger',sendDroideForms.next_erro.join("<br />"));
				sendDroideForms.next_erro = [];

			 }else{

				sendDroideForms._sendajax();

			 }

			return false;
		});
	},
	logs:function(msn){
		if(typeof msn == "string"){
			console.log('return: '+msn);
		}
	},
	_validate:function(type, obj,mensagem,condition){

		if(type == 'f_required'){

			if(obj.val() == ''){
				sendDroideForms.next_erro.push(mensagem);
			}
		}

		if(type == 'f_email'){
			var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
			if(obj.val() == '' || emailReg.test(obj.val()) == false){
				sendDroideForms.next_erro.push(mensagem);
			}
		}

		if(type == 'f_integer'){

			if(Math.floor(obj.val()) != obj.val() || j.isNumeric(obj.val()) != true){

				sendDroideForms.next_erro.push(mensagem);
			}

		}

		if(type=='f_file'){
			//add contiion

			var file = obj.val();

			var exts = condition.split(';');

			if ( file ) {
				var get_ext = file.split('.');
				get_ext = get_ext.reverse();
				if ( j.inArray( get_ext[0].toLowerCase(), exts) > -1 ){
				}else{
					sendDroideForms.next_erro.push(mensagem);
				}
			}
		}
		if(type=='f_size'){
			//add condition
			if(typeof obj[0].files[0] !== 'undefined'){
				var file = obj[0].files[0].size;
				kbps = (file/1024);
				if(kbps>condition){
					sendDroideForms.next_erro.push(mensagem);
				}
			};
		}
		if(type == 'f_custom'){
				sendDroideForms.custom_validator(obj,mensagem);
		}
	},
	custom_validator:function(obj,mensagem){
		return sendDroideForms.logs('Create instance of the custom_validator.');
	},
	alert:function(type, addtext){
		//remove o ultimo alert
		j(sendDroideForms.id_form+'_alert').remove();
		//imprime o alert
		j(sendDroideForms.id_form).before(
			j('<div/>',{
				    id: sendDroideForms.id_form.replace('#', '')+'_alert',
				    class:sendDroideForms.alert_class+type,
				    html: addtext
				})
			);
	},

	divLoad:function(){
		return "<div class='cssload-spin-box'></div> Load...";
	},
	__serializeAll:function(){
		sendDroideForms.fm_data.append('droide',1);
		sendDroideForms.fm_data.append('option','com_ajax');
		sendDroideForms.fm_data.append('module','droideforms');
		sendDroideForms.fm_data.append('id_ext',sendDroideForms.ob_form.data('extension'));
		sendDroideForms.fm_data.append('format','json');
		 return sendDroideForms.fm_data;
	},
	_sendajax:function(){

		data_ext = sendDroideForms.ob_form.data('extension');

		var formdata = sendDroideForms.__serializeAll();
		j.ajax({
			type   			: 'POST',
			data   			: formdata,
			dataTyoe:'JSON',
			contentType: false,
      processData: false,
			cache:false,
			//enctype			: 'multipart/form-data',
			beforeSend	:function(){
				sendDroideForms.alert(sendDroideForms.tipe_erros_class.info,sendDroideForms.divLoad());
				j(sendDroideForms.id_form+' input[type=submit], '+sendDroideForms.id_form+' button').each(function() {
					 j(this).attr('disabled',true);
				});
			},
			success: function (response) {
				dados = jQuery.parseJSON( response.data );
				if(dados.log != ''){
					sendDroideForms.logs(dados.log);
				}
				if(dados.error){
					sendDroideForms.alert(sendDroideForms.tipe_erros_class.danger,dados.msn);
					j(sendDroideForms.id_form+' input[type=submit], '+sendDroideForms.id_form+' button').each(function() {
						 j(this).removeAttr('disabled');
					});
				}else{

					sendDroideForms.alert(sendDroideForms.tipe_erros_class.success,dados.msn);
					if(sendDroideForms.success_disable_forms){
						sendDroideForms.ob_form.remove();
					}else{
						j.each(sendDroideForms.ob_form.find('[name]'),function(index, el) {
								j(this).val('');
								j(this).attr('disabled',true);
						});

						j(sendDroideForms.id_form+' input[type=submit], '+sendDroideForms.id_form+' button').each(function() {
						   j(this).attr('disabled',true);
						});

					}

				}

			}
		});
	}
}
