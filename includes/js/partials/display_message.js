export function displayMessage(message, type='success', timer='' ){
	if(message == undefined){
		return;
	}

	let options	= {
		title: message.toString().trim()
	};y
	
	var options = {
		icon: icon,
		title: message.toString().trim(),
		confirmButtonColor: "#bd2919",
		cancelButtonColor: 'Crimson'
	};
	
	if(timer != ''){
		options['timer'] = timer;
	}
	
	new Main.Alert(message, type, options);
}