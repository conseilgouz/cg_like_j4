/* 
 * @module		CG Like for Joomla 4.x/5.x
 * @author		ConseilGouz
 * @license		GNU General Public License version 3 or later
 * @version 	2.1.0
 */ 
document.addEventListener('DOMContentLoaded', function() {
	buttons = document.querySelectorAll('[class^="cg_like_btn_"]');
	for (var i=0; i< buttons.length;i++) {
		['click', 'touchstart'].forEach(type => {
			buttons[i].addEventListener(type, function(e) {
				$this = this;
				e.stopPropagation();
				e.preventDefault();		
				$b = $this.getAttribute('data');
				if ($this.disabled) {
					return false;
				}
				$this.setAttribute('disabled', '')
				url = '?option=com_ajax&plugin=cglike&action=update&group=content&id='+ $b+'&format=raw';
				Joomla.request({
					method   : 'POST',
					url   : url,
					onSuccess: function (data, xhr) {
						$this.removeAttribute('disabled'); 
						var parsed = JSON.parse(data);
						if (parsed.ret == 0) {
							icon = document.querySelector('#cg_like_icon_'+$b)
							icon.classList.remove('icon-heart-empty');
							icon.classList.add('icon-heart');
							val = document.querySelector('#cg_like_val_'+$b);
							val.innerHTML = parsed.cnt;
							letexte = document.querySelector('#cg_result_'+$b);
							letexte.innerHTML = parsed.msg;
						} else {
							letexte = document.querySelector('#cg_result_'+$b);
							letexte.innerHTML = parsed.msg;
						}
					}
				});
				return false;
			});
		});	
	}			
})

