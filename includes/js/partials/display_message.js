export function displayMessage(message, type='success', timer='' ){
	if(message == undefined){
		return;
	}
	
	let options = {
		confirmButtonText: 'OK',
		cancelButtonColor: 'Crimson',
		cancelButtonText: 'Cancel'
	};
	
	if(timer != ''){
		options['timer'] = timer;
	}
	
	new Main.Alert(message.toString().trim(), type, options);
}