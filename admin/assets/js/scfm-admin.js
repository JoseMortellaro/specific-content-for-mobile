var eos_scfm_saving_button = document.getElementById('eos-scfm-save-options'),
	eos_msg = document.getElementById('eos-scfm-opts-msg'),
	eos_ajax_loader = document.getElementById('eos-scfm-ajax-loader');
if(eos_scfm_saving_button){
	eos_scfm_saving_button.addEventListener('click',function(e){
		eos_scfm_initialize_ajax();
		var opts = {},inputs = document.getElementsByClassName('eos-scfm-option');
		if(inputs && inputs.length > 0){
			var len = inputs.length,n = 0;
			for(n;n<len;++n){
				opts[inputs[n].name] = 'checkbox' === inputs[n].type ? inputs[n].checked : inputs[n].value;
			}
			opts['nonce'] = document.getElementById('eos_scfm_nonce_saving').value;
			var xhr = eos_scfm_sendData(opts,'eos_scfm_save_settings');
			xhr.onload = function(){
				eos_scfm_show_msg(xhr.response);
			};			
		}
	});
}
function eos_scfm_sendData(data,action){
	eos_scfm_initialize_ajax();
	var xhr = new XMLHttpRequest();
	var fd = new FormData();
	fd.append('data',JSON.stringify(data));
	xhr.open("POST",eos_scfm.ajaxurl + '?action=' + action,true);
	xhr.send(fd);
	xhr.onload = function() {
		if (xhr.status === 200) {
			var ids = JSON.parse(xhr.response),n = 0,row;
			for(n;n < ids.length;++n){
				row = document.getElementById('eos-scfm-' + ids[n]);
				if(row){
					row.style.backgroundColor = '#d14';
					row.parentNode.removeChild(row);
				}
			}
		}
		var el = document.getElementById('eos-scfm-doaction');
		el.className = el.className.replace(' eos-scfm-in-progress','');
	};	
	return xhr;
}

function eos_scfm_initialize_ajax(){
	if(eos_ajax_loader){
		eos_ajax_loader.className = eos_ajax_loader.className.replace(' eos-not-visible','');
	}
	if(eos_msg){
		eos_msg.innerHTML = '';
		eos_msg.className = 'notice eos-hidden';
	}
}	
function eos_scfm_show_msg(msg){
	if(eos_msg){
		eos_msg.innerHTML = '<span>' + msg + '</span>';
		eos_msg.className = 'notice';
	}
	if(eos_ajax_loader){
		eos_ajax_loader.className = eos_ajax_loader.className.replace(' eos-not-visible','') + ' eos-not-visible';
	}
}