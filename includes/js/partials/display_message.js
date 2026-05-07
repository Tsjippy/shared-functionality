export function displayMessage(message, type='success', timer='' ){
	if(message == undefined){
		return;
	}
	
	let options = {
		title: message.toString().trim(),
		confirmButtonText: 'OK',
		cancelButtonColor: 'Crimson',
		cancelButtonText: 'Cancel'
	};
	
	if(timer != ''){
		options['timer'] = timer;
	}
	
	new Main.Alert(message, type, options);
}